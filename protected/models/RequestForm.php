<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * ContactForm is the model behind the contact form.
 */
class RequestForm extends Model {
    public $firstname; // Имя
    public $lastname; // Фамилия
    public $patronymic; // Отчество
    public $street; // Улица 
    public $house; // Дом
    public $flat; // Квартира
	public $comment; // Комментарий
    public $telephone; // Телефон
    public $addTelephone; // Доп. телефон
	public $agreementNumber; // Номер договора

    /**
     * @return array the validation rules.
     */ 
    public function rules()
    {
        return [            
            [['firstname', 'lastname', 'telephone', 'street', 'house', 'flat'], 'required']            
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'firstname' => 'Имя',
            'lastname' => 'Фамилия',
            'patronymic' => 'Отчество',
            'street' => 'Улица',
            'house' => 'Дом',
            'flat' => 'Квартира',
			'comment' => 'Комментарий',
            'telephone' => 'Телефон',
            'addTelephone' => 'Доп. телефон',
			'agreementNumber' => '№ договора'			
        ];
    }

    public function sendForm()
    {
        if ($this->validate()) {
            return true;
        } else {
            return false;
        }
    }
}