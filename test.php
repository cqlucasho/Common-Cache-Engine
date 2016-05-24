<?php
require_once('memcache_engine.php');

# 指定缓存使用
$Memcache = new MemcacheEngine('127.0.0.1', 11211);
$Memcache->write('xx', 'xxx');

# 集群使用
$Memcache = new MemcacheEngine(null, null, null, array('strategy' => MemcacheEngine::CLUSTE_CONSISTENY_HASH));
$Memcache->fetchClusterObj()->addServer('127.0.0.1:11211');
$Memcache->fetchClusterObj()->addMultiServer(array('127.0.0.1:11212', '127.0.0.1:11213'));
$Memcache->connect();
$Memcache->write('xxx', 'xxx');

# 使用集群分组
$Memcache = new MemcacheEngine(null, null, null, array('strategy' => MemcacheEngine::CLUSTE_CONSISTENY_HASH, 'group' => 'readServer'));
$Memcache->fetchClusterObj()->addGroup('writeServer', '127.0.0.1:11213');
$Memcache->fetchClusterObj()->addGroup('readServer', array('127.0.0.1:11211', '127.0.0.1:11212'));
$Memcache->connect();
$Memcache->write('xxxx', 'xxxx');
# 还可以立即切换分组并使用
$Memcache->fetchClusterObj()->setCurrentGroupName('writeServer');
$Memcache->connect();
$Memcache->write('xxxx', 'xxxx');