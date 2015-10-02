<?php

namespace app\models;

use Yii;

class User extends \yii\base\Object implements \yii\web\IdentityInterface
{
    public $id = 'sagent';
    public $username;
    public $password;
    public $authKey;
    public $accessToken;
        
    /**
     * @inheritdoc
     */
    public static function findIdentity($id = '')
    {
        $session = Yii::$app->session->get('user');
        return !empty($session) ? new static($session['sauser']) : null;        
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        /*$session = Yii::$app->session->get('user');
        return !empty($session) ? new static($session) : null;*/
        //return isset(self::$_users['100']) ? new static(self::$_users['100']) : null;
    }

    /**
     * Finds user by username
     *
     * @param  string      $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        /*$session = Yii::$app->session->get('user');
        return !empty($session) ? new static($session) : null;*/
        //return isset(self::$_users['100']) ? new static(self::$_users['100']) : null;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /**
     * Validates password
     *
     * @param  string  $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return $this->password === $password;
    }
    
}
