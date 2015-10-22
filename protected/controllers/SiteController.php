<?php

/**
 * @author <votinov.mn@domru.ru>
 */

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller; 
use yii\helpers\Json; 
use app\models\LoginForm;
use app\models\RequestForm;
use app\models\BillingRequests;

class SiteController extends Controller {
    
    const INTERNET_BILLING_ID = 5;
    const KTV_BILLING_ID = 12;
    const PHONE_BILLING_ID = 31;
    const DOMRUTV_BILLING_ID = 53;
    
    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions() { 
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex() {
        if (\Yii::$app->user->isGuest) {
            return $this->redirect('login');
        }
        
        $model = new RequestForm();               
        
        if(!(Yii::$app->session->get('domain'))) Yii::$app->session->set('domain', Yii::$app->params['city']);
        $domain = Yii::$app->session->get('domain'); 
        
        $streets_raw = Yii::$app->erconsole->setCity($domain)->makeRequest('geography/streets');
        $streets = array();
        if(isset($streets_raw->success) && $streets_raw->success == 1) {
            foreach($streets_raw->result as $street) {
                $streets[$street->street_id] = $street->street_name;
            }        
        }

        $modelBilling = new BillingRequests();        
        $products = $this->_productsPrepare($modelBilling->getProducts());    
                 
        $js = <<< JS
            $('#requestform-house, #requestform-flat').change(function(){
                if($('#street-select').val() && $('#requestform-house').val() && $('#requestform-flat').val()){
                    $.post('/check-address', {
                        city: '{$domain}',
                        street: $('#street-select').val(),
                        house: $('#requestform-house option:selected').text(),
                        flat: $('#requestform-flat').val()
                    }).done(function(data) {
                        if(data){
                            $('#house-status').removeClass('text-warning').addClass('text-success');
                            $('#house-status').html('<strong>Подключен</strong>');
                            $('#house-status').attr("data-status", "enabled");
                        } else {
                            $('#house-status').removeClass('text-success').addClass('text-warning');
                            $('#house-status').html('<strong>Не подключен</strong>');
                            $('#house-status').attr("data-status", "disabled");
                        }
                    })
                }
            });
            $(document).ready(function() {    
                $('input[name="product"]:checked').click();
                if(typeof EYii.val !== 'undefined') {
                    $("#street-select").select2("data", {id: EYii.val.street_id, text: EYii.val.street_name});
                    $("#street-select").change();                    
                    setTimeout(function(){
                        $('#requestform-house').val(EYii.val.house_number);
                        $('#requestform-house, #requestform-flat').change();
                    }, 1000);
                }
            });
JS;
        $this->getView()->registerJs($js);

        $this->getView()->registerJsFile('//poll.domru.ru/api/v1/qRequest.js', ['position' => \yii\web\View::POS_END]);        
        $this->getView()->registerJs("var qReq = new qRequest('" . Yii::$app->session->get('domain') . "', '" . Yii::$app->user->identity->username . "', 'SaleAgent');
            qReq.init();
            qReq.setReminder(1);
            qReq.getRandom();",
            \yii\web\View::POS_END
        );
        
        $formData = Yii::$app->session->get('formData');
        if(isset($formData['street_name']))
            $this->getView()->registerJs("EYii.val=$.parseJSON('" . json_encode(array('street_id' => $formData['street'], 'street_name' => $formData['street_name'], 'house_number' => $formData['RequestForm']['house'])) . "')");
        
        return $this->render('index', [
                'model' => $model,
                'streets' => $streets,
                'products' => $products,
                'formData' => $formData['RequestForm']
            ]);
    }
    
    /**
     * Подготовка данных перед передачей в представление
     * @param array $data
     * @return array
     */
    private function _productsPrepare($data) {
        $data = isset($data['result']['ds_packages_sa_list']['parent']['rowset']['packages']) ? $data['result']['ds_packages_sa_list']['parent']['rowset']['packages'] : array();
        $js = $this->_dataToJS($data);
        
        $this->getView()->registerJs('packagesData=' . json_encode($js));
        
        $packages = $mono = array();    
        $this->_sortDataTypes($data, $mono, 1);
        $this->_sortDataTypes($data, $packages, 2);
                
        return array('packages' => $packages, 'mono' => $mono);
    }
    
    /**
     * Подготовка данных для вывода в JavaScript
     * @param array $data
     * @return array
     */
    private function _dataToJS ($data) {
        $result = array();
        foreach ($data as $package) { 
            $packageAttrs = $package['@attributes'];
            $productAttrs = array();
            
            foreach($package['product'] as $key => $product) {
                if($key === '@attributes') {
                    $index = (int)$product['product_id'];
                    $productAttrs[$index]['connect_plan_id'] = (int)$product['connect_plan_id'];
                    $productAttrs[$index]['connect_name'] = (string)$product['connect_name'];
                    $productAttrs[$index]['plan_id'] = (int)$product['plan_id'];
                    $productAttrs[$index]['plan_name'] = (string)$product['plan_name'];
                    $productAttrs[$index]['product_id'] = (int)$product['product_id'];
                    $productAttrs[$index]['price_connected'] = (int)$product['price_connected'];
                } else {        
                    $index = (int)$product['@attributes']['product_id'];
                    $productAttrs[$index]['connect_plan_id'] = (int)$product['@attributes']['connect_plan_id'];
                    $productAttrs[$index]['connect_name'] = (string)$product['@attributes']['connect_name'];
                    $productAttrs[$index]['plan_id'] = (int)$product['@attributes']['plan_id'];
                    $productAttrs[$index]['plan_name'] = (string)$product['@attributes']['plan_name'];
                    $productAttrs[$index]['product_id'] = (int)$product['@attributes']['product_id'];
                    $productAttrs[$index]['price_connected'] = (int)$product['@attributes']['price_connected'];
                }
            }
            
            $result[(int)$packageAttrs['id']] = array(
                'name' => (string)$packageAttrs['name'],
                'active_from' => (string)$packageAttrs['active_from'],
                'cost' => (int)$packageAttrs['cost'],
                'promo_months' => (int)$packageAttrs['promo_months'],
                'cost_after_promo_months' => (int)$packageAttrs['cost_after_promo_months'],
                'flag_id' => (int)$packageAttrs['flag_id'],
                'flag_name' => (string)$packageAttrs['flag_name'],
                'products' => $productAttrs,
                'show' => (int)$packageAttrs['show'],
            );
        }
        return $result;
    }
    
    /**
     * Сортировка пакетов по типу "2 в 1" или "3 в 1"
     * @param array $data
     * @param array $services
     * @param int $type
     */
    private function _sortDataTypes ($data, &$services, $type) {
        foreach ($data as $package) {
            $packagesAttributes = $package['@attributes'];
            $products = '<br>';
            foreach ($package['product'] as $key => $product) {
                if($key === '@attributes') {
                    $products .= ' &ndash; ' . $product['plan_name'] . '<br>';
                } else {
                    $products .= ' &ndash; ' . $product['@attributes']['plan_name'] . '<br>';
                }
            }
            if ($packagesAttributes['view_package'] == $type)
                $services[(int) $packagesAttributes['id']] = '<strong>' . $packagesAttributes['name'] . ($type > 1 ? " ({$type} в 1)" : '') . '</strong>' . $products;
        }
        if($type == 2) $this->_sortDataTypes($data, $services, ++$type);
    }
    
    public function actionLogin() {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();        
        $cities = $model->getCities();
        $items = array();
        
        foreach ($cities as $value) {
            $items[$value->domain] = $value->name;
            if(!empty($value->selected)) $model->city = $value->domain;            
        }        
        
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            if(isset(Yii::$app->request->post()['LoginForm']['city'])) {                
				Yii::$app->session->set('domain', Yii::$app->request->post()['LoginForm']['city']);
            } else {                 
				Yii::$app->session->set('domain', Yii::$app->params['city']);
			}
			
            return $this->goBack();
        } else {
            return $this->render('login', [
                'model' => $model,
                'cities' => $cities,
                'items' => $items
            ]);
        }
    }

