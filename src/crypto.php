<?php

use Random\RandomException;

require_once __DIR__ . '/db.php';

//Search and gets the Key
function getEncryptionKey(): string
{
    $hexKey = $_ENV['ENCRYPTION_KEY'] ?? '';    //Gets hex-String key from .env

    if (strlen($hexKey) !== 64) {
        throw new Exception("ENCRYPTION_KEY must be 64 hex chars");
    }

    $key = hex2bin($hexKey);    //Transform hex to binary

    if ($key === false || strlen($key) !== 32) {
        throw new Exception("Invalid ENCRYPTION_KEY");
    }

    return $key;    //Gives the key back (Plain text)
}

/**
 * @param string $plaintext is going to be encrypted
 * @return array of parameter that was encrypted and helpers
 * @throws RandomException
 * @throws Exception
 */
function encryptField(string $plaintext): array
{
    $key = getEncryptionKey();
    $iv = random_bytes(12); // GCM Galois Counter Mode: AES-256 - Generates pseudo-random bytes
    $tag = '';

    $ciphertext = openssl_encrypt(  //Encrypts the plain text
        $plaintext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($ciphertext === false) {
        throw new Exception("Encryption failed");
    }

    return [        //Returns the encryption helpers and actual encrypted field
        'encrypted' => base64_encode($ciphertext),
        'iv' => base64_encode($iv),
        'tag' => base64_encode($tag),
    ];
}

/**
 * @param string $encryptedBase64 to de decrypted
 * @param string $ivBase64 initialization vector helps decryption
 * @param string $tagBase64 authentication tag helps decryption
 * @return string plain text that was decrypted
 * @throws Exception
 */
function decryptField(string $encryptedBase64, string $ivBase64, string $tagBase64): string
{
    $key = getEncryptionKey();  //Gets the encryption jey from .env file

    //Decode all base64
    $ciphertext = base64_decode($encryptedBase64, true);
    $iv = base64_decode($ivBase64, true);
    $tag = base64_decode($tagBase64, true);

    //Checks all in order
    if ($ciphertext === false || $iv === false || $tag === false) {
        throw new Exception("Invalid encrypted payload");
    }
    //Decrypts the text
    $plaintext = openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($plaintext === false) {
        throw new Exception("Decryption failed");
    }

    return $plaintext; //Returns decrypted plaintext
}