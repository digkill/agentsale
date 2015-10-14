<?php

/**
 * @author <votinov.mn@domru.ru>
 */

/**
 * Взаимодействие с биллингом
 */
namespace app\models;

use Yii;
use yii\base\Model;


class BillingRequests extends Model {
    
    /**
     * Возвращает список домов по заданной улице
     * 
     * @param int $street_id
     * @return array
     */
    public function getHouses($street_id) {

        $domain = Yii::$app->session->get('domain');        

        $billingResult = Yii::$app->billing
                ->domain($domain)
                ->alias('es_webface')
                ->package('get_agr_for_client')
                ->procedure('house_list')
                ->data(array('street_id$i' => $street_id))
                ->fire();       
      
        $houseList = array();
        
        if (isset($billingResult->houses) && ($billingResult != false)) {
            foreach ($billingResult->houses->house as $home) {           
                $houseList[(string)$home->house_id] = (string)$home->house_number;                
            }
        }
        
        asort($houseList);
        
        return $houseList;       
    }
    
    /**
     * Проверка адреса
     * 
     * @param array $data
     * @return object
     */
    public function checkAddress($data) {
	$houseBuild = $this->_dissectAddress($data['house']);
	$dataCheck = Array('street' => $data['street'], 'house_num' => mb_strtolower($houseBuild['house_num']), 'house_build' => $houseBuild['building'], 'office' => $data['office']);
	$r = $this->_checkAddress($dataCheck, $data['city']);
	return $r;
    }

    /**
     * Преобразование адреса в нужный формат
     * 
     * @param string $house
     * @return array
     */
    private function _dissectAddress($house) {
        $houseBuild = array();
        $houseNum = explode('/', $house);
        if (count($houseNum) == 2) {
            $houseBuild['building'] = $houseNum[1];
            $houseBuild['house_num'] = $houseNum[0];
        } else {
            $houseBuild['house_num'] = intval($house);
            $building = str_replace($houseBuild['house_num'], "", $house);
            $specSign = array("\\", " ", "-");
            $building = str_replace($specSign, "", $building);
            $houseBuild['building'] = trim($building, "/");
        }

        return $houseBuild;
    }

    /**
     * Проверка адреса на возможность подключения
     * 
     * @param array $data
     * @param string $city
     * @return object
     */
    private function _checkAddress($data, $city)
    {
        $query = Yii::$app->billing
            ->domain($city)
            ->alias('es_webface')
            ->package('web_clients_create')
            ->procedure('check_connect')
            ->data($data)
            ->fire(true);
        return $query;
    }

    /**
     * Получение пакетов услуг
     * 
     * @param string $token
     * @return array
     */
    public function getProducts($agreement_number = '') {

        $query = Yii::$app->billing
                ->domain(Yii::$app->session->get('domain'))
                ->alias('es_webface')
                ->package('web_cabinet')
                ->procedure('get_info')
                ->data(
                        array(
                            'access_token' => Yii::$app->session->get('token'),
                            'params' => 'ds_packages_sa_list',
                            'param_names_arr$c' => 'agreement_number',
                            'param_values_arr$c' => "{$agreement_number}"
                        )
                )
                ->fire();
        
        return $this->objectToArray($query);
    }
    
    /**
     * Получение оборудования
     * 
     * @param string $token Токен доступа
     * @param int $street ID улицы
     * @param int $house_num Номер дома
     * @param int $office Номер квартиры
     * @param int $material_to Оборудование для: 1 - интернет, 2 - ДОМ.RU TV, 3 - обе услуги
     * @param int $flag_id РА (РА, которая привязана к пакету)
     * @param int $house_build Номер строения (опционально)
     * @return array
     */
    public function getDevices($street, $house_num, $office, $material_to, $flag_id, $agr_pack_id, $agreement_number) {

    $house = $this->_dissectAddress($house_num);
    $house_num = $house['house_num'];
    if(isset($house['building']) && !empty($house['building'])) 
        $house_build = $house['building'];
    else
        $house_build = '';
        
    $query = Yii::$app->billing
                ->domain(Yii::$app->session->get('domain'))
                ->alias('es_webface')
                ->package('web_cabinet')
                ->procedure('get_info')
                ->data(
                        array(
                            'access_token' => Yii::$app->session->get('token'),
                            'params' => 'ds_materials_sa_list',
                            'param_names_arr$c' => 'street,house_num,office,material_to,flag_id,agr_pack_id,agreement_number,house_build',
                            'param_values_arr$c' => "{$street},{$house_num},{$office},{$material_to},{$flag_id},{$agr_pack_id},{$agreement_number},{$house_build}"
                        )
                )
                ->fire();
        
        return $this->objectToArray($query);
    }
    
