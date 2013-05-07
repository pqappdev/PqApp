<?php

/**
 * @todo save işleminde ilk yapılan kayıt tek multi_fql ile yapılmadı diğer işlemler async yapılmalı!
 * @todo async işlemlerde access_token zamanı düşer ise log kaydı yapmalı !
 * @todo fql ile alınan datalar için sadece fql isteğini limit li yapıp cevap döndürecek fonksiyonların eklenmesi sınıfın yeniden düzenlenmesi lazım!
 * @todo log sisteminin düzenlenmesi gerekiyor
 */
include 'helper.php';
class facebookUserSave {

    /**
     * tabloların veritabanındaki ön eki 
     * @var string
     */
    protected $table_prefix = 'fb_';

    /**
     * @property facebookConfig $config
     */
    protected $config;

    /**
     * @property facebookApi $facebook
     */
    protected $facebook;

    /**
     * permissionslara göre user bilgisinin alınacağı tabloları
     */
    protected $tables = null;

    /**
     * Kullanıcı veritabanına kayıtlımı kontrolü yapar
     * @var bool
     */
    public $exists = false;

    /**
     * Kullanıcı bilgileri tablo ya kaydedildimi kontrolü yapar
     * @var bool
     */
    public $saved = false;

    /**
     * asyncCall ile çağrılıcak controller ismi
     * @var string
     */
    public $asynCall = 'FbUserSave';
    
    /**
     * facebook user datası
     * @var array|null
     */
    public $tableData = null;
    
    /**
     * izinlere göre ilişkili tablo isimleri
     * @var array
     */
    private $scopes_tables = array(
        'email' => array('user'),
        'user_activities' => array('user'),
        'user_education_history' => array('user'),
        'user_groups' => array('group', 'group_member'),
        'user_likes' => array('page_fan', 'page'),
        'user_photos' => array('photo', 'album'),
        'user_relationships' => array('user'),
        'user_subscriptions' => array('subscription'),
        'user_work_history' => array('user'),
        'user_birthday' => array('user'),
        'user_events' => array('event_member', 'event'),
        'user_hometown' => array('user'),
        'user_location' => array('user'),
//@todo sadece kullanıcınn açtığu question bilgisi veriyor kullanıcının cevapladığı question lar alınamıyor        
//        'user_questions'        => array('user'), 
        'user_religion_politics' => array('user'),
        'user_videos' => array('video'),
        'user_about_me' => array('user'),
        'user_interests' => array('user'),
        'user_website' => array('user'),
    );

