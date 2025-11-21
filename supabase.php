<?php

// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Supabase Configuration
define('SUPABASE_URL', $_ENV['SUPABASE_URL'] ?? '');
define('SUPABASE_KEY', $_ENV['SUPABASE_KEY'] ?? '');
define('SUPABASE_SERVICE_KEY', $_ENV['SUPABASE_SERVICE_KEY'] ?? '');

// Validate configuration
if (empty(SUPABASE_URL) || empty(SUPABASE_KEY)) {
    die("Error: Supabase configuration is missing. Please check your .env file.");
}

/**
 * Enhanced Select with RLS bypass capability
 * @param string $table Table name
 * @param array $filters Filters to apply
 * @param string $select Columns to select
 * @param string|null $order Order clause
 * @param int|null $limit Limit results
 * @param bool $bypassRLS Use SERVICE_KEY to bypass RLS (for login/auth)
 */
function supabaseSelect($table, $filters = [], $select = '*', $order = null, $limit = null, $bypassRLS = false) {
    $url = SUPABASE_URL . '/rest/v1/' . $table . '?select=' . urlencode($select);
    
    // Add filters
    foreach ($filters as $key => $value) {
        if (is_array($value)) {
            $operator = $value['operator'] ?? 'eq';
            $url .= '&' . $key . '=' . $operator . '.' . urlencode($value['value']);
        } else {
            $url .= '&' . $key . '=eq.' . urlencode($value);
        }
    }
    
    // Add order
    if ($order) {
        $url .= '&order=' . urlencode($order);
    }
    
    // Add limit
    if ($limit) {
        $url .= '&limit=' . $limit;
    }
    
    // Use SERVICE_KEY or public key based on bypass flag
    $authKey = ($bypassRLS && !empty(SUPABASE_SERVICE_KEY)) ? SUPABASE_SERVICE_KEY : SUPABASE_KEY;
    
    $headers = [
        'apikey: ' . $authKey,
        'Authorization: Bearer ' . $authKey,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Debug logging (remove in production)
    if ($httpCode >= 400) {
        error_log("=== SUPABASE SELECT ERROR ===");
        error_log("Table: " . $table);
        error_log("URL: " . $url);
        error_log("HTTP Code: " . $httpCode);
        error_log("Response: " . $response);
        error_log("cURL Error: " . $curlError);
        error_log("Using RLS Bypass: " . ($bypassRLS ? 'YES' : 'NO'));
        return [];
    }
    
    if ($curlError) {
        error_log("cURL Error in supabaseSelect: " . $curlError);
        return [];
    }
    
    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Insert data into Supabase
 * Uses SERVICE_KEY by default to bypass RLS for system operations
 */
function supabaseInsert($table, $data, $bypassRLS = true) {
    $url = SUPABASE_URL . '/rest/v1/' . $table;
    
    $authKey = ($bypassRLS && !empty(SUPABASE_SERVICE_KEY)) ? SUPABASE_SERVICE_KEY : SUPABASE_KEY;
    
    $headers = [
        'apikey: ' . $authKey,
        'Authorization: Bearer ' . $authKey,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 400) {
        error_log("Supabase Insert Error on table '$table': " . $response);
        return ['error' => true, 'message' => 'Insert failed', 'details' => $response];
    }
    
    return json_decode($response, true) ?: [];
}

/**
 * Update data in Supabase
 * Uses SERVICE_KEY by default to bypass RLS for system operations
 */
function supabaseUpdate($table, $filters, $data, $bypassRLS = true) {
    $url = SUPABASE_URL . '/rest/v1/' . $table;
    
    // Add filters
    $queryParams = [];
    foreach ($filters as $key => $value) {
        if (is_array($value)) {
            $operator = $value['operator'] ?? 'eq';
            $queryParams[] = $key . '=' . $operator . '.' . urlencode($value['value']);
        } else {
            $queryParams[] = $key . '=eq.' . urlencode($value);
        }
    }
    $url .= '?' . implode('&', $queryParams);
    
    $authKey = ($bypassRLS && !empty(SUPABASE_SERVICE_KEY)) ? SUPABASE_SERVICE_KEY : SUPABASE_KEY;
    
    $headers = [
        'apikey: ' . $authKey,
        'Authorization: Bearer ' . $authKey,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 400) {
        error_log("Supabase Update Error on table '$table': " . $response);
        return ['error' => true, 'message' => 'Update failed', 'details' => $response];
    }
    
    // Check for HTTP 204 No Content (means update succeeded but no data was returned)
    if ($httpCode === 204) {
        // Return a basic success indicator since Prefer: return=representation might be ignored
        return ['success' => true, 'http_code' => 204]; 
    }
    
    // If successful and content returned (due to Prefer header)
    $decoded = json_decode($response, true);
    
    // Since updates return an array containing the updated record(s), we return the first one
    return (!empty($decoded) && is_array($decoded)) ? $decoded[0] : ['success' => true];
}

/**
 * Delete data from Supabase
 * Uses SERVICE_KEY by default to bypass RLS for system operations
 */
function supabaseDelete($table, $filters, $bypassRLS = true) {
    $url = SUPABASE_URL . '/rest/v1/' . $table;
    
    // Add filters
    $queryParams = [];
    foreach ($filters as $key => $value) {
        if (is_array($value)) {
            $operator = $value['operator'] ?? 'eq';
            $queryParams[] = $key . '=' . $operator . '.' . urlencode($value['value']);
        } else {
            $queryParams[] = $key . '=eq.' . urlencode($value);
        }
    }
    $url .= '?' . implode('&', $queryParams);
    
    $authKey = ($bypassRLS && !empty(SUPABASE_SERVICE_KEY)) ? SUPABASE_SERVICE_KEY : SUPABASE_KEY;
    
    $headers = [
        'apikey: ' . $authKey,
        'Authorization: Bearer ' . $authKey,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode >= 200 && $httpCode < 300;
}

/**
 * Execute a Supabase RPC (stored procedure)
 */
function supabaseRPC($functionName, $params = []) {
    $url = SUPABASE_URL . '/rest/v1/rpc/' . $functionName;
    
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 400) {
        error_log("Supabase RPC Error: " . $response);
        return ['error' => true, 'message' => 'RPC call failed'];
    }
    
    return json_decode($response, true) ?: [];
}