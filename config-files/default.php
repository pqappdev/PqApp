<?php
 
return array(
    'facebook' => array(
        'appId'                 => '', //appid
        'appSecret'             => '', //appsecret
        'appLink'               => '', //applink adress
        'pageLikeLink'          => '', //app like url
        'pageId'                => '', //facebook page id
        'appPageLink'           => '', //applink adress under facebook page
        'redirectUri'           => '', //application url
        'scope'                 => 'user_likes,user_about_me,user_interests,user_education_history,user_work_history,email,user_birthday,user_hometown,user_location,user_relationships,user_relationship_details,user_website', //application permissions
        'forceUnderFacebook'    => false, //if its true and signed_request not exists inside $_REQUEST, application redirect automatically
        'checkMissingPermission'=> false, //it will check missingpermissions with curl
        'fileUpload'            => false, //facebook sdk upload support
        'tables'                => array('user','user_likes'), //facebook user tables which will store in database
        'table_prefix'          => 'fb_',
    ),
    //custom setting params for application
    'settings' => array(
        'endDate' => '2014-01-01 00:00:00',
    ),
);