    /**
     * tablo şablonları 
     * @var array
     */
    private $scope_db_tables = array(
        'user' => "CREATE TABLE `%suser` (  `id` int(11) NOT NULL AUTO_INCREMENT, `uid` varchar(255) NOT NULL,`access_token` text DEFAULT NULL,`long_access_token` text DEFAULT NULL,`code` text DEFAULT NULL,`state` text DEFAULT NULL, `username` varchar(255) DEFAULT NULL, `first_name` varchar(255) DEFAULT NULL, `middle_name` varchar(255) DEFAULT NULL, `last_name` varchar(255) DEFAULT NULL, `name` varchar(255) DEFAULT NULL, `pic_small` text, `pic_big` text, `pic_square` text, `pic` text, `profile_update_time` varchar(255) NULL DEFAULT NULL, `timezone` int(11) DEFAULT NULL, `religion` varchar(255) DEFAULT NULL, `birthday` varchar(255) DEFAULT NULL, `birthday_date` varchar(10) DEFAULT NULL,  `devices` text, `sex` varchar(10) DEFAULT NULL, `hometown_location` text, `meeting_sex` text, `meeting_for` text, `relationship_status` varchar(255) DEFAULT NULL, `significant_other_id` varchar(255) DEFAULT NULL, `political` varchar(255) DEFAULT NULL, `current_location` text, `activities` text, `interests` text, `is_app_user` enum('true','false') DEFAULT NULL, `music` text, `tv` text, `movies` text, `books` text, `quotes` text, `about_me` text, `notes_count` int(11) DEFAULT NULL,  `wall_count` int(11) DEFAULT NULL,  `status` text, `online_presence` varchar(10) DEFAULT NULL, `locale` varchar(10) DEFAULT NULL, `proxied_email` text, `profile_url` text, `email_hashes` text, `pic_cover` text, `allowed_restrictions` varchar(255) DEFAULT NULL, `verified` enum('true','false') DEFAULT NULL DEFAULT 'false', `profile_blurb` varchar(255) DEFAULT NULL, `family` text, `website` varchar(255) DEFAULT NULL, `is_blocked` enum('true','false') NOT NULL DEFAULT 'false', `contact_email` text, `email` text, `third_party_id` varchar(255) DEFAULT NULL, `name_format` varchar(255) DEFAULT NULL, `video_upload_limits` text, `games` text, `work` text, `education` text, `sports` text, `favorite_athletes` text, `favorite_teams` text, `inspirational_people` text, `languages` text, `likes_count` int(11) DEFAULT NULL, `friend_count` int(11) DEFAULT NULL, `can_post` enum('true','false') DEFAULT NULL, `active` enum('true','false') NOT NULL DEFAULT 'false', `create_time` datetime DEFAULT NULL, `update_time` timestamp NULL DEFAULT NULL, `user_likes_saved` enum('true','false') DEFAULT NULL DEFAULT 'false',PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'friend' => "CREATE TABLE `%sfriend` ( `id` int(11) NOT NULL AUTO_INCREMENT,  `uid1` varchar(255) NOT NULL, `uid2` varchar(255) NOT NULL,PRIMARY KEY (`id`), KEY `friend_uid1` (`uid1`) USING BTREE, KEY `friend_uid2` (`uid2`) USING BTREE ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'group' => "CREATE TABLE `%sgroup` (`id` int(11) NOT NULL AUTO_INCREMENT,`gid` varchar(255) NOT NULL,`name` varchar(255) DEFAULT NULL,`nid` int(11) DEFAULT NULL,`pic_small` text,`pic_big` text,`pic` text,`description` text,`recent_news` text,`creator` varchar(255) DEFAULT NULL,`update_time` varchar(255) NULL DEFAULT NULL,`office` varchar(255) DEFAULT NULL,`website` varchar(255) DEFAULT NULL,`venue` text,`privacy` varchar(255) DEFAULT NULL,`icon` text,`icon34` text,`icon68` text,`email` varchar(255) DEFAULT NULL,`version` varchar(255) DEFAULT NULL,PRIMARY KEY (`id`),KEY `group_gid` (`gid`) USING BTREE) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'group_member' => "CREATE TABLE `%sgroup_member` (`id` int(11) NOT NULL AUTO_INCREMENT,`uid` varchar(255) NOT NULL,`gid` varchar(255) NOT NULL,`administrator` enum('true','false') DEFAULT NULL,`positions` varchar(255) DEFAULT NULL,`unread` int(11) DEFAULT NULL,`bookmark_order` int(11) DEFAULT NULL,PRIMARY KEY (`id`),KEY `group_member_uid` (`uid`) USING BTREE,KEY `group_member_gid` (`gid`) USING BTREE) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'page' => "CREATE TABLE `%spage` (`id` int(11) NOT NULL AUTO_INCREMENT,`page_id` varchar(255) NOT NULL,`name` varchar(255) DEFAULT NULL,`username` varchar(255) DEFAULT NULL, `description` text,`page_url` text,`categories` text,`is_community_page` varchar(255) DEFAULT NULL,`pic_small` text, `pic_big` text,`pic_square` text, `pic` text, `pic_large` text,`pic_cover` text,`unread_notif_count` int(11) DEFAULT NULL DEFAULT NULL,`new_like_count` int(11) DEFAULT NULL ,`fan_count` int(11) DEFAULT NULL,`type` varchar(255) DEFAULT NULL,`website` text, `has_added_app` enum('true','false') DEFAULT NULL,`general_info` text,`can_post` enum('true','false') DEFAULT NULL,`checkins` int(11) DEFAULT NULL,`is_published` enum('true','false') DEFAULT NULL,`phone` varchar(255) DEFAULT NULL,  PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'page_fan' => "CREATE TABLE `%spage_fan` (`id` int(11) NOT NULL AUTO_INCREMENT,`uid` varchar(255) NOT NULL,`page_id` varchar(255) NOT NULL,`type` varchar(255) DEFAULT NULL,`profile_section` varchar(255) DEFAULT NULL,`created_time` varchar(255) NULL DEFAULT NULL,PRIMARY KEY (`id`),KEY `page_fan_uid` (`uid`) USING BTREE,KEY `page_fan_page_id` (`page_id`) USING BTREE) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'user_likes' => "CREATE TABLE `%suser_likes` (`id` int(11) NOT NULL AUTO_INCREMENT,`uid` varchar(255) NOT NULL,`page_id` varchar(255) NOT NULL,`category` varchar(255) DEFAULT NULL,`name` varchar(255) DEFAULT NULL,`created_time` varchar(255) NULL DEFAULT NULL,PRIMARY KEY (`id`),KEY `user_likes_uid` (`uid`) USING BTREE,KEY `user_likes_page_id` (`page_id`) USING BTREE) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'photo' => "CREATE TABLE `%sphoto` (`id` int(11) NOT NULL AUTO_INCREMENT,`object_id` varchar(255) NOT NULL,`pid` varchar(255) NOT NULL,`aid` varchar(255) NOT NULL,`owner` varchar(255) NOT NULL,`src_small` text,`src_big` text,`src` text,`link` text,`caption` text,`created` varchar(255) NULL DEFAULT NULL,`modifed` varchar(255) NULL DEFAULT NULL,`position` int(11) DEFAULT NULL,`place_id` int(11) DEFAULT NULL,`like_info` text,`comment_info` text,`can_delete` enum('true','false') DEFAULT NULL,  PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'album' => "CREATE TABLE `%salbum` ( `id` int(11) NOT NULL AUTO_INCREMENT,`aid` varchar(255) NOT NULL,`object_id` varchar(255) NOT NULL,`owner` varchar(255) NOT NULL,`cover_pid` varchar(255) DEFAULT NULL, `cover_object_id` varchar(255) DEFAULT NULL,`name` varchar(255) DEFAULT NULL,`created` varchar(255) NULL DEFAULT NULL, `description` text, `location` text,`size` int(11) DEFAULT NULL,`link` text,`visible` varchar(255) DEFAULT NULL, `modified_major` varchar(255) NULL DEFAULT NULL, `edit_link` text,`type` varchar(255) DEFAULT NULL,`can_upload` enum('true','false') DEFAULT NULL,`photo_count` int(11) DEFAULT NULL,`video_count` int(11) DEFAULT NULL,`like_info` text, `comment_info` text,PRIMARY KEY (`id`), KEY `album_aid` (`aid`) USING BTREE,KEY `album_object_id` (`object_id`) USING BTREE, KEY `album_owner` (`owner`) USING BTREE) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'video' => "CREATE TABLE `%svideo` (`id` int(11) NOT NULL AUTO_INCREMENT,`vid` varchar(255) NOT NULL,`owner` varchar(255) NOT NULL,`title` text,`description` text,`link` text,`thumbnail_link` text,`embed_html` text,`updated_time` varchar(255) NULL DEFAULT NULL,`created_time` varchar(255) NULL DEFAULT NULL,`length` varchar(255) DEFAULT NULL,`src` text,`src_hq` text,PRIMARY KEY (`id`),KEY `video_vid` (`vid`) USING BTREE,KEY `video_owner` (`owner`) USING BTREE) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'subscription' => "CREATE TABLE `%ssubscription` (`id` int(11) NOT NULL AUTO_INCREMENT,`subscribed_id` varchar(255) NOT NULL,`subscriber_id` varchar(255) NOT NULL,PRIMARY KEY (`id`),KEY `subscription_ subscribed_id` (`subscribed_id`) USING BTREE,KEY `subscription_ subscriber_id` (`subscriber_id`) USING BTREE) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'event' => "CREATE TABLE `%sevent` (`id` int(11) NOT NULL AUTO_INCREMENT,`eid` varchar(255) NOT NULL,`name` varchar(255) DEFAULT NULL,`pic_small` text,`pic_big` text,`pic_square` text,`pic` text,`host` text,`description` text,`start_time` varchar(255) NULL DEFAULT NULL,`end_time` varchar(255) NULL DEFAULT NULL,`creator` varchar(255) DEFAULT NULL,`update_time` varchar(255) NULL DEFAULT NULL,`location` text,`venue` text,`privacy` varchar(255) DEFAULT NULL,`hide_guest_list` enum('true','false') DEFAULT NULL,`can_invite_friends` enum('true','false') DEFAULT NULL,`all_members_count` int(11) DEFAULT NULL,`attending_count` int(11) DEFAULT NULL,`unsure_count` int(11) DEFAULT NULL,`declined_count` int(11) DEFAULT NULL,`not_replied_count` int(11) DEFAULT NULL,PRIMARY KEY (`id`),KEY `event_eid` (`eid`) USING BTREE) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'event_member' => "CREATE TABLE `%sevent_member` (`id` int(11) NOT NULL AUTO_INCREMENT,`uid` varchar(255) NOT NULL,`eid` varchar(255) NOT NULL,`rsvp_status` varchar(15) DEFAULT NULL,`start_time` varchar(255) NULL DEFAULT NULL,PRIMARY KEY (`id`),KEY `event_member_uid` (`uid`) USING BTREE,KEY `event_member_eid` (`eid`) USING BTREE) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
    );
    private $fql_schemas = array(
        'user' => 'select uid,username,first_name,middle_name,last_name,name,pic_small,pic_big,pic_square,pic,profile_update_time,timezone,religion,birthday,birthday_date,devices,sex,hometown_location,meeting_sex,meeting_for,relationship_status,significant_other_id,political,current_location,activities,interests,is_app_user,music,tv,movies,books,quotes,about_me,notes_count,wall_count,status,online_presence,locale,proxied_email,profile_url,email_hashes,pic_cover,allowed_restrictions,verified,profile_blurb,family,website,is_blocked,contact_email,email,third_party_id,name_format,video_upload_limits,games,work,education,sports,favorite_athletes,favorite_teams,inspirational_people,languages,likes_count,friend_count,can_post from user where uid=%1$s',
        'user_friend' => 'select uid,username,first_name,middle_name,last_name,name,pic_small,pic_big,pic_square,pic,profile_update_time,timezone,religion,birthday,birthday_date,devices,sex,hometown_location,meeting_sex,meeting_for,relationship_status,significant_other_id,political,current_location,activities,interests,is_app_user,music,tv,movies,books,quotes,about_me,notes_count,wall_count,status,online_presence,locale,proxied_email,profile_url,email_hashes,pic_cover,allowed_restrictions,verified,profile_blurb,family,website,is_blocked,contact_email,email,third_party_id,name_format,video_upload_limits,games,work,education,sports,favorite_athletes,favorite_teams,inspirational_people,languages,likes_count,friend_count,can_post from user where uid in (select uid2 from #friend limit %2$d,%3$d) limit %2$d,%3$d',
        'friend' => 'select uid1,uid2 from friend where uid1=%1$s limit %2$s,%3$s',
        'group' => 'select gid,name,nid,pic_small,pic_big,pic,description,recent_news,creator,update_time,office,website,venue,privacy,icon,icon34,icon68,email,version from group where gid in (select gid from #group_member)',
        'group_member' => 'select uid,gid,administrator,positions,unread,bookmark_order from group_member where uid="%1$s"',
        'page' => 'select page_id,name,username,description,page_url,categories,is_community_page,pic_small,pic_big,pic_square,pic,pic_large,pic_cover,unread_notif_count,new_like_count,fan_count,type,website,has_added_app,general_info,can_post,checkins,is_published,phone from page where page_id in (select page_id from #page_fan)',
        'page_fan' => 'select uid,page_id,type,profile_section,created_time from page_fan where uid="%1$s"',
        'photo' => 'select object_id,pid,aid,owner,src_small,src_big,src,link,caption,created,modified,position,place_id,like_info,comment_info,can_delete from photo where aid in (select aid from #album) limit %2$d,%3$d',
        'album' => 'select aid,object_id,owner,cover_pid,cover_object_id,name,created,description,location,size,link,visible,modified_major,edit_link,type,can_upload,photo_count,video_count,like_info,comment_info from album where owner=%1$s limit %2$d,%3$d',
        'video' => 'select vid,owner,title,description,link,thumbnail_link,embed_html,updated_time,created_time,length,src,src_hq from video where owner=%1$s limit %2$d,%3$d',
        'subscription' => 'select subscribed_id,subscriber_id from subscription where subscriber_id=%1$s limit %2$d,%3$d',
        'event' => 'select eid,name,pic_small,pic_big,pic_square,pic,host,description,start_time,end_time,creator,update_time,location,venue,privacy,hide_guest_list,can_invite_friends,all_members_count,attending_count,unsure_count,declined_count,not_replied_count from event where eid in (select eid from #event_member)',
        'event_member' => 'select uid,eid,rsvp_status,start_time from event_member where uid="%1$s" limit %2$d,%3$d',
    );

    /**
     * Async isteklerin sonuna eklenecek extra get parametreleri
     * @var string
     */
    protected $async_ext_param = '';

    public function get_table_prefix() {
        return $this->table_prefix;
    }

    /**
     * tabloları belirler
     * eksik tabloları yaratır
     * kullanıcı kontrolü yapar
     */
    public function load() {
        $this->set_tables();
        $this->check_tables();
        $this->check_user();
    }

    /**
     * ayarlarda tanımlanmış facebookConfig objeye çevrilmiş bilgilerin kaydı
     * @property facebookConfig $config 
     */
    public function set_config(facebookConfig $config) {
        $this->config = $config;
        //table prefix ayarı
        if (!is_null($config->table_prefix)) {
            $this->table_prefix = $config->table_prefix;
        }
    }

    /**
     * facebookApi sdk dan türetilmiş olan sınıfın tanımı
     * @property facebookApi $config 
     */
    public function set_api(facebookApi $facebook) {
        $this->facebook = $facebook;
        //AsyncCall için ekstra param datası
        $this->async_ext_param = '&signed_request=' . $this->facebook->signed_request;
        if (isset($_REQUEST['access_token'])) {
            $this->async_ext_param.='&access_token=' . $_REQUEST['access_token'];
        }
    }

    /**
     * kayıt yapılıcak tabloları scope'lara göre ayarlar
     * @return array
     */
    public function set_tables() {
        //config içerisinde seçili tables var ise bunları scope_db_tables dan karşılaştırıp olanları tables a aktar !
        if (!is_null($this->config->tables) && is_array($this->config->tables)) {
            $this->tables = $this->config->tables;
            return $this->tables;
        }

        $tables = array();
        //config de tables seçili değil ise scope's a göre tablo isimlerini sabitle
        foreach ($this->config->scopes as $val) {

            if (!array_key_exists($val, $this->scopes_tables))
                continue;

            if (is_array($this->scopes_tables[$val])) {
                $tables = array_merge($tables, $this->scopes_tables[$val]);
            } else {
                $tables [] = $this->scopes_tables[$val];
            }
        }

        $u_tables = array_unique($tables);

        $this->tables = $u_tables;
        return $this->tables;
    }

    /**
     * set tables ile belirlenmiş tablo isimlerini veritabanınıda eksik olan var ise kontrol eder olmayanları yaratır
     * @return bool
     */
    public function check_tables() {

        $sql = "show tables like '" . $this->table_prefix . "%'";
        $command = Yii::app()->db->createCommand($sql);
        $command->execute();
        $rows = $command->queryAll();

        $new_tables = array();
        $var_olan_tablolar = array();
        if (count($rows) > 0) {
            foreach ($rows as $key => $val) {
                foreach ($val as $val2)
                    $var_olan_tablolar[] = str_replace($this->table_prefix, '', $val2);
            }
            $new_tables = array_diff($this->tables, $var_olan_tablolar);
        } else {
            $new_tables = $this->tables;
        }

        //düzenlenmiş yeni yaratılması gereken tabloları işle
        $this->create_tables($new_tables);
    }

    /**
     * eksik tabloları yaratır 
     * @param array $tables check_tables da belirlenmiş eksik tablo isimleri
     */
    public function create_tables($new_tables = array()) {

        if (!is_array($new_tables) && count($new_tables) == 0) {
            return 0;
        }

        foreach ($new_tables as $key) {
            $sql = sprintf($this->scope_db_tables[$key], $this->table_prefix);
            $command = Yii::app()->db->createCommand($sql);
            $command->execute();
        }
    }

    /**
     * facebookApi de set edilmiş user_id veritabanında user tablosunda kayıtlı olup olmadığına bakar
     * @param string|null $uid
     * @return bool
     */
    public function check_user($uid = null) {

        if (is_null($uid)) {
            $uid = $this->facebook->user_id;
        }

        try {

            $user = Yii::app()->db->createCommand()
                    ->select('*')
                    ->from($this->table_prefix . 'user')
                    ->where('uid="' . $uid . '" and active="true"')
                    ->queryRow();
        } catch (Exception $e) {
            //TODO burada tabloların olup olmadığını kontrol edip çalışmasını sağla!
            return false;
        }


        if ($user) {
            $this->tableData = $user;
            $this->exists = true;
            //TODO access_token yenile !
            if ($this->facebook->access_token != $user['access_token'] && !is_null($this->facebook->access_token)) {
                Yii::app()->db->createCommand()->update($this->table_prefix . 'user', array(
                    'access_token' => $this->facebook->access_token
                        ), 'uid="' . $uid . '"');
            }
        }

        return !$user ? false : true;
    }

    public function get_fql_schemas($table_name = null) {

        if (is_null($table_name))
            return $this->fql_schemas;

        return $this->fql_schemas[$table_name];
    }

    /**
     * 
     * izinlere göre çekilicek tabloları belirler 
     * kullanıcı kaydını kontrol eder
     * kullanıcıyı kaydeder
     * 
     * TODO user hariç bütün diğer kaydet istkeleri async hale getirilecek!
     */
    public function save() {

        if (in_array('user', $this->tables)) {
           $this->save_user();
        }
        if (in_array('friend', $this->tables)) {
            $this->save_friends();
        }
//        if (in_array('page', $this->tables)) {
////            helper::asyncCall($this->config->redirectUri . $this->asynCall . '?app_user_save_likes=0,'.facebookApi::MAX_FQL_REQUEST_LIMIT.$this->async_ext_param );
//            $this->save_likes();
//        }
        if (in_array('user_likes', $this->tables)) {
            $this->save_likes();
        }
        if (in_array('group', $this->tables)) {
            $this->save_group();
        }

        if (in_array('photo', $this->tables)) {
            $this->save_photo();
        }
        if (in_array('event', $this->tables)) {
            $this->save_event();
        }
        if (in_array('video', $this->tables)) {
            $this->save_video();
        }

        if (in_array('subscription', $this->tables)) {
            $this->save_subscription();
        }
    }

    protected function save_user() {
        $user_table = $this->table_prefix . "user";
        $user = Yii::app()->db->createCommand()
                ->select('count(id) as say,active')
                ->from($user_table)
                ->where('uid="' . $this->facebook->user_id . '"')
                ->queryRow();

        if ($user['say'] == 1 && $user['active'] == 'true') {
            return 1;
        }

        $fql = sprintf($this->fql_schemas['user'], $this->facebook->user_id);
        $response = $this->facebook->fql_api($fql);
        $data = $response['data'][0];
        $uid = $data['uid'];
//      array gelen verileri json a çevir
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $val = json_encode($val);
            }
            $data[$key] = $val;
        }
        //@todo access_token ve code eklenecek buraya !
        $data['access_token'] = $this->facebook->getAccessToken();
        $longAccessTokenData = $this->facebook->getLongLiveAccessToken();
        $data['long_access_token'] = isset($longAccessTokenData['access_token'])?$longAccessTokenData['access_token']:'';
        $data['code'] = $this->facebook->code;
        $data['state'] = $this->facebook->state;
        $data['active'] = 'true';
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = '0000-00-00 00:00:00';
        $user_command = Yii::app()->db->createCommand();
        //kullanıcı yok ise kaydet
        if ($user['say'] == 0) {
            $result = $user_command->insert($user_table, $data);
            if ($result) {
                $this->saved = true;
            } else {
                Yii::log('kullanıcı kaydı yapılamadı', 'error');
            }
        }
        unset($data['uid']);
        unset($data['create_time']);
        $data['update_time'] = date('Y-m-d H:i:s');
        if ($user['say'] == 1 && $user['active'] == 'false') {
            $result = $user_command->update($user_table, $data, 'uid="' . $uid . '"');
            if ($result) {
                $this->saved = true;
            } else {
                Yii::log('kullanıcı kayıt güncellemesi yapılamadı', 'error');
            }
        }
    }

