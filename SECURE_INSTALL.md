# Secure dependency installation

Use PHP 8.2 or newer and a current Composer 2 executable:

```sh
composer install
composer test
php oil --version
cd public
php -S 127.0.0.1:8080
```

The bundled historical `composer.phar` predates current Composer and must not be used for this installation. PHP 8.2 and 8.5 have been checked with the committed lockfile. The smoke tests exercise the application bootstrap, bundled package loading, actual HTTP home/hello/404 responses, and cryptography. HTTP tests start and stop their own loopback PHP server. They require PHP's `proc_open` and HTTP stream wrapper; crypto reference checks use OpenSSL.

## Dependency decision

Fuel core 1.8.2 and the stable 1.9.0 release require phpseclib 2. Current Composer advisory blocking rejects that dependency line. The official [Fuel core development branch](https://github.com/fuel/core/blob/e05d6e6b04fcd158a288326404ab47578c348822/composer.json) already uses phpseclib 3 and the matching AES/Hash APIs. This project pins that upstream commit, rather than weakening Composer's security policy or substituting package metadata.

Core also requires composer/installers 2.3, while the stable Fuel companion packages constrain it to 1.x. Their official 1.9 development commits are therefore pinned together. The upload package remains at 2.0.6. The root minimum stability remains stable, with explicit development exceptions only for these Fuel packages. Commit `composer.lock` and use `composer install` to reproduce the complete dependency set. Updating upstream pins requires rerunning the regression suite and `composer audit --locked`.

The runtime floor is PHP 8.2, a supported PHP line at the time of this repair. The old PHP 5.4/7.3 claims describe the historical upstream distribution, not this locked dependency set. Database-backed ORM/auth workflows, SMTP delivery, and external parser engines need their own configured integration tests; bootstrap checks do not certify those integrations.

## Legacy ciphertext compatibility

New encryption continues to use Fuel's existing Sodium format and APIs. No new legacy encryption is introduced. Existing keys in `fuel/app/config/crypt.php` must be retained during upgrades. Never replace them with the public test fixture keys.

phpseclib 3 validates exact AES key and IV lengths. Fuel's own historical fixture contains 18-byte decoded key/IV material, which phpseclib 2 truncated or zero padded. The application `Crypt` overload applies that same normalization only to legacy decoding. It preserves the selected key length across calls, including Fuel's default 128-bit legacy selection. The parent implementation still verifies the original HMAC before AES decryption, using the original authentication key. Ciphertext, stored keys, and the new Sodium path are unchanged. Invalid legacy padding/block lengths and authentication failures return `false` as before.

Normalization behavior is derived from MIT-licensed phpseclib 2 [AES::setKeyLength](https://github.com/phpseclib/phpseclib/blob/2.0.55/phpseclib/Crypt/AES.php), [Rijndael::setKeyLength](https://github.com/phpseclib/phpseclib/blob/2.0.55/phpseclib/Crypt/Rijndael.php), and [Base::_clearBuffers](https://github.com/phpseclib/phpseclib/blob/2.0.55/phpseclib/Crypt/Base.php). Cryptographic operations use the installed phpseclib 3 library. No phpseclib 2 source is included or loaded by this project.

`tests/fixtures/crypt-1.8.json` includes the unchanged legacy fixture from [Fuel core 1.8.2's MIT-licensed tests](https://github.com/fuel/core/blob/1.8.2/tests/crypt.php). Its Sodium ciphertext was generated using the unmodified 1.8.2 Crypt source with only its namespace renamed for isolation; the source SHA-256 is recorded in the fixture. This generation needed no phpseclib 2 installation. All fixture keys are public test data. Additional legacy cases are independently encrypted with OpenSSL and authenticated with `hash_hmac` in the tests, including short/long keys and IVs, selected key lengths, tampering, truncation, and wrong keys.

The front controller also converts its nullable route lookup key to a string, preserving the historical empty-key lookup while avoiding [PHP 8.5's null array-key deprecation](https://www.php.net/manual/en/migration85.deprecated.php).
