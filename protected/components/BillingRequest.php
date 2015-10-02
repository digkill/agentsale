<?php
namespace app\components; 
  
use Yii;
use yii\caching\MemCache;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\Exception;
use yii\base\Model;
use app\extensions\ECurl;

/**
 * Класс, реализующий интерфейс для общения с биллингом
 * пример получения данных с
 * https://testing.db.ertelecom.ru/cgi-bin/ppo/excells/web_cabinet.table_data?table_name$c=plan_prices&foreign_key$i=16721
 *
 * Обращение к компоненту
 *  Yii::$app->billing
 *           ->domain('perm') // смена домена
 *           ->useGET() // использовать метод get
 *           ->usePOST() // использовать метод post
 *           ->package('web_cabinet') // смена пакета
 *           ->alias('excells') // смена алиаса
 *           ->procedure('table_data') // смена процедуры
 *           ->data(array('table_name$c'=>'plan_prices','foreign_key$i' => '16721')) // массив данных
 * 			 ->offSetFlash() // отключаем вывод ошибки (setFlash) *           
 *           ->fire(); // выполнить запрос, вызывать после того как запрос сформирован, возвращает simpleXMLObject
 *  Yii::$app->billing->reset(); // метод reset сбрасывает все установленные настройки запроса и возвращает его в исходное состояние, return $this.
 *
 *  Yii::$app->billing->result;  // результат запроса simpleXMLObject (если не был вызван reset())
 *
 *  Yii::$app->billing->queries; // получить все запросы сделанные экземпляром класса.
 *
 *  Yii::$app->billing::convertXmlObjToArr($simpleXMLObject, &$arrayOut); //Конвертирует объект simple_xml в массив
 */
class BillingException extends Exception {

    public function __construct($message) {
        \Yii::error($message);
    }
    
    /*public function __construct($message) {
        $name = isset(Yii::$app->user->name) ? Yii::$app->user->name : 'guest';
        $domain = isset(Yii::$app->params['advanced']) && isset(Yii::$app->params['advanced']->billing_domain) ? Yii::$app->params['advanced']->billing_domain : 'unknown';
        $city = isset(Yii::$app->params['city']) ? Yii::$app->params['city'] : 'unknown';
        Yii::log($message . ' || User: ' . $name . ' Billing_Domain: ' . $domain . ' City: ' . $city, 'error', 'BILLING_ERROR');
    }*/

}

class BillingRequest extends Model {

    /**
     * Хранит класс запроса биллинга
     *
     * @var text
     */
    protected $_request = null;

    /**
     * Ответ биллинга (xml)
     *
     * @var text
     */
    protected $_rawAnswer = '';

    /**
     * Ответ биллинга (SimpleXMLElement)
     *
     * @var SimpleXMLElement
     */
    protected $answer = null;

    /**
     * Ответ биллинга (array)
     *
     * @var array
     */
    public $result = array();

    /**
     * Метод запроса
     *
     * @var string
     */
    public $method = 'POST';

    /**
     * Массив запросов
     * @var type
     */
    protected $queries = array();

    /**
     * Массив данных запроса по умолчанию умолчаний
     * @var type
     */
    public $default_query = array();

    /**
     * Массив данных запроса
     */
    private $query = array();
    public $debug;
    //Заменять ошибки из биллинга или выводить напрямую
    //true - если заменяем ошибки
    private $billingErrorDisabled = true;

    public function getBillingAnswer() {
        return $this->answer;
    }

    public function init() {
        parent::init();
        $this->connectCurl();
        $this->query = $this->default_query;
        //@todo зачем это, если аналогичная операция производится в конструкторе?
        /*if (isset(Yii::$app->params['advanced']->billing_domain)) {
            $this->query['domain'] = Yii::$app->params['advanced']->billing_domain;
        }*/
    }

    /**
     * Подключение Curl
     */
    protected function connectCurl() {
        //Yii::import('application.extensions.ECurl.ECurl');
        require_once( dirname(__FILE__) . '/../extensions/ECurl/ECurl.php');        
        $this->_request = new \app\extensions\ECurl\ECurl;
    }
    

// Использовать GET
    public function useGET() {
        $this->method = 'GET';
        return $this;
    }

// Использовать POST
    public function usePOST() {
        $this->method = 'POST';
        return $this;
    }

    /**
     * Смена домена
     */
    public function domain($domain) {
        $this->query['domain'] = $domain;
        return $this;
    }

    /**
     * Смена алиаса
     */
    public function alias($alias) {
        $this->query['alias'] = $alias;
        return $this;
    }

    /**
     * Смена процедуры
     */
    public function procedure($proc) {
        $this->query['procedure'] = $proc;
        return $this;
    }