    protected function save_friends() {
        $event_req_key = 'app_user_friend_save';
        //kullanıcı kayıtlarını al! user_friends tablosu var ise ozmn user a friends ekliceksin
        $f_count = Yii::app()->db->createCommand()
                ->select('count(id)')
                ->from($this->table_prefix . 'friend')
                ->where('uid1="' . $this->facebook->user_id . '"')
                ->queryScalar();
        //async call değil ise ve kullanıcı kaydı var ise işlem yapma
        if (!isset($_REQUEST[$event_req_key]) && $f_count > 0) {
            return 1;
        }
        $limit_bas = 0;
        $limit_son = facebookApi::MAX_FQL_REQUEST_LIMIT / 2;
        ;
        $app_user_friend_save_limit = null;
        if (isset($_REQUEST[$event_req_key])) {
            $app_user_friend_save_limit = explode(',', $_REQUEST[$event_req_key]);
            $limit_bas = $app_user_friend_save_limit[0];
            Yii::log($event_req_key, 'info', 'AppAsync');
            Yii::log($this->facebook->user_id, 'info', 'AppAsync');
            Yii::log($event_req_key . '=' . $_REQUEST[$event_req_key], 'info', 'AppAsync');
        }

        $fql = array(
            'friend' => sprintf($this->fql_schemas['friend'], $this->facebook->user_id, $limit_bas, $limit_son),
            'user_friend' => sprintf($this->fql_schemas['user_friend'], $this->facebook->user_id, 0, $limit_son),
        );

        try {
            Yii::log(json_encode($this->facebook->signed_request_decoded), 'info', 'AppAsync');
            Yii::log('access_token:' . $this->facebook->access_token, 'info', 'AppAsync');
            $response = $this->facebook->fql_api($fql);
        } catch (Exception $e) {

            Yii::log($e->getMessage(), 'error', 'AppAsync');
            Yii::app()->end();
        }

        if (!isset($response['data'])) {
            Yii::log(json_encode($response), 'error', 'AppAsync');
            $adres = $this->config->redirectUri . $this->asynCall . '?' . $event_req_key . '=' . ($limit_bas * $limit_son) . ',' . $limit_son . $this->async_ext_param;
            helper::asyncCall($adres);
            return 0;
        }

        $friend = $response['data'][0]['fql_result_set'];
        $user_friend = $response['data'][1]['fql_result_set'];
        $friend_count = count($friend);


        if (isset($_REQUEST[$event_req_key])) {
            Yii::log('friend count =' . count($friend), 'info', 'AppAsync');
        }


        foreach ($friend as $row) {
            $count = Yii::app()->db->createCommand()
                    ->select('count(id)')
                    ->from($this->table_prefix . 'friend')
                    ->where('uid1="' . $row['uid1'] . '" and uid2="' . $row['uid2'] . '"')
                    ->queryScalar()
            ;

            if ($count == 0)
                Yii::app()->db->createCommand()->insert($this->table_prefix . 'friend', $row);
        }

        foreach ($user_friend as $row) {
            //kullanıcı tablosunda yok ise kaydet
            $count = Yii::app()->db->createCommand()->select('count(id) as say,active,id')->from($this->table_prefix . 'user')->where('uid="' . $row['uid'] . '"')->queryRow();
            if ($count['say'] == 0) {
                //array json çevirisi
                foreach ($row as $key => $val) {
                    if (is_array($val)) {
                        $row[$key] = json_encode($val);
                    }
                }
                $row['active'] = 'false';
                $row['create_time'] = date('Y-m-d H:i:s');
                Yii::app()->db->createCommand()->insert($this->table_prefix . 'user', $row);
            }
        }

        //friend varmı varsa dataları al bir sonraki friend datası için async at eğer friend datası limit_son dan az ise async atma
        if ($friend_count == $limit_son) {
            $adres = $this->config->redirectUri . $this->asynCall . '?' . $event_req_key . '=' . ($limit_bas + 1 * $limit_son) . ',' . $limit_son . $this->async_ext_param;
            Yii::log('uid:' . $this->facebook->user_id, 'info', 'AppAsync');
            Yii::log('New Call:' . $adres, 'info', 'AppAsync');
            helper::asyncCall($adres);
        }
    }
    /**
     * kullanıcı like datasını tutuyor!
     */
    protected function save_likes() {
        //kullanıcı like datası kaydedilmişmi
        $fCount = Yii::app()->db->createCommand()->select('count(id)')->from($this->table_prefix . 'user_likes')->where('uid="' . $this->facebook->user_id . '"')->queryScalar();
        
        if($fCount>0){
            return true;
        }
        
        $graphRequestUrl = '/'.$this->facebook->user_id.'/likes?limit=5000';
        $paginNext = false;
        $table = $this->table_prefix . 'user_likes';
        do{
            //like listesini çek
           $response = $this->facebook->api($graphRequestUrl);
//           Yii::log(json_encode($response),'error');
           $responseArray = $response;//json_decode($response);
           if(isset($responseArray['error'])){
              Yii::log($this->facebook->user_id.', kullanıcı like verisi alınamadı: '.  json_encode($responseArray['error']),'error');
              return false; 
           }
           if(!isset($responseArray['data'])){
               Yii::log($this->facebook->user_id.' Kullanıcı datası alınamadı','error');
               return false;
           }
           //data kaydı yap!
           foreach($responseArray['data'] as $row){
               
               $insertArray = array(
                   'uid' => $this->facebook->user_id,
                   'page_id' => isset($row['id'])?$row['id']:'',
                   'category' => isset($row['category'])?$row['category']:'',
                   'name' => isset($row['name'])?$row['name']:'',
                   'created_time' => isset($row['created_time'])?$row['created_time']:'',                   
               );
               Yii::app()->db->createCommand()->insert($table,$insertArray);
           }
           //devamı varmı ?
           if(isset($responseArray['paging']['next'])){
               $paginNext = true;
               $graphRequestUrl=str_replace('https://graph.facebook.com', '', $responseArray['paging']['next']);
           }else{
               $paginNext = false;
           }
            
        }while($paginNext);
        
        //kullanıcı like'ları alındıysa user tablosuna işle
        $userTable = $this->table_prefix.'user';
        $data = array(
            'user_likes_saved' => 'true'
        );
        $condition = 'uid="'.$this->facebook->user_id.'"';
        $resultUpdate = Yii::app()->db->createCommand()->update($userTable,$data ,$condition);
        
//        $event_req_key = 'app_user_save_likes';
//
//        $f_count = Yii::app()->db->createCommand()->select('count(id)')->from($this->table_prefix . 'page_fan')->where('uid="' . $this->facebook->user_id . '"')->queryScalar();
//
//        //async call değil ise ve kullanıcı kaydı var ise işlem yapma
//        if (!isset($_REQUEST[$event_req_key]) && $f_count > 0) {
//            return 1;
//        }
//
//        $limit_bas = 0;
//        $limit = facebookApi::MAX_FQL_REQUEST_LIMIT / 2;
//
//        $async_limit = null;
//        if (isset($_REQUEST[$event_req_key])) {
////            Yii::log('async call geldi ','warning');
//            $async_limit = explode(',', $_REQUEST[$event_req_key]);
//            $limit_bas = $async_limit[0];
//        }
//
//
//        $fql = array(
//            'page_fan' => sprintf($this->fql_schemas['page_fan'], $this->facebook->user_id, $limit_bas, $limit),
//            'page' => sprintf($this->fql_schemas['page'], $this->facebook->user_id, $limit_bas, $limit),
//        );
//
//        try {
//            Yii::log(json_encode($this->facebook->signed_request_decoded), 'info', 'AppAsync');
//            Yii::log('access_token:' . $this->facebook->access_token, 'info', 'AppAsync');
//            $response = $this->facebook->fql_api($fql);
//        } catch (Exception $e) {
//
//            Yii::log($e->getMessage(), 'error', 'AppAsync');
//            Yii::app()->end();
//        }
//
//
//        $page_fan = $response['data'][0]['fql_result_set'];
//        $page = $response['data'][1]['fql_result_set'];
//        $page_fan_count = count($page_fan);
//        $table = $this->table_prefix . 'page_fan';
//        foreach ($page_fan as $row) {
//
//            $count = Yii::app()->db->createCommand()
//                    ->select('count(id)')
//                    ->from($table)
//                    ->where('uid="' . $row['uid'] . '" and page_id="' . $row['page_id'] . '"')
//                    ->queryScalar()
//            ;
//
//            if ($count == 0) {
//                Yii::app()->db->createCommand()->insert($this->table_prefix . 'page_fan', $row);
//            }
//        }
//        $table = $this->table_prefix . 'page';
//        foreach ($page as $row) {
//            $count = Yii::app()->db->createCommand()
//                    ->select('count(id)')
//                    ->from($table)
//                    ->where('page_id="' . $row['page_id'] . '"')
//                    ->queryScalar();
//            if ($count == 0) {
//
//                foreach ($row as $key => $val) {
//                    if (is_array($val) || is_object($val)) {
//                        $row[$key] = json_encode($val);
//                    }
//                }
//
//                Yii::app()->db->createCommand()->insert($table, $row);
//            }
//        }
//
//        if ($page_fan_count == $limit) {
//            $adres = $this->config->redirectUri . $this->asynCall . '?' . $event_req_key . '=' . ($limit_bas + 1 * $limit) . ',' . $limit . $this->async_ext_param;
//            helper::asyncCall($adres);
//        }
    }

