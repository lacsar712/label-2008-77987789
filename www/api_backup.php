<?php
header('Content-Type: application/json; charset=UTF-8');

require_once 'config.php';
ensureBackupTables();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => '仅支持POST或GET请求']);
    exit;
}

$action = '';
$input = null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
} else {
    if (isset($_FILES['sql_file']) || isset($_POST['action'])) {
        $action = $_POST['action'] ?? '';
    } else {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        $action = $input['action'] ?? '';
    }
}

$conn = getConnection();
$backupDir = getBackupDir();

switch ($action) {
    case 'create':
        handleCreateBackup($conn, $backupDir, $input ?? []);
        break;
    case 'list':
        handleListBackups($conn, $input ?? []);
        break;
    case 'download':
        handleDownloadBackup($conn, $backupDir, $_GET['id'] ?? 0);
        break;
    case 'delete':
        handleDeleteBackup($conn, $backupDir, $input ?? []);
        break;
    case 'rename':
        handleRenameBackup($conn, $input ?? []);
        break;
    case 'remark':
        handleUpdateRemark($conn, $input ?? []);
        break;
    case 'upload':
        handleUploadBackup($conn, $backupDir);
        break;
    case 'restore':
        handleRestoreBackup($conn, $backupDir, $input ?? []);
        break;
    case 'restore_stream':
        handleRestoreStream($conn, $backupDir, $input ?? []);
        break;
    default:
        echo json_encode(['success' => false, 'message' => '未知操作: ' . $action]);
        break;
}

closeConnection($conn);

function handleCreateBackup($conn, $backupDir, $input) {
    $logs = [];
    $backupType = $input['backup_type'] ?? 'manual';
    $displayName = $input['display_name'] ?? '';

    try {
        $logs[] = '[INFO] 开始创建备份...';
        $timestamp = date('Ymd_His');
        $filename = 'backup_' . $timestamp . '_' . bin2hex(random_bytes(4)) . '.sql';
        $filepath = $backupDir . '/' . $filename;

        if ($displayName === '') {
            $displayName = '手动备份_' . date('Y-m-d H:i:s');
        }

        $stmt = $conn->prepare("INSERT INTO backup_records (filename, display_name, file_size, backup_type, status) VALUES (?, ?, 0, ?, 'processing')");
        $stmt->bind_param('sss', $filename, $displayName, $backupType);
        $stmt->execute();
        $recordId = $conn->insert_id;
        $stmt->close();

        $logs[] = '[INFO] 备份记录已创建，ID: ' . $recordId;

        $sqlContent = generateBackupSQL($conn, $logs);
        if ($sqlContent === false) {
            throw new Exception('生成备份SQL失败');
        }

        $logs[] = '[INFO] SQL生成完成，正在写入文件...';
        $writeResult = file_put_contents($filepath, $sqlContent);
        if ($writeResult === false) {
            throw new Exception('写入备份文件失败');
        }

        $fileSize = filesize($filepath);
        $logs[] = '[INFO] 文件写入成功，文件大小: ' . formatBytes($fileSize);

        $stmt = $conn->prepare("UPDATE backup_records SET file_size = ?, status = 'success' WHERE id = ?");
        $stmt->bind_param('ii', $fileSize, $recordId);
        $stmt->execute();
        $stmt->close();

        $logs[] = '[SUCCESS] 备份创建完成！';

        echo json_encode([
            'success' => true,
            'message' => '备份创建成功',
            'data' => [
                'id' => $recordId,
                'filename' => $filename,
                'display_name' => $displayName,
                'file_size' => $fileSize,
                'file_size_formatted' => formatBytes($fileSize),
                'created_at' => date('Y-m-d H:i:s')
            ],
            'logs' => $logs
        ]);
    } catch (Exception $e) {
        if (isset($recordId)) {
            $stmt = $conn->prepare("UPDATE backup_records SET status = 'failed', error_log = ? WHERE id = ?");
            $errorMsg = $e->getMessage();
            $stmt->bind_param('si', $errorMsg, $recordId);
            $stmt->execute();
            $stmt->close();
        }
        $logs[] = '[ERROR] ' . $e->getMessage();
        echo json_encode([
            'success' => false,
            'message' => '备份失败: ' . $e->getMessage(),
            'logs' => $logs
        ]);
    }
}

