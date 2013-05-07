<?php

/**
 * Description of facebookApi
 *
 * @author candasminareci
 * @todo request error code 190 için düzenleme yapılmalı
 */
class facebookApi extends Facebook {

    const MAX_FQL_REQUEST_LIMIT = 500;

    /**
     * request'ten alınmış signed_request string verisi
     * @var string
     */
    public $signed_request = null;

    /**
     * signed_request in dizi haline çevrilmiş hali
     * @var array
     */
    public $signed_request_decoded = array();

    /**
     * signed_request'ten alınmış access_token verisi
     * @var string
     */
    public $access_token = null;

    /**
     * signed_request'ten alınmış user_id
     * @var string
     */
    public $user_id = null;

    /**
     * uygulama ayarlarında belirlenmiş scope permission string <b>,</b> ile ayrılmış
     * @var string
     */
    public $scope = null;

    /**
     * permission dizi halindeki listesi
     * @var array
     */
    public $scopes = null;

    /**
     * ayarlarda checkPermissions true ise get_missing_permissions ile kontrol eder var ise array ile listeler
     * @var array|null
     */
    public $missing_scopes = null;

    /**
     * kabul edilmeyen izin var ise true set edilir
     * @var bool
     */
    public $missing_scope = false;

    /**
     * uygulamanın facebook altında olup olmadığını kontrol eder signed_request datası var ise true değilse false olur
     * @var bool
     */
    public $under_facebook = false;

    /**
     * Uygulama tab altında ise sayfanın like  bilgisini getirir.
     * is_page_liked fonksiyonu ile set edilir
     * @var bool
     */
    public $page_liked = false;

    /**
     * signed_request datasına göre kullanıcının login olup olmadığını belirtir.
     * @var bool
     */
    public $user_login = false;

    /**
     * uygulama kabul edildikten sonra gelen code
     * @var string default null
     */
    public $code = null;

    /**
     * uygulama kabul edildikten sonra gelen state
     * @var string default null
     */
    public $state = null;

    /**
     * __construct a config ile ayarlanır
     * @var object
     */
    protected $config = null;

    /**
     * Uygulama ayarlarına göre işlemler yapar.
     * @param object $config facebookConfig objesi gönderilmelidir
     */
    public function __construct(facebookConfig $config) {

        parent::__construct(array(
            'appId' => $config->appId,
            'secret' => $config->appSecret,
            'fileUpload' => $config->fileUpload,
        ));

        $this->scope = $config->scope;
        $this->scopes = $config->scopes;
        $this->config = $config;
        //signed_request varmı 
//        Yii::log('signed_request varmi kontrol','info','APP');
        if ($this->is_under_facebook()) {

            $this->parse_appdata_codestate();

            //signed_request encode et
            $this->signed_request_decoded = $this->parseSignedRequest($_REQUEST['signed_request']);
//            Yii::log('signed_request encode edildi','info','APP');
//            Yii::log(json_encode($this->signed_request_decoded),'info','APP');
            //signed_request içerisinde code geldi ise request'e access_token ayrıyetten verilmesi gerekiyor
            if (isset($this->signed_request_decoded['code']) && isset($_REQUEST['access_token'])) {
                $this->setAccessToken($_REQUEST['access_token']);
                $this->access_token = $_REQUEST['access_token'];
                $this->signed_request_decoded['oauth_token'] = $this->access_token;
            }


            //appdata json string check
            if (isset($this->signed_request_decoded['app_data'])) {
                if (substr($this->signed_request_decoded['app_data'], 0, 1) == '{') {
                    $this->signed_request_decoded['app_data'] = json_decode($this->signed_request_decoded['app_data'], true);
                }
            }


            //sayfayı beğenmişmi 
            $this->is_page_liked();
            //kullanıcı uygulamayı kabul etmişmi
            if ($this->is_user_login()) {
                if ($config->checkMissingPermission) {
                    try {
                        $this->get_missing_permissions();
                        //kabul edilmemiş uygulama izni var ise login false yap
                        if ($this->missing_scope) {
                            //                    Yii::log(print_r($this->missing_scopes,true));
                            $this->user_login = false;
                        }
                    } catch (FacebookApiException $e) {
                        $result = $e->getResult();
                        //authexception yani access_token sorunlu 
                        if (isset($result['error']['code']) && $result['error']['code'] == 190) {
                            new CHttpException(401, 'access token expired');
                            //@todo access_token renew yapılacak !                        
                            //                        $user_save = new facebookUserSave;
                            //                        $user_save->set_config($config);
                            //                        //kullanıcı bilgisi
                            //                        $user = Yii::app()->db->createCommand()
                            //                                    ->select('uid,code,state,access_token')
                            //                                    ->from($user_save->get_table_prefix().'user')
                            //                                    ->where('uid="'.$this->user_id.'"')
                            //                                    ->queryRow()
                            //                                ;
                            //                        $this->code = $user['code'];
                            //                        $this->state = $user['state'];
                            //                        $this->access_token = $user['access_token'];
                            //
    //                        $this->renew_access_token();
                            //                        $this->get_missing_permissions();
                        } else if (isset($result['error'])) {
                            helper::printJson($result);
                        }
                    }
                }
            }
        } else {
            //signed_request yok ise facebook altında çalışmaya zorla 
            if ($config->forceUnderFacebook) {
                //@todo code & state parametreleri için düzenleme yapılacak ! 

                $ext_param_ayrac = (strpos($config->appLink, '?') === FALSE) ? '?' : '&';

                //applink yönlendirmesi!
                if (isset($_REQUEST['app_data'])) {
                    Yii::app()->request->redirect($config->appLink . $ext_param_ayrac . 'app_data=' . $_REQUEST['app_data']); //   
                }
                if (count($_GET) > 0) {
                    Yii::app()->request->redirect($config->appLink . $ext_param_ayrac . 'app_data=' . urlencode(json_encode($_GET)));
                }
                Yii::app()->request->redirect($config->appLink);
            }
        }
    }

