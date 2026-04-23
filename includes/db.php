<?php
// includes/db.php - Fixed MySQLi functions (no named placeholders)

// Your existing connection
$conn = mysqli_connect("localhost", "root", "malika05", "src_system");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8mb4");

// =============================================
// HELPER FUNCTIONS using MySQLi
// =============================================

// Execute query with prepared statement
function db_query($sql, $params = []) {
    global $conn;
    
    if (empty($params)) {
        $result = mysqli_query($conn, $sql);
        return $result;
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        if (!empty($params)) {
            // Determine parameter types
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_double($param)) $types .= 'd';
                elseif (is_string($param)) $types .= 's';
                else $types .= 'b';
            }
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
    return false;
}

// Fetch all rows as associative array
function db_fetch_all($sql, $params = []) {
    $result = db_query($sql, $params);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
    return [];
}

// Fetch single row
function db_fetch_one($sql, $params = []) {
    $result = db_query($sql, $params);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

// Insert data into table
function db_insert($table, $data) {
    global $conn;
    
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        // Bind parameters
        $types = '';
        $values = [];
        foreach ($data as $value) {
            if (is_int($value)) $types .= 'i';
            elseif (is_double($value)) $types .= 'd';
            elseif (is_string($value)) $types .= 's';
            else $types .= 'b';
            $values[] = $value;
        }
        mysqli_stmt_bind_param($stmt, $types, ...$values);
        
        if (mysqli_stmt_execute($stmt)) {
            $insert_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $insert_id;
        }
        mysqli_stmt_close($stmt);
    }
    return false;
}

// Update data in table - FIXED VERSION (no named placeholders)
function db_update($table, $data, $where, $whereParams = []) {
    global $conn;
    
    // Build SET clause with ? placeholders
    $set = [];
    foreach ($data as $key => $value) {
        $set[] = "$key = ?";
    }
    $setClause = implode(', ', $set);
    $sql = "UPDATE $table SET $setClause WHERE $where";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        // Combine data values and where params
        $allParams = array_merge(array_values($data), $whereParams);
        
        if (!empty($allParams)) {
            $types = '';
            foreach ($allParams as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_double($param)) $types .= 'd';
                elseif (is_string($param)) $types .= 's';
                else $types .= 'b';
            }
            mysqli_stmt_bind_param($stmt, $types, ...$allParams);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected;
        }
        mysqli_stmt_close($stmt);
    }
    return false;
}

// Delete data from table
function db_delete($table, $where, $params = []) {
    global $conn;
    
    $sql = "DELETE FROM $table WHERE $where";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        if (!empty($params)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) $types .= 'i';
                elseif (is_double($param)) $types .= 'd';
                elseif (is_string($param)) $types .= 's';
                else $types .= 'b';
            }
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected;
        }
        mysqli_stmt_close($stmt);
    }
    return false;
}

// Get total count from table
function db_count($table, $where = '', $params = []) {
    $sql = "SELECT COUNT(*) as count FROM $table";
    if ($where) {
        $sql .= " WHERE $where";
    }
    $result = db_fetch_one($sql, $params);
    return $result ? $result['count'] : 0;
}

// Escape string for safe query
function db_escape($string) {
    global $conn;
    return mysqli_real_escape_string($conn, $string);
}

// Get single value
function db_get_value($sql, $params = []) {
    $result = db_fetch_one($sql, $params);
    if ($result) {
        return reset($result);
    }
    return null;
}
?>