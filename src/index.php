<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$createResult = null;
$createError = null;

$getResult = null;
$getError = null;

$dbRows = [];
$dbError = null;
$showDatabase = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $taxId = trim($_POST['tax_id'] ?? '');

        if ($name === '' || $email === '' || $taxId === '') {
            $createError = 'Please fill in all fields.';
        } else {
            $postData = http_build_query([
                'name' => $name,
                'email' => $email,
                'tax_id' => $taxId,
            ]);

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => $postData,
                    'ignore_errors' => true,
                    'timeout' => 10,
                ]
            ]);

            $response = @file_get_contents('http://localhost/create_customer.php', false, $context);

            if ($response === false) {
                $createError = 'Could not reach create_customer.php';
            } else {
                $decoded = json_decode($response, true);

                if (is_array($decoded) && isset($decoded['error'])) {
                    $createError = $decoded['error'];
                } else {
                    $createResult = $decoded;
                }
            }
        }
    }

    if ($action === 'get') {
        $customerId = trim($_POST['customer_id'] ?? '');

        if ($customerId === '') {
            $getError = 'Please enter a customer ID.';
        } else {
            $url = 'http://localhost/get_customer.php?id=' . urlencode($customerId);
            $response = @file_get_contents($url);

            if ($response === false) {
                $getError = 'Could not reach get_customer.php';
            } else {
                $decoded = json_decode($response, true);

                if (is_array($decoded) && isset($decoded['error'])) {
                    $getError = $decoded['error'];
                } else {
                    $getResult = $decoded;
                }
            }
        }
    }

    if ($action === 'display_database') {
        $showDatabase = true;

        try {
            $pdo = getDbConnection();
            $stmt = $pdo->query("
                SELECT
                    id,
                    full_name,
                    email,
                    tax_id_encrypted,
                    tax_id_iv,
                    tax_id_tag,
                    created_at
                FROM customers
                ORDER BY id ASC
            ");
            $dbRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $dbError = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encryption Lab</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #1f2937;
        }

        .page {
            max-width: 1300px;
            margin: 40px auto;
            padding: 0 20px 40px;
        }

        .title {
            margin-bottom: 24px;
        }

        .title h1 {
            margin: 0 0 8px;
            font-size: 32px;
        }

        .title p {
            margin: 0;
            color: #6b7280;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .card {
            background: #ffffff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .card h2 {
            margin-top: 0;
            margin-bottom: 18px;
            font-size: 22px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
        }

        button {
            border: none;
            background: #2563eb;
            color: white;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .message {
            margin-top: 18px;
            padding: 14px;
            border-radius: 10px;
        }

        .success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .result-box {
            margin-top: 18px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
        }

        .result-row {
            margin-bottom: 10px;
        }

        .result-row:last-child {
            margin-bottom: 0;
        }

        .result-label {
            font-weight: 700;
            display: inline-block;
            min-width: 110px;
        }

        .hint {
            margin-top: 10px;
            color: #6b7280;
            font-size: 14px;
        }

        .database-section {
            margin-top: 28px;
        }

        .database-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .database-header h2 {
            margin: 0;
        }

        .table-wrapper {
            margin-top: 18px;
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        th, td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        th {
            background: #f9fafb;
            font-size: 14px;
        }

        td {
            font-size: 14px;
            word-break: break-word;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .mono {
            font-family: Consolas, Monaco, monospace;
            font-size: 13px;
        }

        @media (max-width: 900px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="title">
        <h1>Customer Encryption Lab</h1>
        <p>Create customers on the left and retrieve decrypted customer data on the right.</p>
    </div>

    <div class="grid">
        <div class="card">
            <h2>Create Customer</h2>

            <form method="post">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        placeholder="Ana Lopez"
                        value="<?= h($_POST['name'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="ana@example.com"
                        value="<?= h($_POST['email'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="tax_id">Tax ID</label>
                    <input
                        type="text"
                        id="tax_id"
                        name="tax_id"
                        placeholder="AB123456789"
                        value="<?= h($_POST['tax_id'] ?? '') ?>"
                    >
                </div>

                <button type="submit">Create Customer</button>
            </form>

            <?php if ($createError !== null): ?>
                <div class="message error">
                    <?= h($createError) ?>
                </div>
            <?php endif; ?>

            <?php if (is_array($createResult)): ?>
                <div class="message success">
                    Customer created successfully.
                </div>
                <div class="result-box">
                    <div class="result-row">
                        <span class="result-label">Inserted ID:</span>
                        <span><?= h((string)($createResult['id'] ?? 'N/A')) ?></span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Message:</span>
                        <span><?= h((string)($createResult['message'] ?? 'Success')) ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Get Customer by ID</h2>

            <form method="post">
                <input type="hidden" name="action" value="get">

                <div class="form-group">
                    <label for="customer_id">Customer ID</label>
                    <input
                        type="number"
                        id="customer_id"
                        name="customer_id"
                        placeholder="1"
                        value="<?= h($_POST['customer_id'] ?? '') ?>"
                    >
                </div>

                <button type="submit">Load Customer</button>
            </form>

            <div class="hint">
                This panel reads the customer and shows the decrypted tax ID returned by the backend.
            </div>

            <?php if ($getError !== null): ?>
                <div class="message error">
                    <?= h($getError) ?>
                </div>
            <?php endif; ?>

            <?php if (is_array($getResult)): ?>
                <div class="message success">
                    Customer loaded successfully.
                </div>
                <div class="result-box">
                    <div class="result-row">
                        <span class="result-label">ID:</span>
                        <span><?= h((string)($getResult['id'] ?? '')) ?></span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Full Name:</span>
                        <span><?= h((string)($getResult['full_name'] ?? '')) ?></span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Email:</span>
                        <span><?= h((string)($getResult['email'] ?? '')) ?></span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Tax ID:</span>
                        <span><?= h((string)($getResult['tax_id'] ?? '')) ?></span>
                    </div>
                    <div class="result-row">
                        <span class="result-label">Created At:</span>
                        <span><?= h((string)($getResult['created_at'] ?? '')) ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card database-section">
        <div class="database-header">
            <div>
                <h2>Raw Database View</h2>
                <div class="hint">
                    This shows the values exactly as stored in the database, without decryption.
                </div>
            </div>

            <form method="post">
                <input type="hidden" name="action" value="display_database">
                <button type="submit">Display Database</button>
            </form>
        </div>

        <?php if ($dbError !== null): ?>
            <div class="message error">
                <?= h($dbError) ?>
            </div>
        <?php endif; ?>

        <?php if ($showDatabase && $dbError === null): ?>
            <?php if (count($dbRows) === 0): ?>
                <div class="message success">
                    No rows found in the database yet.
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Encrypted Tax ID</th>
                            <th>IV</th>
                            <th>Tag</th>
                            <th>Created At</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($dbRows as $row): ?>
                            <tr>
                                <td><?= h((string)$row['id']) ?></td>
                                <td><?= h((string)$row['full_name']) ?></td>
                                <td><?= h((string)$row['email']) ?></td>
                                <td class="mono"><?= h((string)$row['tax_id_encrypted']) ?></td>
                                <td class="mono"><?= h((string)$row['tax_id_iv']) ?></td>
                                <td class="mono"><?= h((string)$row['tax_id_tag']) ?></td>
                                <td><?= h((string)$row['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>