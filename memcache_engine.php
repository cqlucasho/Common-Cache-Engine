<?php
# 加载缓存引擎接口类
require_once('i_cache.php');

/**
 * memcache_engine 提供Memcache缓存引擎, 提供Memcache缓存引擎解决方案
 *
 * @author    lucasho
 * @created   2015-09-15
 * @modified  2015-09-15
 */
class MemcacheEngine implements ICacheEngin {
    /**
     * 初始化
     *
     * @param string $host  主机地址
     * @param int $port     端口
     * @param string $flag  标识
     * @param array $cluster 是否使用集群
     *  @example:
     *      array(
     *          'strategy' = '集群策略名称', 'group' => '集群分组名称, 如果为空表示不使用分组.'
     *      )
     * @throws Exception
     */
    public function __construct($host = null, $port = null, $flag = null, $clusterStrategy = null) {
        $this->_memcache = new Memcache();
        $this->host = isset($host) ? $host : $this->host;
        $this->port = isset($port) ? $port : $this->port;
        $this->flag = isset($flag) ? $flag : $this->flag;
        $this->_cluster_strategy = $clusterStrategy;

        if(isset($this->_cluster_strategy)) {
            $this->cluster();
        }
        else {
            $this->connect();
        }
    }

    /**
     * 连接缓存服务器
     *
     * @throws Exception
     */
    public function connect($isCluster = false) {
        if (!$this->_memcache->connect($this->host, $this->port, $this->flag)) {
            $this->_tryMaxConnect($isCluster);
        }

        return true;
    }

    /**
     * @see ICache::write
     */
    public function write($name, $value, $expire = 1800) {
        return $this->_memcache->add($name, $value, $this->flag, $expire);
    }

    /**
     * @see ICache::read
     */
    public function read($name) {
        return $this->_memcache->get($name);
    }

    /**
     * @see ICache::delete
     */
    public function delete($name) {
        return $this->_memcache->delete($name);
    }

    /**
     * @see ICache::flush
     */
    public function flush() {
        return $this->_memcache->flush();
    }

    /**
     * @see ICacheEngin::cluster
     */
    public function cluster() {
        switch ($this->_cluster_strategy['strategy']) {
            case self::CLUSTE_CONSISTENY_HASH:
                require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'strategy'.DIRECTORY_SEPARATOR.'cluster_consisteny_hash.php');
                $this->_cluster = new $this->_cluster_strategy['strategy']($this);
                break;
            default:
                break;
        }
    }

    /**
     * 获取当前策略对象
     *
     * @return object
     */
    public function fetchClusterObj() {
        return $this->_cluster;
    }

    /**
     * 析构方法，自动关闭缓存实例。
     */
    public function __destruct() {
        if ($this->_memcache) {
            $this->_memcache->close();
        }
    }

    /**
     * 最大尝试连接.
     * 如果不是集群, 间隔10秒后, 再次请求连接.
     *
     * @param $isCluster
     * @throws Exception
     */
    protected function _tryMaxConnect(&$isCluster) {
        if($this->_try_max_connect < 3) {
            ++$this->_try_max_connect;

            if(!$isCluster) {
                sleep(10);
                $this->connect();
            }
            else {
                return false;
            }
        }
        else {
            throw new Exception('MemcacheEngine: connection failed.');
        }
    }

    /**
     * @var string 主机服务器地址，默认为127.0.0.1。
     */
    public $host = '127.0.0.1';

    /**
     * @var int 主机端口地址，默认为11211。
     */
    public $port = 11211;

    /**
     * @var int 缓存标记。
     */
    public $flag = MEMCACHE_COMPRESSED;

    /**
     * @var bool|Memcache 缓存处理实例。
     */
    protected $_memcache = false;

    /**
     * 尝试最大连接数
     * @var int $_try_max_connect
     */
    private $_try_max_connect = 3;

    /**
     * 策略对象
     * @var object $_cluster
     */
    protected $_cluster = null;

    /**
     * 当前使用的集群策略
     * @var array $_cluster_strategy
     */
    protected $_cluster_strategy = null;

    /**
     * 集群策略
     */
    const CLUSTE_CONSISTENY_HASH = 'ClusterConsistentHash';
    const CLUSTE_GOSSIP = 'ClusterGossip';
}
