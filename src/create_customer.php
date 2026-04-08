<?php

require_once __DIR__ . '/crypto.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {    //Accepts only POST
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    //Gets required fields from request
    $name = $_POST['name'] ?? null;
    $email = $_POST['email'] ?? null;
    $taxId = $_POST['tax_id'] ?? null;

    if (!$name || !$email || !$taxId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    //Encrypts the given field
    $encryptedTaxId = encryptField($taxId);
    $encryptedEmail = encryptField($email);
    //connects to the DB
    $pdo = getDbConnection();

    //Prepares the query
    $stmt = $pdo->prepare("
        INSERT INTO customers (
            full_name,
            email,
            tax_id_encrypted,
            tax_id_iv,
            tax_id_tag
        ) VALUES (
            :full_name,
            :email,
            :tax_id_encrypted,
            :tax_id_iv,
            :tax_id_tag
        )
    ");
    //Executes the query
    $stmt->execute([
        ':full_name' => $name,
        ':email' => $encryptedEmail['encrypted'],
        ':tax_id_encrypted' => $encryptedTaxId['encrypted'],
        ':tax_id_iv' => $encryptedTaxId['iv'],
        ':tax_id_tag' => $encryptedTaxId['tag'],
    ]);

    echo json_encode([
        'message' => 'Customer created',
        'id' => $pdo->lastInsertId()
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}