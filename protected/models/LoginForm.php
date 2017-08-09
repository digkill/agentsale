<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $city; 
    public $rememberMe = true;

    private $_identity;
    private $_user = false;
    
    //private $_user = false;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            // password is validated by validatePassword()
            ['password', 'authenticate'],
            ['city', 'safe'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean']          
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'username' => 'Логин',
            'password' => 'Пароль',
            'city' => 'Город',
            'rememberMe' => 'Запомнить меня'            
        ];
    }
    
    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    /*public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Неверный логин или пароль.');
            }
        }
    }*/
    
    
    /**
     * Authenticates the password.
     * This is the 'authenticate' validator as declared in rules().
     */
    public function authenticate($attribute, $params) {
        if (!$this->hasErrors()) {

            $this->_identity = new UserAuth($this->username, $this->password, $this->rememberMe);
            if (!$this->_identity->authenticate()) {
                $errorText = 'Произошла ошибка. Попробуйте авторизоваться позже.';
                if (isset($this->_identity->errorMessage)) {
                    $errorText = $this->_identity->errorMessage;
                }
                $this->addError('password', $errorText);
            }
        }
    }
    

    /**
     * Logs in a user using the provided username and password.
     * @return boolean whether the user is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {            
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600*24*30 : 0);
        } else {
            return false;
        }
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {            
            $this->_user = User::findIdentity();
        }
        return $this->_user;
    }
    
    /**
     * подгружаем города из консоли
     * @return object возвращает массив города в виде объекта
     */
    public function getCities() {

        //город для входа
        $citydomain = Yii::$app->params['city'];

        if (YII_DEBUG) {
            $cities = Yii::$app->erconsole->makeRequest('geography/cities/list', ['mode' => 'full']);
        } else {
            $cities = Yii::$app->erconsole->makeRequest('geography/cities/list');
        }

        // отмечаем город, в котором находимся
        foreach ($cities->result as $key => $value) {
            if ($value->domain == $citydomain) {
                $selected = 'selected';
            } else {
                $selected = '';
            }
            $cities->result[$key]->selected = $selected;                        
        }

        return $cities->result;
    }
    
}
