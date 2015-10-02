<?php
namespace app\extensions\ECurl;
  
use Yii;
use yii\caching\MemCache;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\base\Exception;

/**
 * Класс работы с http запросами
 *
 * @author Max Lanin
 */

class ECurlException extends Exception {
    public function __construct($message,$getThrowBillingException=true) {
        $name = isset(Yii::$app->user->identity->username) ? Yii::$app->user->identity->username : 'guest';
        $domain = isset(Yii::$app->params['cities'][Yii::$app->session->get('domain')]) ? Yii::$app->params['cities'][Yii::$app->session->get('domain')] : 'unknown';
        //Yii::log($message . ' || User: ' . $name . ' Billing_Domain: ' . $domain . ' City: ' . $city, 'error', 'BILLING_ERROR');
        \Yii::error($message . ' || User: ' . $name . ' Billing_Domain: ' . $domain);
		/*if($getThrowBillingException === true){
            throw new BillingException($message, 'XML_Parse_Errors');
        }*/
    }
}

class ECurl extends Model {
    
    protected $headers      = array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Connection: Keep-Alive',
        'Content-type: application/x-www-form-urlencoded;charset=UTF-8',
    );

    protected $userAget     = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0)';

    protected $timeout      = 30;
    protected $compression  = '';
    protected $cookies      = false;
    protected $proxy        = '';
    protected $auth         = '';

    public function __construct() {
        ;
    }

    public function setCompression() {
        $this->compression = 'gzip';
        return $this;
    }

    public function setTimeout($timeout) {
        $this->timeout = $timeout;
        return $this;
    }

    public function setHeaders($headers, $new = false) {
        if ($new) {
            $this->headers = $headers;
        } else {
            $this->headers += $headers;
        }
        return $this;
    }

    public function setUserAgent($userAget) {
        $this->userAget = $userAget;
        return $this;
    }

    public function setCookies($cookie_file) {
        if (file_exists($cookie_file)) {
            $this->cookie_file = $cookie_file;
        } else {
            fopen($cookie_file, 'w') or $this->error('The cookie file could not be opened. Make sure this directory has the correct permissions');
            $this->cookie_file = $cookie_file;
            fclose($this->cookie_file);
        }
        return $this;
    }

    public function setProxy($proxy) {
        $this->proxy = $proxy;
        return $this;
    }

    public function setAuth($user, $password) {
        $this->auth = $user . ':' . $password;
        return $this;
    }

    public function get($url, $forseFGC = false) {	
        if (extension_loaded('curl') && !$forseFGC) {
            $process = curl_init($url);

            curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
            curl_setopt($process, CURLOPT_HEADER, 0);
            curl_setopt($process, CURLOPT_USERAGENT, $this->userAget);

            if ($this->cookies) {
                curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
                curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
            }

            if ($this->auth) {
                curl_setopt($process, CURLOPT_USERPWD, $this->auth);
            }

            curl_setopt($process, CURLOPT_ENCODING , $this->compression);
            curl_setopt($process, CURLOPT_TIMEOUT, $this->timeout);

            if (!empty($this->proxy)) {
                curl_setopt($process, CURLOPT_PROXY, $this->proxy);
            }

            curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);

            $return = trim(curl_exec($process));

            if (curl_errno($process)) {
                throw new ECurlException('Error occured while requesting');
            }

            if (curl_getinfo($process, CURLINFO_HTTP_CODE) !== 200) {
                throw new ECurlException(curl_getinfo($process, CURLINFO_HTTP_CODE) . ' code catched while requesting ' . $url);
            }
            curl_close($process);
            return $return;
        } else {
            return file_get_contents($url);
        }
    }

    public function post($url, $data) {
        $process = curl_init($url);

        curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_USERAGENT, $this->userAget);

        if ($this->cookies) {
            curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
            curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
        }

        curl_setopt($process, CURLOPT_ENCODING, $this->compression);
        curl_setopt($process, CURLOPT_TIMEOUT, $this->timeout);

        if (!empty($this->proxy)) {
            curl_setopt($process, CURLOPT_PROXY, $this->proxy);
        }

        curl_setopt($process, CURLOPT_POSTFIELDS, $data);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($process, CURLOPT_POST, 1);

        $return = trim(curl_exec($process));

        if (curl_errno($process)) {
            throw new ECurlException('Error occured while requesting ' . $url . ': ' . curl_error($process) . '(' . curl_errno($process) . ')', curl_errno($process));
        }

        if (curl_getinfo($process, CURLINFO_HTTP_CODE) !== 200) {
            throw new ECurlException(curl_getinfo($process, CURLINFO_HTTP_CODE) . ' code catched while requesting ' . $url);
        }
        curl_close($process);

        return $return ;
    }
}