    protected function save_group() {
        $event_req_key = 'app_user_save_group';
        $f_count = Yii::app()->db->createCommand()->select('count(id)')->from($this->table_prefix . 'group_member')->where('uid="' . $this->facebook->user_id . '"')->queryScalar();
        //async call değil ise ve kullanıcı kaydı var ise işlem yapma
        if (!isset($_REQUEST[$event_req_key]) && $f_count > 0) {
            return 1;
        }

        $limit_bas = 0;
        $limit = facebookApi::MAX_FQL_REQUEST_LIMIT / 2;

        $async_limit = null;
        if (isset($_REQUEST[$event_req_key])) {
//            Yii::log('async call geldi ','warning');
            $async_limit = explode(',', $_REQUEST[$event_req_key]);
            $limit_bas = $async_limit[0];
        }


        $fql = array(
            'group_member' => sprintf($this->fql_schemas['group_member'], $this->facebook->user_id, $limit_bas, $limit),
            'group' => sprintf($this->fql_schemas['group'], $this->facebook->user_id, $limit_bas, $limit),
        );

        $response = $this->facebook->fql_api($fql);
        $group_member = $response['data'][0]['fql_result_set'];
        $group = $response['data'][1]['fql_result_set'];
        $page_fan_count = count($group_member);
        $table = $this->table_prefix . 'group_member';
        foreach ($group_member as $row) {

            $count = Yii::app()->db->createCommand()
                    ->select('count(id)')
                    ->from($table)
                    ->where('uid="' . $row['uid'] . '" and gid="' . $row['gid'] . '"')
                    ->queryScalar()
            ;

            if ($count == 0) {
                foreach ($row as $key => $val) {
                    if (is_array($val) || is_object($val)) {
                        $row[$key] = json_encode($val);
                    }
                }
                Yii::app()->db->createCommand()->insert($table, $row);
            }
        }
        $table = $this->table_prefix . 'group';
        foreach ($group as $row) {
            $count = Yii::app()->db->createCommand()
                    ->select('count(id)')
                    ->from($table)
                    ->where('gid="' . $row['gid'] . '"')
                    ->queryScalar();
            if ($count == 0) {

                foreach ($row as $key => $val) {
                    if (is_array($val) || is_object($val)) {
                        $row[$key] = json_encode($val);
                    }
                }

                Yii::app()->db->createCommand()->insert($table, $row);
            }
        }

        if ($page_fan_count == $limit) {
            $adres = $this->config->redirectUri . $this->asynCall . '?' . $event_req_key . '=' . ($limit_bas + 1 * $limit) . ',' . $limit . $this->async_ext_param;
            helper::asyncCall($adres);
        }
    }