    /**
     * Смена пакета
     */
    public function package($pack) {
        $this->query['package'] = $pack;
        return $this;
    }

    /**
     * данные
     */
    public function data($data) {
        $this->query['data'] = $data;
        return $this;
    }
    

    public function setBillingErrorDisabled($state = true) {
        $this->billingErrorDisabled = $state;
        return $this;
    }

    /**
     * Совершает запрос к базе данных
     * @param bolean $xml - true - вернет xml false вернет массив
     * @param array $query - можно передать массив запроса напрямую
     * @return array or xmlObject
     */
    public function fire($xml = true, $query = NULL) {
        
        // массив запроса
        $query = isset($query) ? $query : $this->query;
        // генерация запроса для curl
        $input = $this->genQuery($query);

        // запись в массив запросов
        $this->queries[] = '[' . $this->method . '] ' . $input['url'] . '?' . $input['data'];
        
        $this->fireQuery($input);

        //Логирование пустые результаты
        if (empty($this->result)) {
            /*if (isset($query['data']['params'])) {
                Yii::log('Для параметра: ' . $query['data']['params'] . '. Пришел пустой результат.', CLogger::LEVEL_ERROR, 'BILLING_ERROR');
            } else {
                Yii::log('В запросе: ' . $input['url'] . ' пришел пустой результат.', CLogger::LEVEL_ERROR, 'BILLING_ERROR');
            }*/
        }

        if (!$xml && !empty($this->result)) {
            $arr = array();
            $this->convertXmlObjToArr($this->result, $arr);
            return $arr;
        }

        return $this->result;
    }

    /**
     * Совершает запрос к базе данных, возвращает весь объект
     * @param bolean $xml - true - вернет xml false вернет массив
     * @param array $query - можно передать массив запроса напрямую
     * @return object
     */
    public function query($xml = true, $query = NULL) {
        $this->fire($xml, $query);
        return $this;
    }

    /**
     * Выполняет запрос
     * @param mixed $query
     */
    protected function fireQuery($query) {
        try {

            switch ($this->method) {
                case 'GET':
                    $this->_rawAnswer = $this->_request->get($query['url'] . '?' . $query['data']);
                    break;

                case 'POST':
                    $this->_rawAnswer = $this->_request->post($query['url'], $query['data']);
                    break;

                default:
                    break;
            }

            //$this->_rawAnswer = false;
            if ($this->_rawAnswer === false) {
                throw new BillingException('Произошла ошибка при попытке получить данные с ' . $query['url'] . '?' . $query['data']);
            }

            //Ищем по ответу биллинга ORA-
            if ($this->hasOra($this->_rawAnswer)) {
                /*Yii::app()->user->setFlash(
                        MESSAGE_TYPE_ERROR, 'Произошла ошибка. Пожалуйста, попробуйте позднее. При повторении ошибки обратитесь в техническую поддержку.'
                );*/
                throw new BillingException('ORA DETECTED. URL: ' . $query['url'] . '?' . $query['data'] . ' data: ' . $this->_rawAnswer);
            }

            libxml_use_internal_errors(true);
            $this->answer = simplexml_load_string($this->_rawAnswer);
            $xmlErrors = libxml_get_errors();

            if ($this->answer !== false && empty($xmlErrors)) {
                //$this->result = $this->answer;
                // если статус 0 то возвращаем пустой массив !Ёатататта палить огнем, переделать
                if (intval($this->answer) !== false) {
                    $this->result = $this->answer;
                    
                }
                return $this->result;
            } else {
                //$status = false;
                $XMLparseErrors = "Ошибки парсинга XML :" . chr(13);
                foreach ($xmlErrors as $error) {
                    $XMLparseErrors .= $error->message . chr(13);
                }
                libxml_clear_errors();
                throw new BillingException($XMLparseErrors . 'Data: ' . $this->_rawAnswer . 'Query: ' . end($this->queries));
            }
        } catch (BillingException $exc) {
            $this->result = false;
//            $status = false;
//            $this->errno = $exc->getCode();
//            $this->error = $exc->getMessage();
        }
//        if ($status === false) {
//            $this->result = false;
//            throw new CException($this);
//        }
    }

    /**
     * Генерирует запрос
     * @param array $query
     * @return string
     */
    protected function genQuery($query) {
        $data = '';
        if (isset($query['data']) && is_array($query['data'])) {
            $data = $this->genDataForQuery($query['data']);
        }

        $domain = $query['domain'];

        if (isset($query['package']) && isset($query['procedure'])) {
            $call = $query['package'] . '.' . $query['procedure'];
        } else {
            $call = isset($query['procedure']) ? $query['procedure'] : '';
        }

        return array(
            'url' => str_replace('{domain}', $domain, $query['url']) . $query['alias'] . '/' . $call,
            'data' => $data,
        );
    }

