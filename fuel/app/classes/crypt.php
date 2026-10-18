<?php
/**
 * Keep historical FuelPHP ciphertext readable with phpseclib 3.
 *
 * phpseclib 2 normalized arbitrary AES key/IV lengths; version 3 requires exact
 * lengths. Mirror only that legacy input normalization, leaving authentication
 * and decryption to Fuel's implementation and the maintained crypto library.
 * Reference: phpseclib 2 Crypt/Base.php::_clearBuffers and AES::setKeyLength.
 * FuelPHP and phpseclib are MIT licensed; see NOTICE and SECURE_INSTALL.md.
 */
class Crypt extends \Fuel\Core\Crypt
{
    protected $legacy_keylength = false;

    protected function legacy_decode($value, $key = false, $keylength = false)
    {
        if (empty($this->config['legacy']['crypto_key'])) {
            return parent::legacy_decode($value, $key, $keylength);
        }
        if (!$key) {
            $key = static::safe_b64decode($this->config['legacy']['crypto_key']);
            $keylength = 128;
        }
        // An explicitly selected length persisted in phpseclib 2, including
        // the implicit 128-bit selection for Fuel's configured default key.
        if ($keylength) {
            $this->legacy_keylength = $keylength <= 128 ? 16 : ($keylength <= 192 ? 24 : 32);
        }
        $bytes = $this->legacy_keylength ?: (strlen($key) <= 16 ? 16 : (strlen($key) <= 24 ? 24 : 32));
        $key = str_pad(substr($key, 0, $bytes), $bytes, "\0");
        $iv = static::safe_b64decode($this->config['legacy']['crypto_iv']);
        $this->config['legacy']['crypto_iv'] = static::safe_b64encode(str_pad(substr($iv, 0, 16), 16, "\0"));
        // phpseclib 3 disallows changing a selected length while an old key is
        // present. A fresh CBC object also starts each message at its own IV.
        $this->legacy_crypter = new \phpseclib3\Crypt\AES('cbc');
        $this->legacy_crypter->enableContinuousBuffer();
        try {
            return parent::legacy_decode($value, $key, $bytes * 8);
        } catch (\phpseclib3\Exception\BadDecryptionException $e) {
            // phpseclib 2 returned false for invalid legacy padding.
            return false;
        } catch (\LengthException $e) {
            // Malformed CBC ciphertext cannot be a complete AES block.
            return false;
        }
    }
}
