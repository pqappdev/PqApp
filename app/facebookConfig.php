<?php

/**
 * facebook app ayarları
 *
 * @author candasminareci
 */
class facebookConfig {

    /**
     * Uygulama id si 
     * @var string
     */
    public $appId = null;

    /**
     * uygulama secret
     * @var string
     */
    public $appSecret = null;

    /**
     * uygulamanın çalıştığı link
     * @var string
     */
    public $appLink = null;

    /**
     * Uygulama hem app hemde tab page de çalışıyor ise pageLink tab için kullanılabilinir
     * @var string
     */
    public $appPageLink = null;

    /**
     * Sayfa id'si
     * @var string
     */
    public $pageId = null;
    /**
     * Sayfa like linki
     * @var string
     */
    public $pageLikeLink = null;

    /**
     * Kullanıcı izninden sonra geri dönüş link'i
     * @var string
     */
    public $redirectUri = null;

    /**
     * Kullanıcıdan istenen izinler <b>,</b> ile  ayrılıarak belirtilmiş halde. exp:email, user_likes
     * @var string
     */
    public $scope = null;

    /**
     * kullanıcı izinlerinin dizi'ye çevrilmiş hali
     * @var array
     */
    public $scopes = array();

    /**
     * Uygulama facebook altında çalışmaya zorlar eğer signed_request yok ise app sınıfı altında applink'e yönlenir
     * @var bool 
     */
    public $forceUnderFacebook = false;

    /**
     * Uygulama upload işlemi gerektirecek ise true yapınız
     * @var bool
     */
    public $fileUpload = false;

    /**
     * Kayıt yapılması istenen tablo'lar facebookUserSave sınıfında $scope_db_tables dizi'si içerisindeki key isimleri
     * @var array|null
     */
    public $tables = null;

    /**
     * fb bilgileri için kayıt yapılan tablolar için
     * @var string|null
     */
    public $table_prefix = null;

    /**
     * Sınıf başlarken kullanıcı uygulamaya izin verdi ise uygulama izinlerini kontrol eder.
     * @var bool
     */
    public $checkMissingPermission = true;
    
    
    /**
     * config sayfasında'ki ayarları yükler
     * @param array $settings facebook ayarları
     */
    public function __construct($settings = null) {
        $this->setConfig($settings);
    }

    public function addConfig($config) {
        $this->setConfig($config);
    }

    protected function setConfig($settings) {
        //boş gönderilirse geri gönder
        if (is_null($settings))
            return false;

        foreach ($settings as $property => $val) {
            if (property_exists('facebookConfig', $property)) {
                $this->$property = $val;
            }
        }

        //permissionları array içine almak için
        if (!is_null($this->scope)) {
            $scope = str_replace(' ', '', $this->scope);
            $scopes = explode(',', $scope);
            $this->scopes = $scopes;
        }
    }

}