    /**
     * signed_request e belli olan kullanıcının sayfayı beğenip beğenmediğinin bilgisini verir
     * @param string $page_id belirtilmiş ise fql ile signed_requestteki kullanıcının page like ına bakar
     * @param string $user_id belirtilmiş ise fql sorgusunda bu user_id kullanılır
     */
    public function is_page_liked($page_id = null, $user_id = null) {
        if (is_null($page_id)) {
            //tab aldında değil 

            if (!isset($this->signed_request_decoded['page'])) {
                return false;
            }
            //signed_request'te sayfa like edilmişmi ?
            if (isset($this->signed_request_decoded['page']['liked']) && $this->signed_request_decoded['page']['liked'] == 1) {
                $this->page_liked = true;
                return true;
            }
            return false;
        }

        if (is_null($user_id)) {
            $user_id = $this->user_id;
        }
        //kullanıcının fanı olduğu sayfalardan çek !
        $liked = false;
        $fql = "SELECT uid FROM page_fan WHERE page_id = '" . $page_id . "' and uid = '" . $user_id . "';";
        $response = $this->fql_api($fql);
        if (isset($response['data'])) {
            $liked = count($response['data']) > 0 ? true : false;
        }
        $this->page_liked = $liked;
        return $liked;
    }

    /**
     * signed_request check edip facebook altında olup olmadığını kontrol eder
     * @return bool
     */
    public function is_under_facebook() {
        if (isset($_REQUEST['signed_request'])) {
            $this->under_facebook = true;
            $this->signed_request = $_REQUEST['signed_request'];
            return true;
        }
        return false;
    }

    /**
     * signed_request den kullanıcının login olup olmadığını kontrol eder!
     * @return bool
     */
    public function is_user_login() {
        if (isset($this->signed_request_decoded['user_id'])) {
            $this->user_id = $this->signed_request_decoded['user_id'];

            if (isset($this->signed_request_decoded['oauth_token']))
                $this->access_token = $this->signed_request_decoded['oauth_token'];
            else if (isset($_REQUEST['access_token'])) {
                $this->signed_request_decoded['oauth_token'] = $_REQUEST['access_token'];
                $this->access_token = $_REQUEST['access_token'];
                $this->setAccessToken($_REQUEST['access_token']);
            }

            $this->user_login = true;
            return true;
        }
        return false;
    }

    /**
     * Uygulama'nın isteyip kullanıcının izin vermediği izinleri döndürür
     * @return array|null
     */
    public function get_missing_permissions() {
        $request = '/' . $this->user_id . '/permissions';
        try {
            $response = $this->api($request);
        } catch (Exception $e) {
            $this->missing_scope = true;
            return null;
        }
        $eksik = false;
        $return = array();
        $scope_a = explode(',', str_replace(' ', '', $this->scope));
        if (isset($response['data']) && isset($response['data'][0])) {

            foreach ($scope_a as $row) {
                if (!array_key_exists($row, $response['data'][0])) {
                    $return[] = $row;
                    $eksik = true;
                    break;
                }
            }

            if ($eksik) {
                $this->missing_scope = true;
                $this->missing_scopes = $return;
                return $return;
            }
            else
                return $eksik;
        }

        return null;
    }