    public function actionLogout() {
        Yii::$app->user->logout();

        return $this->goHome();
    }   

    public function actionConfirm() {
        if (\Yii::$app->user->isGuest) {
            return $this->redirect('login');
        }
        if(!isset($_POST) || empty($_POST) || !isset($_POST['RequestForm']['house'])) {
            return $this->redirect('/');
        }
        
        $post = Yii::$app->request->post();

        Yii::$app->session->set('formData', $post);
        
        $dataArray = array(
            'lastname' => $post['RequestForm']['lastname'],
            'firstname' => $post['RequestForm']['firstname'],
            'patronymic' => isset($post['RequestForm']['patronymic']) ? $post['RequestForm']['patronymic'] : '',
            'house' => $post['RequestForm']['house'],
            'flat' => $post['RequestForm']['flat'],
            'comment' => $post['RequestForm']['comment'],
            'telephone' => $this->_phoneFormatting($post['RequestForm']['telephone']),
            'addTelephone' => isset($post['RequestForm']['addTelephone']) ? $this->_phoneFormatting($post['RequestForm']['addTelephone']) : '',
            'agreementNumber' => isset($post['agreement']) && !empty($post['agreement'])  ? $post['RequestForm']['agreementNumber'] : null,
            'street' => $post['street'],
            'product' => $post['product'],
            'selectDevices' => isset($post['selectDevices']) ? $post['selectDevices'] : ''           
        );   
        
        if(isset($post['packages-data'])) {
            $packagesData = json_decode($post['packages-data']);
            $commonPrice = 0;
            foreach($packagesData->products as $item) {
                $commonPrice += $item->price_connected;
            }            
            $dataArray['packagesData'] = $packagesData;
            $dataArray['commonConnectPrice'] = $commonPrice;
        }
        if(isset($post['products-data'])) {
            $productsData = json_decode($post['products-data']);
            if(isset($productsData->ds_materials_sa_list->rowset->materials)) {
                $devices = array();
                if(is_array($dataArray['selectDevices'])) {
                    $materials = $productsData->ds_materials_sa_list->rowset->materials;
                    if(is_array($materials)) {
                        foreach($materials as $material) {
                            if(in_array($material->{'@attributes'}->id, $dataArray['selectDevices'])) {
                                $devices[] = (array)$material->{'@attributes'};
                            }
                        }
                    } else {
                        if(in_array($materials->{'@attributes'}->id, $dataArray['selectDevices'])) {
                            $devices[] = (array)$materials->{'@attributes'};
                        }
                    }
                }
            }
        }
        
        if(isset($devices)) {
            $dataArray['selectDevices'] = $devices;
            $commonPrice = 0;
            foreach($dataArray['selectDevices'] as $item) {
                $commonPrice += $item['cost_sale'];
            }            
            $dataArray['packagesData'] = $packagesData;
            $dataArray['commonDevicesPrice'] = $commonPrice;
        } else {
			$dataArray['selectDevices'] = array();
			$dataArray['commonDevicesPrice'] = false;
		}
        
        $rootPath = Yii::getAlias('@webroot');
        
        $this->getView()->registerCssFile(Yii::$app->assetManager->publish($rootPath . '/plugins/ladda-bootstrap/css/ladda-themeless.min.css')[1]);
        $this->getView()->registerCssFile(Yii::$app->assetManager->publish($rootPath . '/plugins/ladda-bootstrap/css/prism.css')[1]);
        $this->getView()->registerJsFile(Yii::$app->assetManager->publish($rootPath . '/plugins/ladda-bootstrap/js/spin.js')[1]);
        $this->getView()->registerJsFile(Yii::$app->assetManager->publish($rootPath . '/plugins/ladda-bootstrap/js/ladda.js')[1]);
        $this->getView()->registerJs('$(function(){Ladda.bind("#send-btn");});');

        return $this->render('confirm', array('data' => $dataArray));
    }
    
