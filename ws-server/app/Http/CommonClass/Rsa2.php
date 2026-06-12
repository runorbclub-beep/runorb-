<?php


namespace App\Http\CommonClass;


class Rsa2
{

    private static $PRIVATE_KEY = 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQComtalp99RmO/luhsMw57xQ1EsOlD4+R2l1luGOBmzyC2OC19EFFoAoBAhzrHw2bsMtBFOPQ6AZHdOzKVwbpLdr49CPv7M1gGcVEDP5l5xgpgDvv36g58glS39kwRSjG9nuaevqMr1FWn3CgNu2TSCJ2wiyPcYaThthtjTnp/fV0AB/fvMBSnWITd0+eVcXiuX1OEobbkALJ+cTOOYWON887S2ZN4vMZeSPUxL6SqIJPn/FNuney/5H7kqs33O4cR215tfFP7Em81iVHLlBQWZcQ+Q2LzH56S4AnEO3kCU6pMhc43Bw/rkn8/LNbI0iPhmGBf4/FaCxUu0QzqQ6hWBAgMBAAECggEAKoazcSEAcMJUb+sa+4MzycKi7LbgyYt78OI6P4ZS5UeuRc+UfcVsVhAJQZ2yv/8K8M9SNhusVrIAbb2sVMlu/b9UMO9WsS+hRF7z4fxHAfZghUFdKhycvEkkSnsUcgW967mmE+ZNGrgF/CtoZkMevV1YJwXtXRjdBLMoaWfvBxO+ZqAzsACdR7mBf/6RFXO/xXk/whNR4jpwXhEhMWPROELG89w848DHnPkTzAqziFyA7e4QVaKrWZGLU5fUuAachgekXqcCSqP+4Q6azlPRRDGj/UThWaJxYzbqH0l/wP+U83AZhSJduj3IKKFifzP/3yKozAm/bO01/oFBR4+ZYQKBgQD1uXnUpV8ABCLaAY/kjNpFS4wUbH4lZKie4UcY2mtZszxOWXNvGCdprBhKPBJCLQutdMy1vw0gMpeP1c0XB+gM4k5OBp6eauFy0TylyOIqj8Jr1eAcLnRP1lwjN33vK5kenpkuh1XZSYNFO0cYVjoT8LWVFr2clkmnEyvXeTHFxQKBgQCvp8hqLO6fZG0rxruqNQEL6ZXf+rwBatHM300LKOV5vgIfDRLfzfI/CAzl+2t/FvyI/qK+LkK+pqVTOMnmjBLEPRgd82dmvI+9sr0cizL/1bGcOHbeIwJahiXVkPJ/xZGqPDdPYaUgssCSw5KfL9Ly5+Mtoe9BXNDWmD6SkCoIjQKBgQCmOdhuv4gqjKG+9HuQ0q/9XIPgdRxI03U2NZNQ/sDMJ3HOVri+GIrg82hjH4wCdFKH/pFVCW8prs/Un39j6xdRT+5E3jmDVS45682pIOHOhP2y0TYQGmTYdVxS/oEUwFuO2R8q2KZ8nTxOIzGPBxW204ki2AVg3lHo0hFAbF39OQKBgDIKTE/jCP+jbm5gKSot+2RXYPjzxwdoVOhWXO5m8iiidpw97ziOxQor0vDZlSAZfkvQrrAbIayKcOKqdOoW0WnSNcKiirx7zz8tYi51gUvcpsJoW4Sg2JTNTo3uwwafVAX5LZCRsqcEBVRKHsT2rNPCN7fSQASQ2nWn3XuHUlIpAoGBAIhONPgwQOKXXLJMfTUs35clOPaN0P61R1ZZAHQEk8mZ8fGMXhC602dYhpaVM69wcflioluIOmbQ0jLCvgHvCHsQSmt0EckanfhILFfK1uIPSnW3AyVydHg+dmPr9iTnhNSfTe8NDpSiJKSG23a2+mpS5fpDN+5j/WQ9ULl94hJi';
    private static $PUBLIC_KEY  = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqJrWpaffUZjv5bobDMOe8UNRLDpQ+PkdpdZbhjgZs8gtjgtfRBRaAKAQIc6x8Nm7DLQRTj0OgGR3TsylcG6S3a+PQj7+zNYBnFRAz+ZecYKYA779+oOfIJUt/ZMEUoxvZ7mnr6jK9RVp9woDbtk0gidsIsj3GGk4bYbY056f31dAAf37zAUp1iE3dPnlXF4rl9ThKG25ACyfnEzjmFjjfPO0tmTeLzGXkj1MS+kqiCT5/xTbp3sv+R+5KrN9zuHEdtebXxT+xJvNYlRy5QUFmXEPkNi8x+ekuAJxDt5AlOqTIXONwcP65J/PyzWyNIj4ZhgX+PxWgsVLtEM6kOoVgQIDAQAB';


    /**
     * 获取私钥
     * @return bool|resource
     */
    private static function getPrivateKey()
    {
        $_str = chunk_split(self::$PRIVATE_KEY,64,"\n");
        $privKey = "-----BEGIN PRIVATE KEY-----\n$_str-----END PRIVATE KEY-----\n";
        return openssl_pkey_get_private($privKey);
    }

    /**
     * 获取公钥
     * @return bool|resource
     */
    private static function getPublicKey()
    {

        $_str = chunk_split(self::$PUBLIC_KEY,64,"\n");
        $publicKey = "-----BEGIN PUBLIC KEY-----\n$_str-----END PUBLIC KEY-----\n";
        return openssl_pkey_get_public($publicKey);
    }

    /**
     * 创建签名
     * @param string $data 数据
     * @return null|string
     */
    public function createSign($data = '')
    {
        if (!is_string($data)) {
            return null;
        }
        return openssl_sign(
            $data,
            $sign,
            self::getPrivateKey(),
            OPENSSL_ALGO_SHA256
        ) ? base64_encode($sign) : null;
    }

    /**
     * 验证签名
     * @param string $data 数据
     * @param string $sign 签名
     * @return bool
     */
    public function verifySign($data = '', $sign = '')
    {
        if (!is_string($sign) || !is_string($sign)) {
            return false;
        }
        return (bool)openssl_verify(
            $data,
            base64_decode($sign),
            self::getPublicKey(),
            OPENSSL_ALGO_SHA256
        );
    }
}