    protected function save_photo() {
        $event_req_key = 'app_user_save_photo';
        $f_count = Yii::app()->db->createCommand()->select('count(id)')->from($this->table_prefix . 'album')->where('owner="' . $this->facebook->user_id . '"')->queryScalar();
        //async call değil ise ve kullanıcı kaydı var ise işlem yapma
        if (!isset($_REQUEST[$event_req_key]) && $f_count > 0) {
            return 1;
        }

        $limit_bas = 0;
        $limit = facebookApi::MAX_FQL_REQUEST_LIMIT / 2;

        $async_limit = null;
        if (isset($_REQUEST[$event_req_key])) {
            $async_limit = explode(',', $_REQUEST[$event_req_key]);
            $limit_bas = $async_limit[0];
        }


        $fql = array(
            //250 den fazla albüm olmaz bug!
            'album' => sprintf($this->fql_schemas['album'], $this->facebook->user_id, 0, $limit),
            'photo' => sprintf($this->fql_schemas['photo'], $this->facebook->user_id, $limit_bas, $limit),
        );

        $response = $this->facebook->fql_api($fql);
        $album = $response['data'][0]['fql_result_set'];
        $photo = $response['data'][1]['fql_result_set'];

        $photo_count = count($album);
        $table = $this->table_prefix . 'album';
        foreach ($album as $row) {

            $count = Yii::app()->db->createCommand()
                    ->select('count(id)')
                    ->from($table)
                    ->where('owner="' . $row['owner'] . '" and aid="' . $row['aid'] . '"')
                    ->queryScalar()
            ;

            if ($count == 0) {
                foreach ($row as $key => $val) {
                    if (is_array($val) || is_object($val)) {
                        $row[$key] = json_encode($val);
                    }
                }
                Yii::app()->db->createCommand()->insert($table, $row);
            }
        }
        $table = $this->table_prefix . 'photo';
        foreach ($photo as $row) {
            $count = Yii::app()->db->createCommand()
                    ->select('count(id)')
                    ->from($table)
                    ->where('gid="' . $row['gid'] . '"')
                    ->queryScalar();
            if ($count == 0) {

                foreach ($row as $key => $val) {
                    if (is_array($val) || is_object($val)) {
                        $row[$key] = json_encode($val);
                    }
                }

                Yii::app()->db->createCommand()->insert($table, $row);
            }
        }

        if ($photo_count == $limit) {
            $adres = $this->config->redirectUri . $this->asynCall . '?' . $event_req_key . '=' . ($limit_bas + 1 * $limit) . ',' . $limit . $this->async_ext_param;
            helper::asyncCall($adres);
        }
    }

