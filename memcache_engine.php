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
}