    protected function _phoneFormatting ($phone = '') {
        return preg_replace('/[^0-9]/', '', $phone); // '/[^0-9+]/ - оставить "+"
    }
    
    public function actionDummy(){
        if (\Yii::$app->user->isGuest) {
            return $this->redirect('login');
        }
        if(isset($_POST['street']) &&
           isset($_POST['house_num']) &&
           isset($_POST['office']) &&
           isset($_POST['client_name']) &&
           isset($_POST['client_phone']) &&
           isset($_POST['agr_pack_id'])                
        ) {
            
            Yii::$app->session->set('query', $_POST);

            \Yii::$app->view->registerMetaTag([
                        'http-equiv' => 'Refresh',
                        'content' => '1; url=http://saleagent.domru.ru/request'
                    ]);
        }
        
        return $this->render('request', array('code'=>'PARAM_IS_LOCKED', 'answer'=>array( 'error'=>0,
            'answer' => 'Идет создание договора. Подождите пожалуйста, в течение минуты договор будет создан')));
    }
    
    public function actionRequest() {
        if (\Yii::$app->user->isGuest) {
            return $this->redirect('login');
        }
        
        $result = array();
        $answer = array();
        
        $query = Yii::$app->session->get('query');
        
        $BillingRequests = new BillingRequests();
        $result = $BillingRequests->sendRequest($query);
        $answer = $result->result;  

        $status = isset($result->status) ? (int)$result->status : null;
        $code = isset($result->messages->code) ? (string)$result->messages->code : null;
        $mongo = new \Mongo('mongo.cluster');            
        $db = $mongo->logs;
        $collection = $db->saleagent_request;
        $query = \Yii::$app->billing->getQueries();

        $data = array(
                    "query" => end($query),
                    "result" => $result->asXML(),                        
                    "city" => \Yii::$app->session->get('domain'),                        
                    "date" => date("d/m G:i:s"),                       
                    "http_referer" => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
                    "timestamp" => time(),
                    "user_agent" => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
                );
        $collection->insert($data);

        $debug = $answer;

        if(isset($answer->ds_create_agr->rowset->rezult) and $status === 1) {
            $answer = (array)$answer->ds_create_agr->rowset->rezult->attributes()['reply'];                
            Yii::$app->session->set('request_answer', array('answer' => $answer[0], 'error' => false));      
        } elseif($status === 0 and $code !== 'PARAM_IS_LOCKED'){

            $answer = \Yii::$app->billing->getQueriesError();
            Yii::$app->session->set('request_answer', array('answer' => $answer, 'error' => 1));
            \Yii::error('Ошибка при отправке заявки: ');                
        } else{
            Yii::$app->session->set('request_answer', array('answer' => 'Идет создание договора. Подождите пожалуйста, в течение минуты договор будет создан', 'error' => false));
            \Yii::$app->view->registerMetaTag([
                'http-equiv' => 'Refresh',
                'content' => '1; url=' . $_SERVER["REQUEST_URI"]
            ]);
        }

        //if(session_id() !== '') @session_write_close();

        //$this->redirect($_SERVER["REQUEST_URI"]);
        
        
        return $this->render('request', array('answer' => Yii::$app->session->get('request_answer'), 'code' => $code));

    }
    
