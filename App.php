<?php 

/** alına bilen bütün datalar alınsın projesi
 * 
 * config / api / default.php,danino.php veya define('DEFAULT_APP_CONFIG','danino');
 * 
 * extensions 
 * app.class
 * app
 *  config.class
 *  facebookConfig.class
 *  twitterConfig.class
 *  @todo app sınıfının logları ayrı bir dosya içinde yazılmalı düzgünce loglama işlemi yapılmalı !
 */
Yii::import('application.vendors.pqappdev.pqapp.app.*');
/**
 * @todo twitter eklenecek 
 * @todo facebookusersave sınıfı yazılacak
 * @property facebookApi $facebook facebook sdk dan türetilmiş sınıf
 * @property userSave $user user kayıt sınıflarının üst objesi
 * @property appConfig $config genel ayar dosyası
 */
class App {
    /**
     * default app config dosya ismi
     * @var string
     */

    const DEFAULT_APP_CONFIG = 'default';

    /**
     * default app config path'i 
     * @var string
     */
    private $file_path = '/protected/config/app/';

    /**
     * yüklenen config file dosyasının tam yolu
     * @var string
     */
    public $config_file = null;

    /**
     * Yüklenen config dosyası içerisindeki data
     * @var array
     */
    public $config_data = null;

    /**
     * Api config objesi
     * @var object
     */
    public $config = null;

    public $facebook = null;
    
    public $user =null ;

    /**
     * 
     * @param string $config_name ayar dosya ismi config/app altındaki dosya ismi dosya uzantısı olmadan
     * @param array $config_data config ayarlarını manual gönderebilmek için
     * @param bool $extra dosyadaki config ayarlarından harici ekstra setting belirtmek isterseniz aynı config dosyası dizisi şeklinde gerekli alanları yazınız
     * @return App 
     */    
    public static function load($config_name = null, $config_data = null,$extra=false){
        global $___APP_OBJECT___;
        
        if(empty($___APP_OBJECT___)){
            $___APP_OBJECT___ = new App($config_name, $config_data,$extra);
        }
        
        return $___APP_OBJECT___;
    }
    

    /**
     * @param string $config_name ayar dosya ismi config/app altındaki dosya ismi dosya uzantısı olmadan
     * @param array $config_data config ayarlarını manual gönderebilmek için
     * @param bool $extra dosyadaki config ayarlarından harici ekstra setting belirtmek isterseniz aynı config dosyası dizisi şeklinde gerekli alanları yazınız
     */
    public function __construct($config_name = null, $config_data = null,$extra=false) {
        
        //config dosyasını yükler
        $this->setConfig($config_name,$config_data,$extra);
        
        $this->setApi();
        //user save sınıfların ayarlanması ve izinlere göre kayıt yapılması
        $this->saveUser();
        
    }

    /**
     * config dosyasını sisteme yükler 
     * @param string $config_name ayar dosya ismi config/app altındaki dosya ismi dosya uzantısı olmadan
     * @param array $config_data config ayarlarını manual gönderebilmek için     
     */
    protected function setConfig($config_name = null, $config_data = null,$extra = false) {
     
        if(is_array($config_data) && !$extra){
            $this->config_data = $config_data;
        }else{
            $config_path= realpath('.').$this->file_path;
            switch (TRUE) {
                case !is_null($config_name):
                    $config_path.=$config_name . '.php';
                    break;
                case defined('DEFAULT_APP_CONFIG'):
                    $config_path.=DEFAULT_APP_CONFIG . '.php';
                    break;
                default:
                    $config_path.=self::DEFAULT_APP_CONFIG . '.php';
            }

            if (!file_exists($config_path)) {              
                Yii::log('app config dosyası bulunamadı ' . $config_path, 'error', 'App::__construct');
                return null;
            }
            
            $this->config_file = $config_path;
            $this->config_data = include $config_path;            
            
        }

        $this->config = new appConfig($this->config_data);
        if($extra){        
            $this->config->addConfig($config_data);
        }
    }

    /**
     * api sdk sınıflarını sisteme yükle
     */
    protected function setApi() {
        
        $this->user = new userSave;        
        
        if(is_null($this->config)){
            return false;
        }
        
        if (!is_null($this->config->facebook)) {
            $this->facebook = new facebookApi($this->config->facebook);
            $this->user->facebook->set_config($this->config->facebook);
            $this->user->facebook->set_api($this->facebook);
            $this->user->facebook->load();
        }
        if (!is_null($this->config->twitter)) {
            //@todo twitter sınıfı yazılacak !
//            $this->twitter = new twitterApi();
        }
    }

    /**
     * Kullanıcı kaydı yapar
     */
    public function saveUser() {
        
        //user save sınıflarının set edilmesi
        #facebookUserSave
        
        if(is_null($this->config)) 
            return 0;
        
        if( $this->facebook->user_login && $this->config->auto_save){
            $this->user->facebook->save();
            //@todo diğer user save ler burada tanıtılacak
        }        
                
    }

}
