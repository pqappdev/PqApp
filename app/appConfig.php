<?php
/**
 * Description app config objelerini barındıran sınıf
 * @author candasminareci
 * @property facebookConfig $facebook facebook config bilgileri
 * @property twitterConfig $twitter twitter config bilgileri
 */
class appConfig {
    /**
     * facebook app ayar bilgileri
     * @var object
     */
    public $facebook;
    /**
     * twitter app ayar bilgileri
     * @var object
     */
    public $twitter=null;
    /**
     * genel app ayarı auto save true olduğunda kullanıcı kaydı app __construct da otomatik kontrol edilip kayıt işlemi yapılır
     * @var bool
     */
    public $auto_save=false;
    
    
    /**
     * kampanya bitiş tarihi
     * @var string|dateTime
     */
    public $endDate = '0000-00-00 00:00:00';
    
    
    /**
     * @param array $config_data  app ayar dosyası içindeki dizi
     */
    public function __construct($config_data=null) {
        $this->facebook=new facebookConfig(isset($config_data['facebook'])?$config_data['facebook']:null);
        
        
        //settings altındaki parametreleri ayarlar
        
        
        //uygulamalar genel ayarları
        if(array_key_exists('settings', $config_data)){
            
            foreach($config_data['settings'] as $property => $val){
                if(property_exists('appConfig', $property)){
                    $this->$property = $val;
                }
            }
        }
    }
    
    public function addConfig($config_data=null){
        
        
        
        if(is_null($config_data) || !is_array($config_data)) return 0;
        $this->facebook->addConfig($config_data['facebook']);
        
        
    }
    
    
}
