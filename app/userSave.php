<?php
/**
 *
 * @author candasminareci
 * @property facebookUserSave $facebook
 */
class userSave {
    
    public $facebook;

    public function __construct() {
        $this->facebook = new facebookUserSave;
    }
    
}