    protected function save_event() {
        $event_req_key = 'app_user_save_event';
        $f_count = Yii::app()->db->createCommand()->select('count(id)')->from($this->table_prefix . 'event_member')->where('uid="' . $this->facebook->user_id . '"')->queryScalar();
        //async call değil ise ve kullanıcı kaydı var ise işlem yapma
        if (!isset($_REQUEST[$event_req_key]) && $f_count > 0) {
            return 1;
        }

        $limit_bas = 0;
        $limit = facebookApi::MAX_FQL_REQUEST_LIMIT / 2;

        $async_limit = null;
        if (isset($_REQUEST[$event_req_key])) {
//            Yii::log('async call geldi ','warning');
            $async_limit = explode(',', $_REQUEST[$event_req_key]);
            $limit_bas = $async_limit[0];
        }


        $fql = array(
            //250 den fazla albüm olmaz bug!
            'event_member' => sprintf($this->fql_schemas['event_member'], $this->facebook->user_id, 0, $limit),
            'event' => sprintf($this->fql_schemas['event'], $this->facebook->user_id, $limit_bas, $limit),
        );

        $response = $this->facebook->fql_api($fql);
        $event_member = $response['data'][0]['fql_result_set'];
        $event = $response['data'][1]['fql_result_set'];

        $event_member_count = count($event_member);
        $table = $this->table_prefix . 'event_member';
        foreach ($event_member as $row) {

            $count = Yii::app()->db->createCommand()
                    ->select('count(id)')
                    ->from($table)
                    ->where('uid="' . $row['uid'] . '"')
                    ->queryScalar()
            ;

            if ($count == 0) {
                foreach ($row as $key => $val) {
                    if (is_array($val) || is_object($val)) {
                        $row[$key] = json_encode($val);
                    }
                }
                Yii::app()->db->createCommand()->insert($table, $row);
            }
        }
        $table = $this->table_prefix . 'event';
        foreach ($event as $row) {
            $count = Yii::app()->db->createCommand()
                    ->select('count(id)')
                    ->from($table)
                    ->where('eid="' . $row['eid'] . '"')
                    ->queryScalar();
            if ($count == 0) {

                foreach ($row as $key => $val) {
                    if (is_array($val) || is_object($val)) {
                        $row[$key] = json_encode($val);
                    }
                }

                Yii::app()->db->createCommand()->insert($table, $row);
            }
        }

        if ($event_member_count == $limit) {
            $adres = $this->config->redirectUri . $this->asynCall . '?' . $event_req_key . '=' . ($limit_bas + 1 * $limit) . ',' . $limit . $this->async_ext_param;
            helper::asyncCall($adres);
        }
    }

