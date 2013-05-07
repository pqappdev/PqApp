<?php
/**
 * Description of helper
 *
 * @author promoqube
 */
class helper {
    /**
     * gönderilen diziyi pre arasında basarak çıktı verir
     * @param $dizi array
     */
    public static function printArray($dizi){
        echo '<pre>'.print_r($dizi,true).'</pre>';
    }
    /**
     * gönderilen diziyi pre arasında basarak çıktı verir
     * @param $dizi array
     */
    public static function printDump($dizi){
        echo '<pre>'.  var_dump($dizi).'</pre>';
    }
    /**
     * gönderilen dizi veya objeyi json çıktısı olarak bastırır ve çıktıyı tmmlar
     * @param $dizi array
     */
    public static function printJson($dizi){
        header('Content-Type: application/json;');
        echo json_encode($dizi);
        Yii::app()->end();
    }
    public static function getFileExtension($file){
        $exp = explode('.',$file);
        return $exp[count($exp)-1];
    }
    public static function is_email($mail_address) {
        $pattern = "/^[\w-]+(\.[\w-]+)*@";
        $pattern .= "([0-9a-z][0-9a-z-]*[0-9a-z]\.)+([a-z]{2,4})$/i";
        if (preg_match($pattern, $mail_address)) {
            $parts = explode("@", $mail_address);

            if (function_exists('checkdnsrr'))
                return checkdnsrr($parts[1], "MX") ? true : false;
            else
                return true;
        } else {
            //echo "The e-mail address contains invalid charcters.";
            return false;
        }
    }
    /**
     * @param string url adresi
     * @return boolean
     */
    public static function asyncCall($url){
        
        $parts = parse_url($url);
        $fp = fsockopen($parts['host'], isset($parts['port'])?$parts['port']:80, $errno, $errstr, 30);
        
        if(!$fp)
            return false;
        
        $out='POST '.$parts['path'].' HTTP/1.1'."\r\n";
        $out.='Host: '.$parts['host']."\r\n";
        $out.='User-Agent: WishMesh.com com custom user agent not gecko!'."\r\n";
        $out.='Content-Type: application/x-www-form-urlencoded'."\r\n";
        $out.='Content-Length: '.(isset($parts['query'])?strlen($parts['query']):0)."\r\n";
        $out.='Connection: Close'."\r\n"."\r\n";
        if(isset($parts['query'])) $out.=$parts['query'];
        fwrite($fp, $out);
        fclose($fp);
        return true;
        
    }    

}

?>
