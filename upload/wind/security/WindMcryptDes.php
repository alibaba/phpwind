<?php
/**
 * @fileName: WindMcryptDes.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-03-03 20:17:32
 * @desc: 
 **/
Wind::import('WIND:security.IWindSecurity');

class WindMcryptDes implements IWindSecurity {

    public function encrypt($input, $key) {
        $input = $this->pkcs5_pad($input);
        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_ECB, '');
        mcrypt_generic_init($td, substr($key,0,24), '00000000');
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return base64_encode($data);
    }

    public function decrypt($input, $key ) {
        $encrypted = base64_decode($input);
        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_ECB, '');
        mcrypt_generic_init($td, substr($key,0,24), '00000000');
        $decrypted = mdecrypt_generic($td, $encrypted);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $decrypted = $this->pkcs5_unpad($decrypted);
        return $decrypted;
    } 

    private function pkcs5_pad ($source) {
        $block = mcrypt_get_block_size(MCRYPT_3DES, MCRYPT_MODE_ECB);
        $pad = $block - (strlen($source) % $block);
        if ($pad <= $block) {
            $char = chr($pad);
            $source .= str_repeat($char, $pad);
        }
        return $source;
    } 

    private function pkcs5_unpad($source) { 
        $char = substr($source, -1, 1);
        $num = ord($char);
        if ($num > 8) {
            return $source;
        }
        $len = strlen($source);
        for ($i = $len - 1; $i >= $len - $num; $i--) {
            if (ord(substr($source, $i, 1)) != $num) {
                return $source;
            }
        }
        $source = substr($source, 0, -$num);
        return $source;
    }
} 

