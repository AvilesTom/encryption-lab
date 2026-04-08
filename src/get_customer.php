<?php

require_once __DIR__ . '/crypto.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {     //Only GET request
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    $id = $_GET['id'] ?? null;      //ID from wanted user/customer

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing id']);
        exit;
    }

    $pdo = getDbConnection();   //Connects to the DB

    //Prepares the query
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, tax_id_encrypted, tax_id_iv, tax_id_tag, created_at
        FROM customers
        WHERE id = :id
    ");

    //executes the query and gets the customer
    $stmt->execute([':id' => $id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    //No customer found
    if (!$customer) {
        http_response_code(404);
        echo json_encode(['error' => 'Customer not found']);
        exit;
    }

    //Uses decrypt function to decrypt data from Database
    $decryptedTaxId = decryptField(
        $customer['tax_id_encrypted'],
        $customer['tax_id_iv'],
        $customer['tax_id_tag']
    );

    //shows the customer
    echo json_encode([
        'id' => $customer['id'],
        'full_name' => $customer['full_name'],
        'email' => $customer['email'],
        'tax_id' => $decryptedTaxId,
        'created_at' => $customer['created_at']
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}