    /**
     * Генерирует данные для запроса
     * @param array $data
     * @return array
     */
    protected function genDataForQuery($data) {
        $tmp = array();
        foreach ($data as $key => $value) {
            if ($value != '') {
                if (is_array($value)) {
                    $_tmp = array();
                    foreach ($value as $_key => $_value) {
                        $_tmp[] = $key . '=' . urlencode($_value);
                    }
                    $tmp[] = join('&', $_tmp);
                } else {
                    $tmp[] = $key . '=' . urlencode($value);
                }
            }
        }
        return join('&', $tmp);
    }

//    protected function parseAnswer() {
//        $this->result = array();
//        $this->convertXmlObjToArr($this->answer, $this->result);
//        return true;
//    }

    /**
     * Конвертирует объект simple_xml в массив
     * @param SimpleXMLElement $obj
     * @param array $arr
     */
    public function convertXmlObjToArr($obj, &$arr) {
        $children = $obj->children();
        $executed = false;

        foreach ($children as $elementName => $node) {
            if ((is_array($arr) || is_object($arr)) && array_key_exists($elementName, $arr)) {
                if (array_key_exists(0, $arr[$elementName])) {
                    $i = count($arr[$elementName]);
                    foreach ($node->attributes() as $attrName => $attribute) {
                        $arr[$elementName][$i][$attrName] = trim(strval($attribute));
                    }

                    $this->convertXmlObjToArr($node, $arr[$elementName][$i]);
                } else {
                    $tmp = $arr[$elementName];
                    $arr[$elementName] = array();
                    $arr[$elementName][0] = $tmp;
                    $i = count($arr[$elementName]);
                    foreach ($node->attributes() as $attrName => $attribute) {
                        $arr[$elementName][$i][$attrName] = trim(strval($attribute));
                    }
                    $this->convertXmlObjToArr($node, $arr[$elementName][$i]);
                }
//$arr[$elementName][$i]['_value'] = trim(strval($node));
            } else {
                $arr[$elementName] = array();
                foreach ($node->attributes() as $attrName => $attribute) {
                    $arr[$elementName][$attrName] = trim(strval($attribute));
                }
                $this->convertXmlObjToArr($node, $arr[$elementName]);
//$arr[$elementName]['_value'] = trim(strval($node));
            }

            $executed = true;
        }

        foreach ($obj->attributes() as $attrName => $attribute) {
            $arr[$attrName] = trim(strval($attribute));
        }

        if ($children->getName() == "") {
            if (empty($arr)) {
                $arr = (string) $obj;
            }
        }
    }

//    /**
//     * Возвращает результат
//     * @return array
//     */
//    public function fetchAll() {
//        return $this->result;
//    }

    /**
     * Сбрасывает адаптер в дефолт
     */
    public function reset() {
        $this->queries = array();
        $this->query = $this->default_query;
        $this->query['domain'] = Yii::$app->params['advanced']->billing_domain;
        $this->_rawAnswer = '';
        $this->answer = null;
        $this->result = array();

        return $this;
    }

    /**
     * Возвращает ответ биллинга
     * @return SimpleXMLElement
     */
    public function getAnswer() {
        return $this->answer;
    }

    /**
     * Возвращает набор произведенных запросов
     * @return array
     */
    public function getQueries() {
        return $this->queries;
    }

    /**
     * Возвращает набор произведенных запросов
     * @return array
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * Возвращает читаемое описание ошибки
     * @param string $code - код ошики из биллинга
     */
    private function getError($code = '') {

        // выход если код равен -11 (истекла сессия в биллинге, либо неверный токен)
        // вторым исключаем тех кто еще не авторизован
        // третьим условием исключаем выход при запросе аутентификации по токену, чтобы не выкинуть пользователя если он залогинен
        if ($code == '-11' && !Yii::$app->user->isGuest && !Yii::$app->request->getQuery('token', false)) {
            Yii::$app->user->logout(false);
            Yii::$app->request->redirect(Yii::$app->user->loginUrl);
        }
        
        return strval($this->answer->messages->text);
    }

    /**
     * Проверяем, есть ли ORA (Oracle Exception) в ответе
     * 
     * TRUE, если есть и отправить письмо администрации, иначе FALSE
     * 
     * 
     * @param type $string
     * @return boolean
     */
    protected function hasOra($string) {

        if (strpos($string, '<code>UNKNOWN</code>')) {
            return TRUE;
        }

        //Проверяем ORA только в теге text
        $mas = split('<text>', $string);
        if (isset($mas[1])) {
            $textTagArr = split('</text>', $mas[1]);
            if (isset($textTagArr[0])) {
                $textTag = $textTagArr[0];
                Yii::trace($textTag);
                if (strpos($textTag, 'ORA-')) {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

}