    /**
     * Валидация номера договора в биллинге
     * @param int $agreement_number
     * @param int $agr_pack_id
     * @return array
     */
    public function Checkagreement($agreement_number, $agr_pack_id) {
        
        $query = Yii::$app->billing
                ->domain(Yii::$app->session->get('domain'))
                ->alias('es_webface')
                ->package('web_cabinet')
                ->procedure('get_info')
                ->data(
                        array(
                            'access_token' => Yii::$app->session->get('token'),
                            'params' => 'check_agr_num_ds',
                            'param_names_arr$c' => 'agreement_number,agr_pack_id',
                            'param_values_arr$c' => "{$agreement_number},{$agr_pack_id}"
                        )
                )
                ->fire();        
        
        
        return $this->objectToArray($query);
    }
    
    public function sendRequest($data) {
        
        if(!isset($data['client_phone_extra']) || empty($data['client_phone_extra'])) $data['client_phone_extra'] = '-1';
        if(!isset($data['materials_ens_id_int']) || empty($data['materials_ens_id_int'])) $data['materials_ens_id_int'] = '-1';
        if(!isset($data['materials_ens_id_tv']) || empty($data['materials_ens_id_tv'])) $data['materials_ens_id_tv'] = '-1';

        $data['client_name'] = self::_encodeTo($data['client_name']);
        $data['comment'] = self::_encodeTo($data['comment']);
        $house = $this->_dissectAddress($data['house_num']);
        $data['house_num'] = $house['house_num'];
        if(isset($house['building']) && !empty($house['building'])) 
            $data['house_build'] = $house['building'];
        else
            $data['house_build'] = '';
       
        if(!isset($data['agreementNumber']) || (isset($data['agreementNumber']) && empty($data['agreementNumber']))) {
            $data['agreementNumber'] = '-1';
        }
        
        if(!isset($data['comment']) || (isset($data['comment']) && empty($data['comment']))) {
            $data['comment'] = '0';
        }
        
        set_time_limit(180);
        
        $query = Yii::$app->billing
                ->domain(Yii::$app->session->get('domain'))
                ->alias('es_webface')
                ->package('web_cabinet')
                ->procedure('get_info')
                ->data( 
                        array(
                            'access_token' => Yii::$app->session->get('token'),
                            'params' => 'ds_create_agr',
                            'param_names_arr$c' => 'street,house_num,office,client_name,client_phone,agr_pack_id,comment,client_phone_extra,agreement_number,materials_ens_id_int,materials_ens_id_tv,house_build',
                            'param_values_arr$c' => "{$data['street']},{$data['house_num']},{$data['office']},{$data['client_name']},{$data['client_phone']},{$data['agr_pack_id']},{$data['comment']},{$data['client_phone_extra']},{$data['agreementNumber']},{$data['materials_ens_id_int']},{$data['materials_ens_id_tv']},{$data['house_build']}"
                        )
                )
                ->fire();        
                            
        return $query;
    }
    
    /**
     * Convert an object to an array
     *
     * @param object $object The object to convert
     * @return array
     *
     */
    protected function objectToArray($object) {
        if (!is_object($object) && !is_array($object)) {
            return $object;
        }
        if (is_object($object)) {
            $object = get_object_vars($object);
        }
        return array_map(array($this, 'objectToArray'), $object);
    }
    
    /**
     * Кодирование строки в указанную кодировку
     * @param string $sting Строка
     * @param string $encoding Кодировка
     * @return string Результирующая строка
     */
    static function _encodeTo($sting, $encoding = 'windows-1251') {
        return iconv(mb_detect_encoding($sting, mb_detect_order(), true), $encoding, $sting);
    }
    
}