    /**
     * Multi fql veya single fql gönderilebilinir exception lar facebook sdk dan üretiliyor <br><b>Multi query kullanımı:</b> array( 'query_1' => 'select username from user where uid=1' )
     * @param string|array $fql 
     * @return array
     */
    public function fql_api($fql) {


        $query = '/fql?q=';

        //multi query gönderildi ise fql sorgusunu düzenle 
        if (is_array($fql)) {

            foreach ($fql as $key => $val) {
                $fql[$key] = str_replace('"', "'", $val);
            }

            $fql = json_encode($fql);
        }

        $query .= urlencode($fql);

        $response = $this->api($query);
        return $response;
    }

    /**
     * dialog login url u geri döndürüyor request['app_data'] var ise link sonuna ekler
     */
    public function get_login_url() {

        $dialog_link = 'https://www.facebook.com/dialog/oauth?client_id=%s&redirect_uri=%s&scope=%s';

        $redirect_uri = $this->config->redirectUri;

        if (isset($this->signed_request_decoded['app_data'])) {
            $redirect_uri.='?app_data=' . json_encode($this->signed_request_decoded['app_data']);
        }

        $dialog_link = sprintf($dialog_link, $this->config->appId, urlencode($redirect_uri), $this->scope);

        return $dialog_link;
    }

    /**
     * signed_request içinden app_data ya eklenen veriyi parse edip geri get data olarak döndürür <p>exp: utm_source=&utm_medium=&utm_campaign=&utm_term=&utm_content=</p>
     * @return string|null
     */
    public function get_utm_link() {
        $link = null;
        if (isset($this->signed_request_decoded['app_data']) && !is_array($this->signed_request_decoded['app_data'])) {

            $utm = $this->signed_request_decoded['app_data'];
            $utm = explode(".", $utm);

            $link = 'utm_source=' . (isset($utm[0]) ? $utm[0] : '')
                    . '&utm_medium=' . ( isset($utm[1]) ? $utm[1] : '')
                    . '&utm_campaign=' . (isset($utm[2]) ? $utm[2] : '')
                    . '&utm_term=' . (isset($utm[3]) ? $utm[3] : '')
                    . '&utm_content=' . (isset($utm[4]) ? $utm[4] : '');
//            Yii::log($link,'info','APP');
        }

        return $link;
    }

    /**
     * access_token expired olmuş ise yeni access_token bilgisini çekip sınıf içerisinde bilgiyi yeniler !
     * TODO bu halledilecek !
     */
    public function renew_access_token() {

//        $token_url = "https://graph.facebook.com/oauth/access_token?client_id=%s&client_secret=%s&code=%s&redirect_uri=%s&display=popup";
        $token_url = "https://graph.facebook.com/oauth/access_token?client_id=%s&client_secret=%s&grant_type=fb_exchange_token&fb_exchange_token=%s&redirect_uri=%s";
//        $url=  sprintf($token_url,$this->appId,$this->apiSecret,$this->access_token,$this->code,$this->config->redirectUri);
        $url = sprintf($token_url, $this->appId, $this->apiSecret, $this->access_token, $this->config->redirectUri);
        $response_s = $this->curl_get_file_contents($url);
        $query = array('access_token' => null);
        parse_str($response_s, $query);

        echo $this->access_token . "<br>";

        helper::printArray($query);
        exit;

        if (isset($query['access_token'])) {
            $this->setAccessToken($query['access_token']);
            $this->access_token = $query['access_token'];
        } else {
            echo $url . '<br>';
            echo 'renewAccessToken sıçtı' . "\r\n";
            helper::printArray($query);
            exit;
        }
    }

    public function getLongLiveAccessToken() {
        //istek yapılacak uri
        $requestUri = 'https://graph.facebook.com/oauth/access_token?';
        //parametreler
        $requestParams = array(
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->config->appId,
            'client_secret' => $this->config->appSecret,
            'fb_exchange_token' => $this->accessToken,
        );
        //request linki
        $request = $requestUri . http_build_query($requestParams);
        //curl isteği
        $response = $this->curl_get_file_contents($request);
        //gelen istek http query olarak geliyor

        parse_str($response, $responseArray);

        return $responseArray;
    }

    protected function curl_get_file_contents($URL) {
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        $err = curl_getinfo($c, CURLINFO_HTTP_CODE);
        curl_close($c);
        if ($contents)
            return $contents;
        else
            return FALSE;
    }

    private function parse_appdata_codestate() {

        if (in_array('app_data', $this->signed_request_decoded)) {

            $data = json_decode($this->signed_request_decoded['app_data']);

            if (is_array($data) && isset($data['code']) && isset($data['state'])) {
                $this->code = $data['code'];
                $this->state = $data['state'];

                $this->signed_request_decoded['app_data'] = array(
                    'code' => $this->code,
                    'state' => $this->state,
                );
            }
        }
    }

}
