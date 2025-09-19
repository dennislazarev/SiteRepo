<?php
// app/Core/Crypto.php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;

/**
 * Класс для криптографических операций, использующий APP_KEY из .env
 */
class Crypto
{
    private static ?string $key = null;

    /**
     * Получает и кэширует ключ из .env
     * @return string
     * @throws InvalidArgumentException Если ключ не задан или некорректен
     */
    private static function getKey(): string
    {
        if (self::$key === null) {
            $appKey = $_ENV['APP_KEY'] ?? null;
            if (!$appKey) {
                throw new InvalidArgumentException("APP_KEY не найден в .env");
            }
            
            self::$key = base64_decode($appKey);
            if (self::$key === false) {
                throw new InvalidArgumentException("APP_KEY не является корректной base64 строкой");
            }
            
            // Проверка длины ключа для AES-256 (32 байта)
            if (strlen(self::$key) !== 32) {
                 // Можно попробовать pad или throw, зависит от требований
                 // throw new InvalidArgumentException("APP_KEY должен быть 32 байта для AES-256 после декодирования base64");
                 error_log("Предупреждение: APP_KEY не 32 байта. Длина: " . strlen(self::$key));
            }
        }
        return self::$key;
    }

    /**
     * Шифрует данные
     * @param string $data Данные для шифрования
     * @return string|false Зашифрованные данные в формате base64 (iv + encrypted_data) или false в случае ошибки
     */
    public static function encrypt(string $data)
    {
        try {
            $key = self::getKey();
            $cipher = "AES-256-CBC";
            $ivLength = openssl_cipher_iv_length($cipher);
            if ($ivLength === false) {
                throw new \RuntimeException("Не удалось получить длину IV для шифра $cipher");
            }
            $iv = openssl_random_pseudo_bytes($ivLength);
            $encrypted = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);
            
            if ($encrypted === false) {
                error_log("Ошибка шифрования данных");
                return false;
            }
            
            return base64_encode($iv . $encrypted);
        } catch (\Exception $e) {
            error_log("Crypto::encrypt ошибка: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Дешифрует данные
     * @param string $data Зашифрованные данные в формате base64 (iv + encrypted_data)
     * @return string|false Расшифрованные данные или false в случае ошибки
     */
    public static function decrypt(string $data)
    {
        try {
            $key = self::getKey();
            $data = base64_decode($data);
            if ($data === false) {
                error_log("Ошибка декодирования base64 в Crypto::decrypt");
                return false;
            }
            
            $cipher = "AES-256-CBC";
            $ivLength = openssl_cipher_iv_length($cipher);
            if ($ivLength === false) {
                throw new \RuntimeException("Не удалось получить длину IV для шифра $cipher");
            }
            
            if (strlen($data) < $ivLength) {
                error_log("Зашифрованные данные слишком короткие");
                return false;
            }
            
            $iv = substr($data, 0, $ivLength);
            $encrypted = substr($data, $ivLength);
            
            $decrypted = openssl_decrypt($encrypted, $cipher, $key, OPENSSL_RAW_DATA, $iv);
            
            if ($decrypted === false) {
                error_log("Ошибка дешифрования данных. Возможно, неверный ключ или поврежденные данные.");
                return false;
            }
            
            return $decrypted;
        } catch (\Exception $e) {
            error_log("Crypto::decrypt ошибка: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Хэширует данные с солью, используя APP_KEY как часть соли
     * Полезно для хэширования данных, которые не нужно дешифровать, но нужно проверять
     * @param string $data Данные для хэширования
     * @param string $salt Дополнительная соль
     * @return string|false Хэш или false в случае ошибки
     */
    public static function hash(string $data, string $salt = '')
    {
        try {
            $key = self::getKey();
            // Используем часть ключа как соль для hmac
            $keySalt = substr($key, 0, 16); // Возьмем первые 16 байт ключа
            return hash_hmac('sha256', $data . $salt, $keySalt);
        } catch (\Exception $e) {
            error_log("Crypto::hash ошибка: " . $e->getMessage());
            return false;
        }
    }
}
?>