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
 * @modified 2016-05-25
 * @version 1.0
 * @link http://www.iamhby.com
 */
class ClusterConsistentHash implements ICacheCluster, ICacheClusterGroup {
    public function __construct(&$cacheEngine) {
        $this->_cache_engine = $cacheEngine;
    }

    /**
     * @see ICacheCluster::connect
     */
    public function connect() {
        if(($currNode = $this->_fetchByGroup()) === false) {
            throw new Exception('connection failed that clusters or cluster groups is empty.');
        }

        # 如果连接失败, 则按顺时针选择下一台服务器作为连接对象
        if(!$this->_cache_engine->invokeConnect(true)) {
            $this->_nextNode($currNode);
        }
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
        ++$this->_node_number;
    }

    /**
     * @see ICacheCluster::addMultiServer
     */
    public function addMultiServer($servers = array()) {
        if(!empty($servers)) {
            foreach($servers as $value) {
                array_push($this->_clusters, $value);
                ++$this->_node_number;
            }
        }
    }

    /**
     * @see ICacheCluster::deleteServer
     */
    public function deleteServer($key) {
        if(isset($this->_clusters[$key])) {
            unset($this->_clusters[$key]);
            --$this->_node_number;
        }
    }

    /**
     * @see ICacheClusterGroup::fetchGroup
     */
    public function fetchGroup($name) {
        return isset($this->_cluster_groups[$name]) ? $this->_cluster_groups[$name] : false;
    }

    /**
     * @see ICacheClusterGroup::setCurrentGroupName
     */
    public function setCurrentGroupName($name) {
        $this->_curr_group_name = $name;
    }

    /**
     * @see ICacheClusterGroup::addGroup
     */
    public function addGroup($name, $value = null) {
        if(empty($this->_cluster_groups[$name])) {
            $this->_cluster_groups[$name] = $value;
            $this->_group_node_number[$name] = 1;
        }
    }

    /**
     * @see ICacheClusterGroup::modifyGroup
     */
    public function modifyGroup($name, $value = null) {
        if(empty($value)) return false;

        if(!is_array($value)) {
            $this->_cluster_groups[$name] = $value;
        }
        else {
            foreach ($value as $k => $v) {
                $this->_cluster_groups[$name][$k] = $v;
            }
        }
    }

    /**
     * @see ICacheClusterGroup::deleteGroup
     */
    public function deleteGroup($name) {
        if(isset($this->_cluster_groups[$name])) {
            $this->_cluster_groups[$name] = null;

            unset($this->_cluster_groups[$name]);
            unset($this->_group_node_number[$this->_curr_group_name]);
        }
    }

    /**
     * @see ICacheClusterGroup::appendServerToGroup
     * @return bool
     */
    public function appendServerToGroup($name, $value, $key = null) {
        if(!isset($this->_cluster_groups[$name])) return false;

        if(is_string($this->_cluster_groups[$name])) {
            $this->_cluster_groups[$name] = $value;
        }
        else {
            array_push($this->_cluster_groups[$name], $value);
        }

        ++$this->_group_node_number[$this->_curr_group_name];
    }

    /**
     * @see ICacheClusterGroup::deleteServerFromGroup
     */
    public function deleteServerFromGroup($name, $key) {
        if(isset($this->_cluster_groups[$name][$key])) {
            unset($this->_cluster_groups[$name][$key]);
            --$this->_group_node_number[$this->_curr_group_name];
        }
    }

    /**
     * @see ICacheClusterGroup::flushGroup
     */
    public function flushGroup() {
        $this->_cluster_groups = null;
    }

    /**
     * 获取集群节点数量
     *
     * @return int
     */
    public function fetchNodeNumber() {
        return $this->_node_number;
    }

    /**
     * 随机获取缓存节点
     *
     * @return int
     */
    protected function _hash($group = false) {
        $randMD5 = md5(mt_rand());
        $hex = bin2hex($randMD5);
        $count = 0;
        for($i=0; $i < 32; $i++) {
            $count += mb_substr($hex, $i*2, 2);
        }

        return ($group) ? ($count*1)%$this->_group_node_number[$this->_curr_group_name] : ($count*1)%$this->_node_number;
    }

    /**
     * 判断是否从指定组获取服务器信息
     *
     * @return mixed
     */
    protected function _fetchByGroup() {
        $currNode = null;

        # 判断当前分组名称是否不为空
        if(isset($this->_curr_group_name) || !empty($this->_curr_group_name = $this->_cache_engine->fetchClusterStrategy()['group'])) {
            # 判断分组中是否已配置有节点
            if(empty($this->_cluster_groups[$this->_curr_group_name])) return false;

            $currNode = $this->_hash(true);
            list($this->_cache_engine->host, $this->_cache_engine->port) = explode(':', $this->_cluster_groups[$this->_curr_group_name][$currNode]);
        }
        else {
            # 判断集群是否已配置有节点
            if(empty($this->_clusters)) return false;

            $currNode = $this->_hash();
            list($this->_cache_engine->host, $this->_cache_engine->port) = explode(':', $this->_clusters[$currNode]);
        }

        return $currNode;
    }

    /**
     * 顺时针选择下一个节点作为连接对象.
     *
     * @param int $currNode 当前结点
     */
    protected function _nextNode(&$currNode) {
        ++$currNode;

        if(isset($this->_curr_group_name)) {
            $this->_fetchNodeFromGroup($currNode);
        }
        else {
            $this->_fetchNodeFromCluster($currNode);
        }

        if(!$this->_cache_engine->invokeConnect(true)) {
            $this->_nextNode($currNode);
        }
    }

    /**
     * 从分组中获取节点信息
     *
     * @param int $currNode
     */
    protected function _fetchNodeFromGroup(&$currNode) {
        if(isset($this->_cluster_groups[$this->_curr_group_name]) && is_string($this->_cluster_groups[$this->_curr_group_name])) {
            list($this->_cache_engine->host, $this->_cache_engine->port) = explode(':', $this->_cluster_groups[$this->_curr_group_name]);
        }

        if(isset($this->_cluster_groups[$this->_curr_group_name][$currNode])) {
            list($this->_cache_engine->host, $this->_cache_engine->port) = explode(':', $this->_cluster_groups[$this->_curr_group_name][$currNode]);
        }
        else {
            list($this->_cache_engine->host, $this->_cache_engine->port) = explode(':', $this->_cluster_groups[$this->_curr_group_name][0]);
        }
    }

    /**
     * 从集群中获取节点
     *
     * @param int $currNode
     */
    protected function _fetchNodeFromCluster(&$currNode) {
        if(isset($this->_clusters[$currNode])) {
            list($this->_cache_engine->host, $this->_cache_engine->port) = explode(':', $this->_clusters[$currNode]);
        }
        else {
            list($this->_cache_engine->host, $this->_cache_engine->port) = explode(':', $this->_clusters[0]);
        }
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
     * 当前使用分组名称
     * @var null $_curr_group_name
     */
    protected $_curr_group_name = null;
    /**
     * 集群节点数量
     * @var int $_node_number
     */
    protected $_node_number = 0;
    /**
     * 分组中节点数量
     * @var array $_node_number
     */
    protected $_group_node_number = array();
}
