<?php
require __DIR__.'/smoke.php';

function check($ok, $message)
{
    if (!$ok) {
        throw new RuntimeException('FAIL: '.$message);
    }
    echo 'PASS: '.$message."\n";
}

$fixture = json_decode(file_get_contents(__DIR__.'/fixtures/crypt-1.8.json'), true, 512, JSON_THROW_ON_ERROR);
$key = $fixture['sodium_key'];
$plaintext = base64_decode($fixture['plaintext_base64'], true);
$crypt = Crypt::forge('compatibility', array('legacy' => $fixture['legacy_config']));
check($crypt->decode($fixture['sodium_ciphertext'], $key) === $plaintext, 'FuelPHP 1.8 sodium ciphertext decrypts unchanged.');
check($crypt->decode($fixture['legacy_ciphertext']) === $fixture['legacy_plaintext'], 'FuelPHP legacy AES/HMAC fixture decrypts through phpseclib 3.');

foreach (array('', '日本語', "binary\0\xff", str_repeat('large payload ', 16000)) as $value) {
    $encoded = $crypt->encode($value, $key);
    check(strncmp($encoded, 'S:', 2) === 0 && $crypt->decode($encoded, $key) === $value, 'Sodium round trip, '.strlen($value).' bytes.');
}
check($crypt->decode($crypt->encode('default key')) === 'default key', 'Application default key round trip.');
check($crypt->encode($plaintext, $key) !== $crypt->encode($plaintext, $key), 'Fresh nonce/salt produce distinct ciphertext.');
check($crypt->decode($fixture['sodium_ciphertext'], str_repeat('02', 32)) === false, 'Wrong sodium key is rejected.');

// Corrupt authenticated bytes without making the encoding invalid.
$raw = ParagonIE\Fuel\Base64UrlSafe::decode(substr($fixture['sodium_ciphertext'], 2));
$raw[60] = chr(ord($raw[60]) ^ 1);
check($crypt->decode('S:'.ParagonIE\Fuel\Base64UrlSafe::encode($raw), $key) === false, 'Tampered sodium ciphertext is rejected.');
$legacy = $fixture['legacy_ciphertext'];
$legacy[5] = $legacy[5] === 'A' ? 'B' : 'A';
check($crypt->decode($legacy) === false, 'Tampered legacy AES ciphertext is rejected before decryption.');
$wrongConfig = $fixture['legacy_config'];
$wrongConfig['crypto_hmac'] = rtrim(strtr(base64_encode(str_repeat('x', 18)), '+/', '-_'), '=');
$wrong = Crypt::forge('wrong-legacy-key', array('legacy' => $wrongConfig));
check($wrong->decode($fixture['legacy_ciphertext']) === false, 'Wrong legacy authentication key is rejected.');
$rejected = false;
try {
    $crypt->decode('S:'.ParagonIE\Fuel\Base64UrlSafe::encode('short'), $key);
} catch (FuelException $e) {
    $rejected = true;
}
check($rejected, 'Truncated sodium message is rejected.');

// Independent OpenSSL fixtures exercise phpseclib 2's historical truncation /
// zero padding without installing the retired phpseclib 2 dependency.
function legacyFixture($plaintext, $key, $bytes, $iv, $hmac)
{
    $raw = openssl_encrypt($plaintext, 'aes-'.($bytes * 8).'-cbc',
        str_pad(substr($key, 0, $bytes), $bytes, "\0"), OPENSSL_RAW_DATA,
        str_pad(substr($iv, 0, 16), 16, "\0"));
    $tag = rtrim(strtr(base64_encode(hash_hmac('sha256', $raw, $hmac, true)), '+/', '-_'), '=');
    return rtrim(strtr(base64_encode($raw.$tag), '+/', '-_'), '=');
}
$decode64 = function ($value) { return base64_decode(strtr($value, '-_', '+/')); };
$iv = $decode64($fixture['legacy_config']['crypto_iv']);
$hmac = $decode64($fixture['legacy_config']['crypto_hmac']);
foreach (array(1 => 16, 16 => 16, 18 => 24, 24 => 24, 27 => 32, 32 => 32, 40 => 32) as $length => $bytes) {
    $rawKey = str_repeat('k', $length);
    $reader = Crypt::forge('legacy-length-'.$length, array('legacy' => $fixture['legacy_config']));
    $encoded = legacyFixture($plaintext, $rawKey, $bytes, $iv, $hmac);
    check($reader->legacy_decode($encoded, $rawKey) === $plaintext, 'Legacy custom '.$length.'-byte key preserves historical AES length.');
}
$reader = Crypt::forge('legacy-sequence', array('legacy' => $fixture['legacy_config']));
check($reader->decode($fixture['legacy_ciphertext']) === $fixture['legacy_plaintext'], 'Legacy default key selects 128 bits.');
foreach (array(array(false,16), array(192,24), array(false,24), array(256,32), array(false,32), array(128,16)) as $step) {
    $rawKey = str_repeat('q', 40);
    $encoded = legacyFixture($plaintext, $rawKey, $step[1], $iv, $hmac);
    check($reader->legacy_decode($encoded, $rawKey, $step[0]) === $plaintext, 'Repeated legacy decode preserves selected '.($step[1] * 8).'-bit length.');
}
$short = $fixture['legacy_config'];
$short['crypto_iv'] = base64_encode('iv');
$reader = Crypt::forge('short-legacy-iv', array('legacy' => $short));
$encoded = legacyFixture($plaintext, 'custom', 16, 'iv', $hmac);
check($reader->legacy_decode($encoded, 'custom') === $plaintext, 'Short legacy IV is zero padded as before.');
check($reader->legacy_decode('broken') === false, 'Truncated legacy message fails authentication.');
check($reader->legacy_decode($encoded, str_repeat('z', 16)) === false, 'Wrong legacy AES key returns false.');
$raw = 'truncated';
$tag = rtrim(strtr(base64_encode(hash_hmac('sha256', $raw, $hmac, true)), '+/', '-_'), '=');
$malformed = rtrim(strtr(base64_encode($raw.$tag), '+/', '-_'), '=');
check($reader->legacy_decode($malformed, 'custom') === false, 'Authenticated legacy ciphertext with an invalid block length returns false.');
