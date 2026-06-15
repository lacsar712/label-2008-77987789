<?php

class QueryBuilder
{
    private $conn;
    private $table;
    private $select = ['*'];
    private $wheres = [];
    private $params = [];
    private $types = '';
    private $orderBy = [];
    private $limit = null;
    private $offset = null;
    private $joins = [];

    public function __construct(mysqli $conn, string $table)
    {
        $this->conn = $conn;
        $this->table = $table;
    }

    public function select(array $columns): self
    {
        $this->select = $columns;
        return $this;
    }

    public function where(string $column, string $operator, $value, string $type = 's'): self
    {
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'paramType' => $type
        ];
        return $this;
    }

    public function whereLike(string $column, string $value, bool $wrapWildcards = true): self
    {
        $this->wheres[] = [
            'type' => 'like',
            'column' => $column,
            'value' => $wrapWildcards ? '%' . $value . '%' : $value,
            'paramType' => 's'
        ];
        return $this;
    }

    public function whereIn(string $column, array $values, string $type = 's'): self
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'paramType' => $type
        ];
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column
        ];
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'not_null',
            'column' => $column
        ];
        return $this;
    }

    public function orderBy(string $column, string $direction = 'DESC'): self
    {
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $this->orderBy[] = "`$column` $direction";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function paginate(int $page, int $perPage): self
    {
        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;
        return $this;
    }

    private function buildWhere(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $clauses = [];
        $this->params = [];
        $this->types = '';

        foreach ($this->wheres as $where) {
            switch ($where['type']) {
                case 'basic':
                    $clauses[] = "`{$where['column']}` {$where['operator']} ?";
                    $this->params[] = $where['value'];
                    $this->types .= $where['paramType'];
                    break;
                case 'like':
                    $clauses[] = "`{$where['column']}` LIKE ?";
                    $this->params[] = $where['value'];
                    $this->types .= 's';
                    break;
                case 'in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $clauses[] = "`{$where['column']}` IN ($placeholders)";
                    foreach ($where['values'] as $val) {
                        $this->params[] = $val;
                        $this->types .= $where['paramType'];
                    }
                    break;
                case 'null':
                    $clauses[] = "`{$where['column']}` IS NULL";
                    break;
                case 'not_null':
                    $clauses[] = "`{$where['column']}` IS NOT NULL";
                    break;
            }
        }

        return 'WHERE ' . implode(' AND ', $clauses);
    }

    private function buildOrderBy(): string
    {
        if (empty($this->orderBy)) {
            return '';
        }
        return 'ORDER BY ' . implode(', ', $this->orderBy);
    }

    private function buildLimitOffset(): string
    {
        $sql = '';
        if ($this->limit !== null) {
            $sql .= ' LIMIT ?';
            $this->params[] = $this->limit;
            $this->types .= 'i';
        }
        if ($this->offset !== null) {
            $sql .= ' OFFSET ?';
            $this->params[] = $this->offset;
            $this->types .= 'i';
        }
        return $sql;
    }

    public function buildSelectSql(): string
    {
        $select = implode(', ', array_map(fn($col) => $col === '*' ? '*' : "`$col`", $this->select));
        $sql = "SELECT $select FROM `{$this->table}`";
        $sql .= ' ' . $this->buildWhere();
        $sql .= ' ' . $this->buildOrderBy();
        $sql .= $this->buildLimitOffset();
        return trim($sql);
    }

    public function buildCountSql(): string
    {
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}`";
        $sql .= ' ' . $this->buildWhere();
        return trim($sql);
    }

    public function execute(): mysqli_result
    {
        $sql = $this->buildSelectSql();
        $stmt = $this->conn->prepare($sql);

        if (!empty($this->types)) {
            $stmt->bind_param($this->types, ...$this->params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    public function count(): int
    {
        $limit = $this->limit;
        $offset = $this->offset;
        $this->limit = null;
        $this->offset = null;

        $sql = $this->buildCountSql();
        $stmt = $this->conn->prepare($sql);

        $typeLen = strlen($this->types);
        $limitLen = $limit !== null ? 1 : 0;
        $offsetLen = $offset !== null ? 1 : 0;
        $paramLen = $typeLen - $limitLen - $offsetLen;
        $actualTypes = substr($this->types, 0, $paramLen);
        $actualParams = array_slice($this->params, 0, $paramLen);

        if (!empty($actualTypes)) {
            $stmt->bind_param($actualTypes, ...$actualParams);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $total = (int) $result->fetch_assoc()['total'];
        $stmt->close();

        $this->limit = $limit;
        $this->offset = $offset;
        return $total;
    }

    public function fetchAll(): array
    {
        $result = $this->execute();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
        return $rows;
    }

    public function fetch(): ?array
    {
        $result = $this->execute();
        $row = $result->fetch_assoc();
        $result->free();
        return $row ?: null;
    }

    public function delete(): bool
    {
        $where = $this->buildWhere();
        if (empty($where)) {
            return false;
        }

        $sql = "DELETE FROM `{$this->table}` $where";
        $stmt = $this->conn->prepare($sql);

        $typeLen = strlen($this->types);
        $limitLen = $this->limit !== null ? 1 : 0;
        $offsetLen = $this->offset !== null ? 1 : 0;
        $paramLen = $typeLen - $limitLen - $offsetLen;
        $actualTypes = substr($this->types, 0, $paramLen);
        $actualParams = array_slice($this->params, 0, $paramLen);

        if (!empty($actualTypes)) {
            $stmt->bind_param($actualTypes, ...$actualParams);
        }

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function insert(array $data, array $types): int
    {
        $columns = implode(', ', array_map(fn($col) => "`$col`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `{$this->table}` ($columns) VALUES ($placeholders)";

        $stmt = $this->conn->prepare($sql);
        $typeString = implode('', $types);
        $values = array_values($data);
        $stmt->bind_param($typeString, ...$values);
        $stmt->execute();
        $id = (int) $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function update(array $data, array $types): bool
    {
        $where = $this->buildWhere();
        if (empty($where)) {
            return false;
        }

        $setClauses = [];
        $setParams = [];
        $setTypes = implode('', $types);

        $keys = array_keys($data);
        for ($i = 0; $i < count($keys); $i++) {
            $setClauses[] = "`{$keys[$i]}` = ?";
            $setParams[] = $data[$keys[$i]];
        }

        $typeLen = strlen($this->types);
        $limitLen = $this->limit !== null ? 1 : 0;
        $offsetLen = $this->offset !== null ? 1 : 0;
        $paramLen = $typeLen - $limitLen - $offsetLen;
        $whereTypes = substr($this->types, 0, $paramLen);
        $whereParams = array_slice($this->params, 0, $paramLen);

        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $setClauses) . " $where";

        $stmt = $this->conn->prepare($sql);
        $allTypes = $setTypes . $whereTypes;
        $allParams = array_merge($setParams, $whereParams);

        if (!empty($allTypes)) {
            $stmt->bind_param($allTypes, ...$allParams);
        }

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
