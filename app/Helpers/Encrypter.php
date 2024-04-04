<?php

namespace App\Helpers;

class Encrypter
{
    public static function handle($value, string $type = 'encrypt', string $cypherMethod = 'AES-256-CBC')
    {
        if ($value == null) return null;
        $type = "openssl_$type";
        $iv = str_repeat("\0", openssl_cipher_iv_length($cypherMethod));
        return $type($value, $cypherMethod, env('CMAG_APP_KEY'), $options = 0, $iv);
    }
}