    protected function save_subscription() {
        $event_req_key = 'app_user_save_subscription';
        $f_count = Yii::app()->db->createCommand()
                        ->select('count(id)')
                        ->from($this->table_prefix . 'subscription')
                        ->where('subscriber_id="' . $this->facebook->user_id . '"')->queryScalar();
        //async call değil ise ve kullanıcı kaydı var ise işlem yapma
        if (!isset($_REQUEST[$event_req_key]) && $f_count > 0) {
            return 1;
        }

        $limit_bas = 0;
        $limit = facebookApi::MAX_FQL_REQUEST_LIMIT / 2;

        $async_limit = null;
        if (isset($_REQUEST[$event_req_key])) {
//            Yii::log('async call geldi ','warning');
            $async_limit = explode(',', $_REQUEST[$event_req_key]);
            $limit_bas = $async_limit[0];
        }


        $fql = array(
            'subscription' => sprintf($this->fql_schemas['subscription'], $this->facebook->user_id, $limit_bas, $limit),
        );

        $response = $this->facebook->fql_api($fql);
        $subscription = $response['data'][0]['fql_result_set'];

        $subscription_count = count($subscription);
        $table = $this->table_prefix . 'subscription';
        foreach ($subscription as $row) {

            $count = Yii::app()->db->createCommand()
                    ->select('count(id)')
                    ->from($table)
                    ->where('subscriber_id="' . $row['subscriber_id'] . '"')
                    ->queryScalar()
            ;

            if ($count == 0) {
                foreach ($row as $key => $val) {
                    if (is_array($val) || is_object($val)) {
                        $row[$key] = json_encode($val);
                    }
                }
                Yii::app()->db->createCommand()->insert($table, $row);
            }
        }
        if ($subscription_count == $limit) {
            $adres = $this->config->redirectUri . $this->asynCall . '?' . $event_req_key . '=' . ($limit_bas + 1 * $limit) . ',' . $limit . $this->async_ext_param;
            helper::asyncCall($adres);
        }
    }