function generateBackupSQL($conn, &$logs) {
    $tables = getBackupTables();
    $sql = "-- ========================================\n";
    $sql .= "-- 数据库备份文件\n";
    $sql .= "-- 生成时间: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- 数据库: " . DB_NAME . "\n";
    $sql .= "-- ========================================\n\n";
    $sql .= "SET NAMES utf8mb4;\n";
    $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

    foreach ($tables as $table) {
        $logs[] = '[INFO] 正在导出表: ' . $table;
        $sql .= "-- ------------------------------\n";
        $sql .= "-- 表结构: $table\n";
        $sql .= "-- ------------------------------\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";

        $result = $conn->query("SHOW CREATE TABLE `$table`");
        if (!$result) {
            $logs[] = '[WARN] 跳过表 ' . $table . ': 不存在';
            continue;
        }
        $row = $result->fetch_row();
        $sql .= $row[1] . ";\n\n";
        $result->free();

        $sql .= "-- ------------------------------\n";
        $sql .= "-- 表数据: $table\n";
        $sql .= "-- ------------------------------\n";

        $result = $conn->query("SELECT * FROM `$table`");
        if ($result && $result->num_rows > 0) {
            $columns = [];
            $fields = $result->fetch_fields();
            foreach ($fields as $field) {
                $columns[] = "`" . $field->name . "`";
            }
            $colStr = implode(', ', $columns);

            $rowCount = 0;
            while ($row = $result->fetch_assoc()) {
                $values = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . $conn->real_escape_string($val) . "'";
                    }
                }
                $sql .= "INSERT INTO `$table` ($colStr) VALUES (" . implode(', ', $values) . ");\n";
                $rowCount++;
            }
            $logs[] = '[INFO]   导出 ' . $rowCount . ' 条记录';
            $result->free();
        } else {
            $logs[] = '[INFO]   空表，无数据导出';
        }
        $sql .= "\n";
    }

    $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    return $sql;
}

function handleListBackups($conn, $input) {
    $page = max(1, intval($input['page'] ?? 1));
    $per_page = max(1, intval($input['per_page'] ?? 10));
    $keyword = $input['keyword'] ?? '';
    $backupType = $input['backup_type'] ?? null;

    $where = [];
    $params = [];
    $types = '';

    if ($keyword !== '') {
        $where[] = '(display_name LIKE ? OR filename LIKE ? OR remark LIKE ?)';
        $kw = '%' . sanitize($keyword) . '%';
        $params[] = $kw;
        $params[] = $kw;
        $params[] = $kw;
        $types .= 'sss';
    }
    if ($backupType !== null && $backupType !== '') {
        $where[] = 'backup_type = ?';
        $params[] = sanitize($backupType);
        $types .= 's';
    }

    $whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "SELECT COUNT(*) AS cnt FROM backup_records $whereSql";
    $stmt = $conn->prepare($countSql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    $total_pages = max(1, ceil($total / $per_page));
    $offset = ($page - 1) * $per_page;

    $dataSql = "SELECT * FROM backup_records $whereSql ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $dataTypes = $types . 'ii';
    $dataParams = array_merge($params, [$per_page, $offset]);

    $stmt = $conn->prepare($dataSql);
    $stmt->bind_param($dataTypes, ...$dataParams);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $row['file_size_formatted'] = formatBytes($row['file_size']);
        $items[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'data' => [
            'total' => intval($total),
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => intval($total_pages),
            'items' => $items
        ]
    ]);
}

function handleDownloadBackup($conn, $backupDir, $id) {
    $id = intval($id);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => '无效的ID']);
        return;
    }

    $stmt = $conn->prepare("SELECT filename, display_name FROM backup_records WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => '备份记录不存在']);
        return;
    }

    $filepath = $backupDir . '/' . $row['filename'];
    if (!file_exists($filepath)) {
        echo json_encode(['success' => false, 'message' => '备份文件不存在']);
        return;
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . rawurlencode($row['display_name']) . '.sql"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache, must-revalidate');
    readfile($filepath);
    exit;
}

function handleDeleteBackup($conn, $backupDir, $input) {
    $id = intval($input['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => '无效的ID']);
        return;
    }

    $stmt = $conn->prepare("SELECT filename FROM backup_records WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => '备份记录不存在']);
        return;
    }

    $filepath = $backupDir . '/' . $row['filename'];
    if (file_exists($filepath)) {
        unlink($filepath);
    }

    $stmt = $conn->prepare("DELETE FROM backup_records WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => '删除成功']);
}

function handleRenameBackup($conn, $input) {
    $id = intval($input['id'] ?? 0);
    $newName = trim($input['display_name'] ?? '');

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => '无效的ID']);
        return;
    }
    if ($newName === '') {
        echo json_encode(['success' => false, 'message' => '名称不能为空']);
        return;
    }

    $stmt = $conn->prepare("UPDATE backup_records SET display_name = ? WHERE id = ?");
    $safeName = sanitize($newName);
    $stmt->bind_param('si', $safeName, $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => '重命名成功']);
}