    /**
     * Получение оборудования
     * 
     * @street ID улицы
     * @house_num номер дома
     * @office номер квартиры
     * @house_build номер строения (опционально)
     * @material_to оборудование для: 1 - интернет, 2 - ДОМ.RU TV, 3 - 3 в 1
     * @flag_id РА (РА, которая привязана к пакету)
     * 
     * @return string
     */
    public function actionGetdevices() {
        if (Yii::$app->request->isAjax) {
            $modelBilling = new BillingRequests();
            
            $street = Yii::$app->request->post('street');
            $house_num = Yii::$app->request->post('house_num');
            $office = Yii::$app->request->post('office');
            $material_to = Yii::$app->request->post('material_to');
            $flag_id = Yii::$app->request->post('flag_id');
            $agr_pack_id = Yii::$app->request->post('agr_pack_id');
            $agreement_number = Yii::$app->request->post('agreement_number');
            $devices = array(); 
             
            // моно
            if(count($material_to) == 1) {       
                if (in_array(self::INTERNET_BILLING_ID, $material_to)) {
                    $devices = $modelBilling->getDevices($street, $house_num, $office, 1, $flag_id, $agr_pack_id, $agreement_number)['result'];
                } 
                if (in_array(self::KTV_BILLING_ID, $material_to)) {
                    $devices = $modelBilling->getDevices($street, $house_num, $office, 1, $flag_id, $agr_pack_id, $agreement_number)['result'];
                    // если нет интернета, то удалить список оборудования для него
                    foreach($devices['ds_materials_sa_list']['rowset']['materials'] as &$device) {
                        if ($device['@attributes']['product'] == self::INTERNET_BILLING_ID) unset($device['@attributes']['product']);
                    }
                } 
                if (in_array(self::DOMRUTV_BILLING_ID, $material_to)) {
                    $devices = $modelBilling->getDevices($street, $house_num, $office, 2, $flag_id, $agr_pack_id, $agreement_number)['result'];
                    // если нет интернета, то удалить список оборудования для него
                    foreach($devices['ds_materials_sa_list']['rowset']['materials'] as &$device) {
                        if ($device['@attributes']['product'] == self::INTERNET_BILLING_ID) unset($device['@attributes']['product']);
                    }
                }                
            }
            
            // 2 в 1
            if(count($material_to) == 2) {
                // телефон и Дом.ru TV - 2
                if (in_array(self::PHONE_BILLING_ID, $material_to) && in_array(self::DOMRUTV_BILLING_ID, $material_to)) {
                    $devices = $modelBilling->getDevices($street, $house_num, $office, 2, $flag_id, $agr_pack_id, $agreement_number)['result'];
                }
                // интернет и Дом.ru TV - 3
                if (in_array(self::INTERNET_BILLING_ID, $material_to) && in_array(self::DOMRUTV_BILLING_ID, $material_to)) {
                    $devices = $modelBilling->getDevices($street, $house_num, $office, 3, $flag_id, $agr_pack_id, $agreement_number)['result'];
                }
                // интернет и КТВ - 1
                if (in_array(self::INTERNET_BILLING_ID, $material_to) && in_array(self::KTV_BILLING_ID, $material_to)) {
                    $devices = $modelBilling->getDevices($street, $house_num, $office, 1, $flag_id, $agr_pack_id, $agreement_number)['result'];
                }
            }
            
            // 3 в 1
            if(count($material_to) == 3) {
                // интернет, КТВ и телефон - 3 (удалить список для Дом.ru TV)
                if (in_array(self::INTERNET_BILLING_ID, $material_to) && in_array(self::KTV_BILLING_ID, $material_to) && in_array(self::PHONE_BILLING_ID, $material_to)) {
                    $devices = $modelBilling->getDevices($street, $house_num, $office, 3, $flag_id, $agr_pack_id, $agreement_number)['result'];
                    foreach($devices['ds_materials_sa_list']['rowset']['materials'] as &$device) {
                        if ($device['@attributes']['product'] == self::DOMRUTV_BILLING_ID) unset($device['@attributes']['product']);
                    }
                }
                // интерент, Дом.ru TV и телефон - 3	
                if (in_array(self::INTERNET_BILLING_ID, $material_to) && in_array(self::DOMRUTV_BILLING_ID, $material_to) && in_array(self::PHONE_BILLING_ID, $material_to)) {
                    $devices = $modelBilling->getDevices($street, $house_num, $office, 3, $flag_id, $agr_pack_id, $agreement_number)['result'];
                }
            }
            
            echo Json::encode($devices);
        } else {
            return $this->redirect('/');
        }
    }
    
