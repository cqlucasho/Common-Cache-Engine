<?php
# 加载缓存引擎接口类
require_once('class/a_cache_engin.php');

/**
 * memcache_engine 提供Memcache缓存引擎, 提供Memcache缓存引擎解决方案
 *
 * @author    lucasho
 * @created   2015-09-15
 * @modified  2016-05-25
 * @version 1.0
 * @link http://www.iamhby.com
 */
class MemcacheEngine extends ACacheEngin {
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
        # 初始化缓存对象
        $this->initialCache(new Memcache());

        parent::__construct($host, $port, $flag, $clusterStrategy);
    }

    /**
     * @see ICache::write
     */
    public function write($name, $value, $expire = 1800) {
        $this->_storage_name = $name;
        $this->_selectServerAndSet();

        return $this->_cache_obj->add($name, $value, $this->flag, $expire);
    }

    /**
     * @see ICache::read
     */
    public function read($name) {
        $this->_storage_name = $name;
        $this->_selectServerAndSet();

        return $this->_cache_obj->get($name);
    }

    /**
     * 选择服务器并连接到选择服务器
     */
    protected function _selectServerAndSet() {
        $groupSign = !empty($this->_cluster_strategy['group']) ? true : false;

        switch ($this->fetchClusterStrategy()) {
            case self::CLUSTE_CONSISTENY_HASH:
                $this->_clusterConsistenyHash($groupSign);
                break;
            default:
                break;
        }
    }

    /**
     * 连接到hash一致性策略集群
     *
     * @param mixed $value      存储值
     * @param bool $groupSign   组标识
     * @return mixed
     * @throws Exception
     */
    protected function _clusterConsistenyHash(&$groupSign) {
        if(is_null($clusterObj = $this->fetchClusterObj())) {
            # 计算出值的hash, 并得到节点.
            $nodeNumber = $clusterObj->fetchValueHash($this->_storage_name, $groupSign);
            # 设置对应节点
            $clusterObj->setCurrNode($nodeNumber);
            # 连接到对应服务器
            $clusterObj->connect();
        }

        throw new Exception('cluster object is null.');
    }

    /**
     * 缓存存储值
     * @var mixed $_storage_value
     */
    protected $_storage_name = null;
}
