<?php
header('Content-Type: application/json; charset=UTF-8');
require_once 'config.php';
ensurePrintTemplates();

$conn = getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id > 0) {
        $stmt = $conn->prepare("SELECT * FROM print_templates WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $template = $result->fetch_assoc();
        $stmt->close();
        if ($template) {
            if ($template['style_json']) {
                $template['style'] = json_decode($template['style_json'], true);
            } else {
                $template['style'] = new stdClass();
            }
            unset($template['style_json']);
            jsonResponse(['success' => true, 'data' => $template]);
        } else {
            jsonResponse(['success' => false, 'message' => '模板不存在'], 404);
        }
    } else {
        $result = $conn->query("SELECT * FROM print_templates ORDER BY is_default DESC, id ASC");
        $list = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['style_json']) {
                $row['style'] = json_decode($row['style_json'], true);
            } else {
                $row['style'] = new stdClass();
            }
            unset($row['style_json']);
            $list[] = $row;
        }
        jsonResponse(['success' => true, 'data' => $list]);
    }
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'create') {
        $name = sanitize($input['name'] ?? '');
        $header_text = sanitize($input['header_text'] ?? '');
        $footer_text = sanitize($input['footer_text'] ?? '');
        $logo_url = sanitize($input['logo_url'] ?? '');
        $style = $input['style'] ?? new stdClass();
        $is_default = isset($input['is_default']) ? intval($input['is_default']) : 0;
        $template_type = sanitize($input['template_type'] ?? 'minimal');

        if (!$name) {
            jsonResponse(['success' => false, 'message' => '模板名称不能为空']);
        }

        if ($is_default) {
            $conn->query("UPDATE print_templates SET is_default = 0");
        }

        $style_json = json_encode($style, JSON_UNESCAPED_UNICODE);
        $stmt = $conn->prepare("INSERT INTO print_templates (name, header_text, footer_text, logo_url, style_json, is_default, template_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssis", $name, $header_text, $footer_text, $logo_url, $style_json, $is_default, $template_type);
        $result = $stmt->execute();
        $new_id = $conn->insert_id;
        $stmt->close();

        if ($result) {
            jsonResponse(['success' => true, 'data' => ['id' => $new_id], 'message' => '创建成功']);
        } else {
            jsonResponse(['success' => false, 'message' => '创建失败']);
        }
    }

    if ($action === 'update') {
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['success' => false, 'message' => '无效的模板ID']);
        }

        $name = sanitize($input['name'] ?? '');
        $header_text = sanitize($input['header_text'] ?? '');
        $footer_text = sanitize($input['footer_text'] ?? '');
        $logo_url = sanitize($input['logo_url'] ?? '');
        $style = $input['style'] ?? new stdClass();
        $is_default = isset($input['is_default']) ? intval($input['is_default']) : 0;
        $template_type = sanitize($input['template_type'] ?? 'minimal');

        if (!$name) {
            jsonResponse(['success' => false, 'message' => '模板名称不能为空']);
        }

        if ($is_default) {
            $conn->query("UPDATE print_templates SET is_default = 0");
        }

        $style_json = json_encode($style, JSON_UNESCAPED_UNICODE);
        $stmt = $conn->prepare("UPDATE print_templates SET name = ?, header_text = ?, footer_text = ?, logo_url = ?, style_json = ?, is_default = ?, template_type = ? WHERE id = ?");
        $stmt->bind_param("sssssisi", $name, $header_text, $footer_text, $logo_url, $style_json, $is_default, $template_type, $id);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            jsonResponse(['success' => true, 'message' => '更新成功']);
        } else {
            jsonResponse(['success' => false, 'message' => '更新失败']);
        }
    }

    if ($action === 'delete') {
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['success' => false, 'message' => '无效的模板ID']);
        }

        $check = $conn->query("SELECT is_default FROM print_templates WHERE id = $id");
        $row = $check->fetch_assoc();
        if ($row && $row['is_default']) {
            jsonResponse(['success' => false, 'message' => '不能删除默认模板']);
        }

        $stmt = $conn->prepare("DELETE FROM print_templates WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            jsonResponse(['success' => true, 'message' => '删除成功']);
        } else {
            jsonResponse(['success' => false, 'message' => '删除失败']);
        }
    }

    if ($action === 'set_default') {
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) {
            jsonResponse(['success' => false, 'message' => '无效的模板ID']);
        }

        $conn->query("UPDATE print_templates SET is_default = 0");
        $stmt = $conn->prepare("UPDATE print_templates SET is_default = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            jsonResponse(['success' => true, 'message' => '设置成功']);
        } else {
            jsonResponse(['success' => false, 'message' => '设置失败']);
        }
    }

    jsonResponse(['success' => false, 'message' => '无效的操作']);
}

closeConnection($conn);
jsonResponse(['success' => false, 'message' => '无效的请求方法'], 405);