    public function actionGetpackages() {
        if (Yii::$app->request->isAjax) {
            $modelBilling = new BillingRequests();            
            $agreement_number = Yii::$app->request->post('agreement_number');
            
            $products = $modelBilling->getProducts($agreement_number);
            $packages = isset($products['result']['ds_packages_sa_list']['parent']['rowset']['packages']) ? $products['result']['ds_packages_sa_list']['parent']['rowset']['packages'] : array();
            $addendums = isset($products['result']['ds_packages_sa_list']['parent']['addendums']['addenda']) ? $products['result']['ds_packages_sa_list']['parent']['addendums']['addenda'] : array();
            unset($products);
            
            $packages = $this->_dataToJS($packages);
            
            if($addendums) {
                if(count($addendums) > 1) {
                    foreach($addendums as $addenda) {
                        $packages['addendums'][] = $addenda['@attributes'];
                    }                
                } else {
                    $packages['addendums'][] = $addendums['@attributes'];
                }
            }            
                       
            echo Json::encode($packages);
        } else {
            return $this->redirect('/');
        }
    }

    /**
     * Валидация номера договора в биллинге
     * @return string
     */
    public function actionCheckagreement() {
        if (Yii::$app->request->isAjax) {
            $modelBilling = new BillingRequests();
            
            $agreement_number = Yii::$app->request->post('agreement_number');
            $agr_pack_id = Yii::$app->request->post('agr_pack_id');
            
            echo Json::encode($modelBilling->Checkagreement($agreement_number, $agr_pack_id)['result']);
            
        } else
            return $this->redirect('/');
    }
    