function handleUpdateRemark($conn, $input) {
    $id = intval($input['id'] ?? 0);
    $remark = $input['remark'] ?? '';

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => '无效的ID']);
        return;
    }

    $stmt = $conn->prepare("UPDATE backup_records SET remark = ? WHERE id = ?");
    $safeRemark = sanitize($remark);
    $stmt->bind_param('si', $safeRemark, $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => '备注更新成功']);
}

function handleUploadBackup($conn, $backupDir) {
    if (!isset($_FILES['sql_file'])) {
        echo json_encode(['success' => false, 'message' => '请选择上传文件']);
        return;
    }

    $file = $_FILES['sql_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => '上传错误代码: ' . $file['error']]);
        return;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'sql') {
        echo json_encode(['success' => false, 'message' => '仅支持 .sql 格式文件']);
        return;
    }

    $timestamp = date('Ymd_His');
    $filename = 'upload_' . $timestamp . '_' . bin2hex(random_bytes(4)) . '.sql';
    $filepath = $backupDir . '/' . $filename;
    $displayName = pathinfo($file['name'], PATHINFO_FILENAME);

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode(['success' => false, 'message' => '文件保存失败']);
        return;
    }

    $fileSize = filesize($filepath);
    $remark = '外部上传 - ' . $file['name'];

    $stmt = $conn->prepare("INSERT INTO backup_records (filename, display_name, file_size, backup_type, status, remark) VALUES (?, ?, ?, 'manual', 'success', ?)");
    $stmt->bind_param('ssis', $filename, $displayName, $fileSize, $remark);
    $stmt->execute();
    $recordId = $conn->insert_id;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => '上传成功',
        'data' => [
            'id' => $recordId,
            'display_name' => $displayName,
            'file_size_formatted' => formatBytes($fileSize)
        ]
    ]);
}

function handleRestoreBackup($conn, $backupDir, $input) {
    $id = intval($input['id'] ?? 0);
    $confirm = $input['confirm_text'] ?? '';

    if ($confirm !== 'CONFIRM') {
        echo json_encode(['success' => false, 'message' => '请输入 CONFIRM 确认恢复']);
        return;
    }
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => '无效的备份ID']);
        return;
    }

    $stmt = $conn->prepare("SELECT filename FROM backup_records WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => '备份记录不存在']);
        return;
    }

    $filepath = $backupDir . '/' . $row['filename'];
    if (!file_exists($filepath)) {
        echo json_encode(['success' => false, 'message' => '备份文件不存在']);
        return;
    }

    $sqlContent = file_get_contents($filepath);
    if ($sqlContent === false) {
        echo json_encode(['success' => false, 'message' => '读取备份文件失败']);
        return;
    }

    $logs = [];
    try {
        $logs[] = '[INFO] ========== 开始恢复流程 ==========';
        $logs[] = '[INFO] 步骤1: 恢复前自动备份当前数据...';

        $preBackupId = createAutoPreRestoreBackup($conn, $backupDir, $logs);
        if ($preBackupId === false) {
            throw new Exception('恢复前自动备份失败');
        }
        $logs[] = '[INFO] 自动备份完成，备份ID: ' . $preBackupId;
        $logs[] = '[INFO] 步骤2: 开始事务中执行恢复SQL...';

        $conn->begin_transaction();

        $statements = parseSQLStatements($sqlContent);
        $logs[] = '[INFO] 解析到 ' . count($statements) . ' 条SQL语句';

        $execCount = 0;
        foreach ($statements as $idx => $sql) {
            $sql = trim($sql);
            if ($sql === '' || strpos($sql, '--') === 0) {
                continue;
            }
            try {
                if (!$conn->query($sql)) {
                    throw new Exception('SQL执行错误 (语句#' . ($idx + 1) . '): ' . $conn->error);
                }
                $execCount++;
                if ($execCount % 50 === 0) {
                    $logs[] = '[INFO] 已执行 ' . $execCount . ' 条语句...';
                }
            } catch (Exception $e) {
                throw $e;
            }
        }

        $conn->commit();
        $logs[] = '[INFO] 共执行 ' . $execCount . ' 条SQL语句';
        $logs[] = '[SUCCESS] ========== 恢复完成 ==========';

        echo json_encode([
            'success' => true,
            'message' => '恢复成功',
            'logs' => $logs,
            'pre_backup_id' => $preBackupId
        ]);
    } catch (Exception $e) {
        if ($conn->errno !== 0) {
            $conn->rollback();
            $logs[] = '[WARN] 事务已回滚';
        }
        $logs[] = '[ERROR] ' . $e->getMessage();
        $logs[] = '[ERROR] ========== 恢复失败 ==========';

        echo json_encode([
            'success' => false,
            'message' => '恢复失败: ' . $e->getMessage(),
            'logs' => $logs,
            'error_detail' => $e->getMessage()
        ]);
    }
}

