<?php

namespace app\models;

use Yii;

class UserAuth extends User {

    public $rememberMe = '';
    public $errorMessage = '';

    public function __construct($username, $password, $rememberMe) {
        $this->username = $username;
        $this->password = $password;
        $this->rememberMe = $rememberMe;
    }

    /**
     * Authenticates a user.     
     * @return boolean whether authentication succeeds.
     */
    public function authenticate() {

        $res = $this->_userLoginPasswordAuth();

        // check if response from billing is exists
        // else was thrown BillingException
        if ($res !== false) {
            // xml response object in raw view
            $result = Yii::$app->billing->billinganswer;

            if(isset($result->messages->text)) $this->errorMessage = strval($result->messages->text);
            
            if ($this->_setUser($result)) {

                if (!isset($result->result->access_token)) {
                    if (isset($result->messages->text)) {                            
                        return false;
                    }
                } else {
                    return true;
                }

            }
        } else {
            $this->errorMessage = 'Произошла ошибка. Попробуйте войти позднее, либо обратитесь в техническую поддержку.';
            return false;
        }

        return false;
    }

    /**
     * Авторизация в биллинге - получение сессии
     * 
     * @param type $hashLogin - true для авторизации Interzet через hash, false - для стандартной авторизации
     * @return object 
     */
    private function _userLoginPasswordAuth($hashLogin = false) {
        
        $client_id = Yii::$app->params['openauth_long']['client_id'];
        $secret = Yii::$app->params['openauth_long']['secret'];
        $grant_type = Yii::$app->params['openauth_long']['grant_type'];
        $responce_type = Yii::$app->params['openauth_long']['response_type$c'];        

        //Время в UTC для авторизации
        date_default_timezone_set('UTC');
        $timestamp = date('YmdHis');

        if (!empty($this->username) || !empty($this->password)) {
            $res = Yii::$app->billing
                    ->alias('es_webface')
                    ->package('open_auth');
            
            $client_secret = md5($client_id . $grant_type . $this->username . $this->password . $timestamp . $secret);
            $res = $res->procedure('ds_authorize_password');            

            $res = $res->data(
                            array(
                                'client_id' => $client_id,
                                'grant_type' => $grant_type,
                                'username' => $this->username,
                                'timestamp$c' => $timestamp,
                                'client_secret' => $client_secret,
                                'response_type$c' => $responce_type
                            )
                    )->domain(isset($_POST['LoginForm']['city']) ? $_POST['LoginForm']['city'] : 'perm')->fire();
        } else {
            $res = false;
        }

        return $res;
    }

    /**
     * По переданному результату $result - XML-объекту от биллинга, возвращаемому при авторизации
     * производим запись в сессионные параметры - роль, токен, устанавливаем COOKIE,
     * проводим проверки результатов работы функции авторизации в объекте $result
     * 
     * @param object $result - XML-объект
     * @return boolean - в зависимости от $result возвращает либо true/либо false
     */
     private function _setUser($result) {
        if (intval($result->status) == 1 && isset($result->result->access_token)) {
            $token = strval($result->result->access_token);
            Yii::$app->session->set('token', $token);
            
            $user = array(
                'sauser' => array(
                    'username' => $this->username
                )
            );

            Yii::$app->session->set('user', $user);

            return true;
        } else {
            return false;
        }
    }

    /*public function getUser() {
        return new static(Yii::$app->session->get('user'));
    }*/

}
