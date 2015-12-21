<?php
namespace app\components;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\Exception;
/**
 * Класс реализует взаимодействие с ErConsole
 * @author Ланин Максим <lanin.me>
 * @author Штин Антон <anthony.shtin@gmail.com> Yii портирование
 *
 * Использование:
 * Yii::$app->erconsole->makeRequest('geography/cities/about');
 *
 * Для эльфов:
 *  try {
 *    // Инициализируем объект ApiClient
 *    $apiObject = new ApiClient('perm', array('user'   => 'admin', 'api_id' => '123'));
 *    // Делаем запрос к АПИ
 *    $request = $apiObject->makeRequest('get/something', array('id' => array(3, 13, 78)), 'GET', 86400);
 *  } catch (CException $exc) {
 *    print $exc->getMessage();
 * } */
class ApiClientException extends Exception {

    public function __construct($message) {
        //Yii::log($message, 'error', 'ERCONSOLE_ERROR');
    }

}

class ErConsoleApiClient extends Component {

    public $_auth = array('user' => '', 'api_id' => '');
    public $_city = '';
    // private $_resourceTemplate = 'http://{domain}console.testing.ertelecom.ru/api';
    private $_resourceTemplate = 'http://{domain}console.ertelecom.ru/api';
    private $_resource = '';
    private $_url = '';
    private $_method = '';
    private $_params = array();
    private $_requestType = 'GET';
    private $_memcache = null;
    private $_type = 'memcached';
    private $_cacheTime = 0;
    private $counter = 0;

    /**
     * Инициализация
     * устанавливаем кеш
     * устанавливаем адрес для общения с консолью
     */
    public function init() {
        parent::init();
        $this->setCache();
        $this->setCity(Yii::$app->session->get('domain'));
    }

    /**
     * устаналвиваем город
     * @param type $city
     */
    public function setCity($city = '') {
        $this->_city = $city;
        $this->setResource($this->_city);
        return $this;
    }

    /**
     * устанвлаиваем адрес для общения с Er-console
     * @param type $city
     */
    private function setResource($city = '') {

        if (!empty($city)) {
            $this->_resource = str_replace('{domain}', $city . '.', $this->_resourceTemplate);
        } else {
            $this->_resource = str_replace('{domain}', '', $this->_resourceTemplate);
        }

        return $this;
    }

    /**
     * устаналвиваем параметры авторизации
     * @param array $_auth
     */
    public function setAuth($auth = array('user' => '', 'api_id' => '')) {
        $this->_auth = $auth;
        return $this;
    }

    /**
     * Устанавливает кэширование в memcache
     * @throws CException
     */
    public function setCache() {
        $this->_memcache = Yii::$app->cache;
    }

    /**
     * Совершает запрос
     * @param array $params
     *      Параметры запроса
     * @param string $requestType
     *      Тип запроса GET/POST. По умолчанию GET
     * @param integer $cacheTime
     *      Время жизни кэша запроса
     * @return stdClass
     * @throws CException
     */
    public function makeRequest($method = '', array $params = array('id' => array(3, 13, 78)), $requestType = 'GET', $cacheTime = 86400) {

        $this->_method = $method;
        $this->_params = $params;
        $this->_requestType = $requestType;
        $this->_cacheTime = $cacheTime;
        $this->_url = $this->_resource . '/' . $this->_method;

        return $this->_makeRequest();
    }

    /**
     * Совершает запрос
     * @return stdClass
     * @throws CException
     */
    private function _makeRequest() {
        // Ищем в кэше
        if (!is_null($this->_memcache) && $this->_cacheTime != 0) {
            $key = md5($this->_url . '?' . serialize($this->_params));
            $result = $this->_memcache->get($key);

            if ($result !== false) {
                return $result;
            }
        }
        // запрос к консоли
        try {
            $this->_prepareRequest();

            // Делаем запрос
            if (extension_loaded('curl')) {
                $result = $this->_curl();
            } else {
                $result = $this->_fgc();
            }

            //Yii::log('Query: [' . $this->_requestType . '] ' . $this->_url . '?' . $this->_params, 'info', 'application.components.ErConsoleApiClient');

            $result = json_decode($result);

            if (is_null($result) || $result === false) {
                throw new ApiClientException('Query: [' . $this->_requestType . '] ' . $this->_url . '?' . $this->_params . chr(13) . ' ErConsole JSON is invalid.');
            }

            // Сохраняем в кэш
            if (!is_null($this->_memcache) && $this->_cacheTime != 0) {

                switch ($this->_type) {
                    case 'memcache':
                        $compress = is_bool($result) || is_int($result) || is_float($result) ? false : MEMCACHE_COMPRESSED;
                        $this->_memcache->set($key, $result, $compress, $this->_cacheTime);
                        break;
                    case 'memcached':
                    default:
                        $this->_memcache->set($key, $result, $this->_cacheTime);
                        break;
                }
            }
        } catch (ApiClientException $exc) {
            $result = false;
        }

        return $result;
    }

    /**
     * Подготавливает строку запроса, составляет токен итд
     */
    private function _prepareRequest() {
        // Собираем параметры
        foreach ($this->_params as &$param) {
            if (is_array($param)) {
                $param = join(',', $param);
            }
            $param = trim($param);
        }

        // Подготоавливаем аутентификацию
        $this->_params['timestamp'] = time();
        if (!empty($this->_auth)) {
            $this->_params['user'] = $this->_auth['user'];
            $params = $this->_params;
            ksort($params);

            $token = '';
            foreach ($params as $key => $val) {
                $token .= $key . $val;
            }

            $this->_params['token'] = md5($token . $this->_auth['api_id']);
        }

        // Собираем запрос
        foreach ($this->_params as $key => &$value) {
            $value = $key . '=' . urlencode($value);
        }

        $this->_params = join('&', $this->_params);
    }

    /**
     * Совершает запрос посредством CURL
     * @return string
     * @throws CException
     */
    private function _curl() {
        $ch = curl_init();

        if ($this->_requestType == 'POST') {
            $ch = curl_init($this->_url);
            curl_setopt($ch, CURLOPT_URL, $this->_url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_params);
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            curl_setopt($ch, CURLOPT_URL, $this->_url);
            $ch = curl_init($this->_url . '?' . $this->_params);
        }

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_USERAGENT, 'domru-lk');

        if (isset($_SERVER['HTTP_REFERER'])) {
            curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
        }

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new ApiClientException('Error occured while requesting ' . $this->_url . '?' . $this->_params . ': ' . curl_error($ch) . '(' . curl_errno($ch) . ')');
        }

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
            throw new ApiClientException(curl_getinfo($ch, CURLINFO_HTTP_CODE) . ' code catched while requesting ' . $this->_url . '?' . $this->_params);
        }

        curl_close($ch);
        return $result;
    }

    /**
     * Совершает запрос посредством file_get_contents()
     * @return string
     */
    private function _fgc() {
        return file_get_contents($this->_url . '?' . $this->_params);
    }

}
