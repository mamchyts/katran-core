<?php
/**
 * The file contains class Secure()
 */
namespace Katran;

/**
 * Secure Class
 * Parses Secure
 *
 * @package Application
 */
class Secure
{
    // max salt length
    const SALT_LENGTH = 32;

    /**
     * Generate salt
     * 
     * @param  string $password
     * @param  string $hash
     * @return bool
     * @access public
     */
    public static function generateSalt()
    {
        return substr(md5(uniqid(mt_rand(), true)), 0, self::SALT_LENGTH/2).
                substr(sha1(uniqid(microtime(true), true)), 0, self::SALT_LENGTH/2);
    }


    /**
     * Function generate hash
     * 
     * @param  string $plainText
     * @param  string $salt
     * @return string
     * @access public
     */
    public static function generateHash($plainText = '', $salt = null)
    {
        if(empty($salt))
            $salt = self::generateSalt();

        if(function_exists('password_verify'))
            return password_hash($plainText, PASSWORD_DEFAULT, ['cost' => 12, 'salt' => $salt]);
        else
            return substr(md5($plainText), 0, 20).substr(sha1($plainText), 0, 20);
    }


    /**
     * PasswordVerify
     * 
     * @param  string $password
     * @param  string $hash
     * @return bool
     * @access public
     */
    public static function passwordVerify($password = '', $hash = '')
    {
        // if php version < 5.5
        if(function_exists('password_verify'))
            return password_verify($password, $hash);
        else
            return self::generateHash($password) === $hash;
    }
}