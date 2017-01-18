<?php
require_once('memcache_engine.php');

# 指定缓存使用
$Memcache = new MemcacheEngine('127.0.0.1', 11211);
$Memcache->write('xx', 'xxx');
echo $Memcache->read('xx'),'<br/>';

# 集群使用
$Memcache = new MemcacheEngine(null, null, null, array('strategy' => MemcacheEngine::CLUSTE_CONSISTENY_HASH));
$Memcache->fetchClusterObj()->addServer('127.0.0.1:11211');
$Memcache->fetchClusterObj()->addMultiServer(array('127.0.0.1:11212', '127.0.0.1:11213'));
$Memcache->connect();
$Memcache->write('x1', 'x123');
echo $Memcache->read('x1'),'<br/>';

# 使用集群分组
$Memcache = new MemcacheEngine(null, null, null, array('strategy' => MemcacheEngine::CLUSTE_CONSISTENY_HASH, 'group' => 'readServer'));
$Memcache->fetchClusterObj()->addGroup('writeServer', '127.0.0.1:11213');
$Memcache->fetchClusterObj()->addGroup('readServer', array('127.0.0.1:11211', '127.0.0.1:11212'));
$Memcache->connect();
$Memcache->write('x2', 'x234');
echo $Memcache->read('x2'),'<br/>';
# 还可以立即切换分组并使用
$Memcache->fetchClusterObj()->setCurrentGroupName('writeServer');
# 如果之前已经连接过相同服务器, 则不再需要执行$Memcache->connect();
$Memcache->write('x3', 'x345');
echo $Memcache->read('x3');

$Memcache->fetchClusterObj()->setCurrentGroupName('readServer');
$Memcache->write('x4', 'x456');
echo $Memcache->read('x4'),'<br/>';