    protected function save_video() {
        $table = $this->table_prefix . 'video';
        $event_req_key = 'app_user_save_video';
        $f_count = Yii::app()->db->createCommand()
                        ->select('count(id)')
                        ->from($table)
                        ->where('owner="' . $this->facebook->user_id . '"')->queryScalar();
        //async call değil ise ve kullanıcı kaydı var ise işlem yapma
        if (!isset($_REQUEST[$event_req_key]) && $f_count > 0) {
            return 1;
        }

        $limit_bas = 0;
        $limit = facebookApi::MAX_FQL_REQUEST_LIMIT / 2;

        $async_limit = null;
        if (isset($_REQUEST[$event_req_key])) {
//            Yii::log('async call geldi ','warning');
            $async_limit = explode(',', $_REQUEST[$event_req_key]);
            $limit_bas = $async_limit[0];
        }


        $fql = array(
            'video' => sprintf($this->fql_schemas['video'], $this->facebook->user_id, $limit_bas, $limit),
        );

        $response = $this->facebook->fql_api($fql);
        $subscription = $response['data'][0]['fql_result_set'];

        $subscription_count = count($subscription);

        foreach ($subscription as $row) {

            $count = Yii::app()->db->createCommand()
                    ->select('count(id)')
                    ->from($table)
                    ->where('owner="' . $row['owner'] . '"')
                    ->queryScalar()
            ;

            if ($count == 0) {
                foreach ($row as $key => $val) {
                    if (is_array($val) || is_object($val)) {
                        $row[$key] = json_encode($val);
                    }
                }
                Yii::app()->db->createCommand()->insert($table, $row);
            }
        }
        if ($subscription_count == $limit) {
            $adres = $this->config->redirectUri . $this->asynCall . '?' . $event_req_key . '=' . ($limit_bas + 1 * $limit) . ',' . $limit . $this->async_ext_param;
            helper::asyncCall($adres);
        }
    }

}