function handleRestoreStream($conn, $backupDir, $input) {
    $id = intval($input['id'] ?? 0);
    $confirm = $input['confirm_text'] ?? '';

    if ($confirm !== 'CONFIRM') {
        echo json_encode(['success' => false, 'message' => '请输入 CONFIRM 确认恢复']);
        return;
    }
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => '无效的备份ID']);
        return;
    }

    $stmt = $conn->prepare("SELECT filename FROM backup_records WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => '备份记录不存在']);
        return;
    }

    $filepath = $backupDir . '/' . $row['filename'];
    if (!file_exists($filepath)) {
        echo json_encode(['success' => false, 'message' => '备份文件不存在']);
        return;
    }

    @ini_set('zlib.output_compression', 0);
    @ini_set('implicit_flush', 1);
    while (@ob_end_flush());
    @ob_implicit_flush(true);
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');

    $sendLog = function($level, $msg) {
        $data = json_encode(['level' => $level, 'message' => $msg, 'timestamp' => date('Y-m-d H:i:s')], JSON_UNESCAPED_UNICODE);
        echo "data: " . $data . "\n\n";
        flush();
    };

    try {
        $sendLog('info', '========== 开始恢复流程 ==========');
        $sendLog('info', '步骤1: 恢复前自动备份当前数据...');

        $preBackupId = createAutoPreRestoreBackupStream($conn, $backupDir, $sendLog);
        if ($preBackupId === false) {
            throw new Exception('恢复前自动备份失败');
        }
        $sendLog('info', '自动备份完成，备份ID: ' . $preBackupId);
        $sendLog('info', '步骤2: 开始事务中执行恢复SQL...');

        $sqlContent = file_get_contents($filepath);
        if ($sqlContent === false) {
            throw new Exception('读取备份文件失败');
        }

        $conn->begin_transaction();

        $statements = parseSQLStatements($sqlContent);
        $sendLog('info', '解析到 ' . count($statements) . ' 条SQL语句');

        $execCount = 0;
        foreach ($statements as $idx => $sql) {
            $sql = trim($sql);
            if ($sql === '' || strpos($sql, '--') === 0) {
                continue;
            }
            try {
                if (!$conn->query($sql)) {
                    throw new Exception('SQL执行错误 (语句#' . ($idx + 1) . '): ' . $conn->error);
                }
                $execCount++;
                if ($execCount % 20 === 0) {
                    $sendLog('info', '已执行 ' . $execCount . ' 条语句...');
                }
            } catch (Exception $e) {
                throw $e;
            }
        }

        $conn->commit();
        $sendLog('info', '共执行 ' . $execCount . ' 条SQL语句');
        $sendLog('success', '========== 恢复完成 ==========');
        echo "event: done\ndata: " . json_encode(['success' => true, 'pre_backup_id' => $preBackupId], JSON_UNESCAPED_UNICODE) . "\n\n";
        flush();
    } catch (Exception $e) {
        if ($conn->errno !== 0) {
            $conn->rollback();
            $sendLog('warn', '事务已回滚');
        }
        $sendLog('error', $e->getMessage());
        $sendLog('error', '========== 恢复失败 ==========');
        echo "event: done\ndata: " . json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE) . "\n\n";
        flush();
    }
    exit;
}

