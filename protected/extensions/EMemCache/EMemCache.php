<?php
namespace app\extensions\EMemCache;
  
use Yii;
use yii\caching\MemCache;
use yii\base\InvalidConfigException;
/**
 * @author oborin oborin.i@ertelecom.ru 
 * 
 * переопредение методов  set get flush
 * таким образом, что к ключу дописывается (в конец) некий id, который тоже хранится в кэше.
 * При вызове метода flush, как таковой кэш не очищается, а просто изменяется этот самый id.
 * Тем самым, мы по существующим ключам не получим данных и они сгенерируются заново и запишутся в кэш под новым id.
 * 
 * Этакий псевдо сброс кэша.
 * 
 */
class EMemCache extends MemCache {
    // ключ по которому хранится префикс текущих ключей кэша
    private $cacheId;
    // суффикс определяющий принадлежность данных 
    private $suffix = '_data';
    // параметр определяющий тип кэша
    public $isSessionCache = false;
    // параметр определяющий время жизни кэша
    public $lifeTime = 0;

    
    
    /**
     * Инициализация, определение какой кэш будет использоваться.
     */
    public function init() {
        parent::init();
        if ($this->isSessionCache) {
            $this->suffix = '_session';
        }
        // ключ в котором хранится текущее значение суффикса кэша
        $this->cacheId = $this->keyPrefix . $this->suffix;
    }
    
    /**
     * SET
     */
    public function set($key, $value, $expire = 0, $dependency = NULL) {
        $expire = ($expire == 0) ? 0 : $this->lifeTime;
        $currentCacheId = $this->_getCurrentCacheId();
        //echo $key . '_' . $currentCacheId . $this->suffix;
        return parent::set($key . '_' . $currentCacheId . $this->suffix, $value, $expire, $dependency);
    }
    
    /**
     * GET
     */
    public function get($key) {
        
        $currentCacheId = $this->_getCurrentCacheId();
        //echo '<br>1 ' . $key . '_' . $currentCacheId . $this->suffix;
        return parent::get($key . '_' . $currentCacheId . $this->suffix);
    }
    
    /**
     * FLUSH
     */
    public function flush() {

        $currentCacheId = $this->_getCurrentCacheId();

        $return = parent::set($this->cacheId, $currentCacheId + 1);
        if ($return !== false) {
            Yii::log('Flushing ' . $this->suffix . ' cache', 'info', get_class($this));
            Yii::app()->user->setFlash(MESSAGE_TYPE_INFO, 'Кэш ' . $this->suffix . ' очищен');
        }
        return $return;
    }
    /**
     * Возвращает текущее значение суффиксов ключей кэша
     * @return int
     */
    private function _getCurrentCacheId() { 
        
        $currentCacheId = parent::get($this->cacheId);

        if ($currentCacheId === false) {
            $currentCacheId = 0;
            parent::set($this->cacheId, $currentCacheId);
        }
        return $currentCacheId;
    }

}