    public function actionGetstreets($search = null, $id = null) {
        if(!$search) {
            echo Json::encode(array());
            return;
        }
        if (Yii::$app->request->isAjax) {
            $domain = Yii::$app->params['city'];			
			if (Yii::$app->session->has('domain')) {
				$domain = Yii::$app->session->get('domain');
			}			
			$streets_raw = Yii::$app->erconsole->setCity($domain)->makeRequest('geography/streets');
            $streets = array();
            if(isset($streets_raw->success) && $streets_raw->success == 1) {
                foreach($streets_raw->result as $street) {                    
                    if(mb_stripos($street->street_name, $search, 0, 'UTF-8') !== false) {                        
                        $streets['results'][] = array('id' => $street->street_id, 'text' => $street->street_name);
                    }
                }        
            }
            echo Json::encode($streets);
        } else
            return $this->redirect('/');
    }
    
    public function actionGethouses() {
        if (Yii::$app->request->isAjax) {            
            $out = [];
            if (isset($_POST['depdrop_parents'])) {
                $street_id = end($_POST['depdrop_parents']);
                $model = new BillingRequests();
                $list = $model->getHouses($street_id);         
                $selected  = null;
                if ($street_id != null && count($list) > 0) {
                    $selected = '';
                    foreach ($list as $house_id => $house_number) {
                        $out[] = ['id' => $house_number, 'name' => $house_number];       // 'id' => $house_id - для указания ID из биллинга        
                    }
                    echo Json::encode(['output' => $out, 'selected'=>$selected]);
                    return;
                }
            }
            echo Json::encode(['output' => '', 'selected'=>'']);
        } else
            return $this->redirect('/');
    }
    
    /**
     * Проверка адреса 
     * 
     * @return string JSON
     */
    public function actionCheckaddress() {        
        if (Yii::$app->request->isAjax) {
            $requestModel = new BillingRequests();
            $data = array();
            $data['city'] = Yii::$app->request->post('city');
            $data['street'] = Yii::$app->request->post('street');
            $data['house'] = Yii::$app->request->post('house');
            $data['office'] = Yii::$app->request->post('flat');            
            $r = $requestModel->checkAddress($data);
            $result = false;
            
            if ((isset($r->houses->house->int) && $r->houses->house->int == 1) ||
                    (isset($r->houses->house->gs) && $r->houses->house->gs == 1) ||
                    (isset($r->houses->house->ktv) && $r->houses->house->ktv == 1) ||
                    (isset($r->houses->house->cktv) && $r->houses->house->cktv == 1)) {
                $result = true;
            }
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result);
            return;
        } else
            return $this->redirect('/');
    }
    
}