function createAutoPreRestoreBackup($conn, $backupDir, &$logs) {
    try {
        $timestamp = date('Ymd_His');
        $filename = 'pre_restore_' . $timestamp . '_' . bin2hex(random_bytes(4)) . '.sql';
        $filepath = $backupDir . '/' . $filename;
        $displayName = '恢复前自动备份_' . date('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO backup_records (filename, display_name, file_size, backup_type, status) VALUES (?, ?, 0, 'auto_pre_restore', 'processing')");
        $stmt->bind_param('ss', $filename, $displayName);
        $stmt->execute();
        $recordId = $conn->insert_id;
        $stmt->close();

        $sqlContent = generateBackupSQL($conn, $logs);
        if ($sqlContent === false) {
            throw new Exception('生成备份SQL失败');
        }

        $writeResult = file_put_contents($filepath, $sqlContent);
        if ($writeResult === false) {
            throw new Exception('写入备份文件失败');
        }

        $fileSize = filesize($filepath);

        $stmt = $conn->prepare("UPDATE backup_records SET file_size = ?, status = 'success' WHERE id = ?");
        $stmt->bind_param('ii', $fileSize, $recordId);
        $stmt->execute();
        $stmt->close();

        return $recordId;
    } catch (Exception $e) {
        if (isset($recordId)) {
            $stmt = $conn->prepare("UPDATE backup_records SET status = 'failed', error_log = ? WHERE id = ?");
            $err = $e->getMessage();
            $stmt->bind_param('si', $err, $recordId);
            $stmt->execute();
            $stmt->close();
        }
        return false;
    }
}

function createAutoPreRestoreBackupStream($conn, $backupDir, $sendLog) {
    try {
        $timestamp = date('Ymd_His');
        $filename = 'pre_restore_' . $timestamp . '_' . bin2hex(random_bytes(4)) . '.sql';
        $filepath = $backupDir . '/' . $filename;
        $displayName = '恢复前自动备份_' . date('Y-m-d H:i:s');

        $stmt = $conn->prepare("INSERT INTO backup_records (filename, display_name, file_size, backup_type, status) VALUES (?, ?, 0, 'auto_pre_restore', 'processing')");
        $stmt->bind_param('ss', $filename, $displayName);
        $stmt->execute();
        $recordId = $conn->insert_id;
        $stmt->close();

        $fakeLogs = [];
        $sqlContent = generateBackupSQL($conn, $fakeLogs);
        foreach ($fakeLogs as $log) {
            if (strpos($log, '[INFO]') === 0) {
                $sendLog('info', '  ' . substr($log, 6));
            }
        }
        if ($sqlContent === false) {
            throw new Exception('生成备份SQL失败');
        }

        $writeResult = file_put_contents($filepath, $sqlContent);
        if ($writeResult === false) {
            throw new Exception('写入备份文件失败');
        }

        $fileSize = filesize($filepath);

        $stmt = $conn->prepare("UPDATE backup_records SET file_size = ?, status = 'success' WHERE id = ?");
        $stmt->bind_param('ii', $fileSize, $recordId);
        $stmt->execute();
        $stmt->close();

        return $recordId;
    } catch (Exception $e) {
        if (isset($recordId)) {
            $stmt = $conn->prepare("UPDATE backup_records SET status = 'failed', error_log = ? WHERE id = ?");
            $err = $e->getMessage();
            $stmt->bind_param('si', $err, $recordId);
            $stmt->execute();
            $stmt->close();
        }
        return false;
    }
}

function parseSQLStatements($sql) {
    $statements = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    $i = 0;
    $len = strlen($sql);

    while ($i < $len) {
        $char = $sql[$i];
        $nextChar = $i + 1 < $len ? $sql[$i + 1] : '';

        if (!$inString) {
            if ($char === '-' && $nextChar === '-') {
                $endOfLine = strpos($sql, "\n", $i);
                if ($endOfLine === false) {
                    $i = $len;
                } else {
                    $i = $endOfLine + 1;
                }
                continue;
            }
            if ($char === '/' && $nextChar === '*') {
                $endComment = strpos($sql, '*/', $i + 2);
                if ($endComment === false) {
                    $i = $len;
                } else {
                    $i = $endComment + 2;
                }
                continue;
            }
        }

        if (($char === "'" || $char === '"') && ($i === 0 || $sql[$i - 1] !== '\\')) {
            if (!$inString) {
                $inString = true;
                $stringChar = $char;
            } elseif ($char === $stringChar) {
                $inString = false;
            }
        }

        if ($char === ';' && !$inString) {
            $current .= $char;
            $trimmed = trim($current);
            if ($trimmed !== '' && $trimmed !== ';') {
                $statements[] = $trimmed;
            }
            $current = '';
        } else {
            $current .= $char;
        }
        $i++;
    }

    $trimmed = trim($current);
    if ($trimmed !== '' && $trimmed !== ';') {
        $statements[] = $trimmed;
    }

    return $statements;
}
