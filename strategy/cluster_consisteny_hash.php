<?php
/**
 * 一致性hash集群策略
 * @example:
 *  $this->_clusters = array(
 *      '127.0.0.1:11212',
 *      '127.0.0.1:11213'
 *  );
 *
 *  $this->_cluster_groups = array(
 *      # 读缓存服务器池
 *      '分组名称' => array(
 *          '127.0.0.1:11212',
 *          '127.0.0.1:11213'
 *       ),
 *
 *      # 如果是写缓存服务器, 推荐使用一个作为写服务.
 *      '分组名称' => '127.0.0.1:11211'
 *  );
 *
 * @author lucasho
 * @created 2016-05-23
 * @modified 2016-05-23
 */
class ClusterConsistentHash implements ICacheCluster, ICacheClusterGroup {
    public function __construct(&$cacheEngine) {
        $this->_cache_engine = $cacheEngine;
    }

    /**
     * @see ICacheCluster::connect
     */
    public function connect() {
        list($this->_cache_engine->host, $this->_cache_engine->port) = explode(':', $this->_clusters[$this->_hash()]);

        $this->_cache_engine->connect();
    }

    /**
     * @see ICacheCluster::fetchServer
     */
    public function fetchServer($key) {
        return $this->_clusters[$key];
    }

    /**
     * @see ICacheCluster::addServer
     */
    public function addServer($value) {
        array_push($this->_clusters, $value);
        ++$this->_cluster_number;
    }

    /**
     * @see ICacheCluster::addMultiServer
     */
    public function addMultiServer($servers = array()) {
        if(!empty($servers)) {
            foreach($servers as $value) {
                array_push($this->_clusters, $value);
                ++$this->_cluster_number;
            }
        }
    }

    /**
     * @see ICacheCluster::deleteServer
     */
    public function deleteServer($key) {
        if(isset($this->_clusters[$key])) {
            unset($this->_clusters[$key]);
            --$this->_cluster_number;
        }
    }

    /**
     * 获取集群配置数量
     */
    public function fetchClusterNumber() {
        return $this->_cluster_number;
    }

    /**
     * @see ICacheClusterGroup::fetchGroup
     */
    public function fetchGroup($name) {
        return isset($this->_cluster_groups[$name]) ? $this->_cluster_groups[$name] : false;
    }

    /**
     * @see ICacheClusterGroup::addGroup
     */
    public function addGroup($group, $value = null) {
        if(empty($this->_cluster_groups[$group])) {
            $this->_cluster_groups[$group] = $value;
        }
    }

    /**
     * @see ICacheClusterGroup::modifyGroup
     */
    public function modifyGroup($group, $value = null) {
        if(empty($value)) return false;

        if(!is_array($value)) {
            $this->_cluster_groups[$group] = $value;
        }
        else {
            foreach ($value as $k => $v) {
                $this->_cluster_groups[$group][$k] = $v;
            }
        }
    }

    /**
     * @see ICacheClusterGroup::deleteGroup
     */
    public function deleteGroup($group) {
        if(isset($this->_cluster_groups[$group])) {
            unset($this->_cluster_groups[$group]);
        }
    }

    /**
     * @see ICacheClusterGroup::appendServerToGroup
     */
    public function appendServerToGroup($group, $key, $value) {
        if(!isset($this->_cluster_groups[$group][$key])) {
            $this->_cluster_groups[$group][$key] = $value;
        }
    }

    /**
     * @see ICacheClusterGroup::deleteServerFromGroup
     */
    public function deleteServerFromGroup($name, $key) {
        if(isset($this->_cluster_groups[$name][$key])) {
            unset($this->_cluster_groups[$name][$key]);
        }
    }

    /**
     * 随机获取缓存节点
     */
    protected function _hash() {
        $randMD5 = md5(mt_rand());
        $hex = bin2hex($randMD5);
        $count = 0;
        for($i=0; $i < 32; $i++) {
            $count += mb_substr($hex, $i*2, 2);
        }

        return ($count*1)%$this->_cluster_number;
    }

    /**
     * 缓存对象
     * @var null $_cache_engine
     */
    protected $_cache_engine = null;

    /**
     * 集群配置
     * @var array $_clusters
     */
    protected $_clusters = array();

    /**
     * 集群分组配置
     * @var array $_cluster_groups
     */
    protected $_cluster_groups = array();

    /**
     * 集群数量
     * @var int $_cluster_number
     */
    protected $_cluster_number = 0;

    /**
     * 当前使用分组名称
     * @var null $_curr_group_name
     */
    protected $_curr_group_name = null;
}