<?php
require_once('memcache_engine.php');
# 指定缓存使用
$Memcache = new MemcacheEngine(null, null, null, array('strategy' => MemcacheEngine::CLUSTE_CONSISTENY_HASH));
$Memcache->write('xx', 'xxx');

# 集群使用
$Memcache = new MemcacheEngine(null, null, null, array('strategy' => MemcacheEngine::CLUSTE_CONSISTENY_HASH));
$Memcache->fetchClusterObj()->addMultiServer(array('127.0.0.1:11211', '127.0.0.1:11212'));
$Memcache->connect();
$Memcache->write('xxx', 'xxx');