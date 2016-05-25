<?php
/**
 * 缓存引擎接口，提供不同缓存解决方案。
 *
 * @author lucasho
 * @created 2015-09-15
 * @modified 2016-05-25
 * @version 1.0
 * @link http://www.iamhby.com
 */
interface ICacheEngin {
    /**
     * 根据$name,$value和$expire写入缓存值，如果$name值已存在则覆盖原有值。
     *
     * @param string $name   缓存名称。
     * @param mixed  $value  缓存值。
     * @param int    $expire 过期时间（单位秒）。
     * @return mixed
     */
    function write($name, $value, $expire = 3600);

    /**
     * 根据$name参数读取缓存的值，如果值不存在或已过期返回false。
     *
     * @param string $name 缓存名称。
     * @return mixed|bool
     */
    function read($name);

    /**
     * 根据$name参数删除缓存的值，并返回结果。
     *
     * @param string $name 缓存名称。
     * @return mixed
     */
    function delete($name);

    /**
     * 清空缓存引擎的全部缓存值，并返回结果。
     *
     * @return mixed
     */
    function flush();
}

/**
 * 缓存集群接口
 *
 * @author lucasho
 * @created 2016-05-24
 * @modified 2016-05-24
 * @version 1.0
 * @link http://www.iamhby.com
 */
interface ICacheCluster {
    /**
     * 连接
     *
     * @return mixed
     */
    function connect();

    /**
     * 获取服务器
     *
     * @param int $key
     * @return mixed
     */
    function fetchServer($key);

    /**
     * 添加服务器
     *
     * @param string $name
     * @param string $value
     * @return mixed
     */
    function addServer($value);

    /**
     * 批量添加服务器
     *
     * @param array $servers
     * @return mixed
     */
    function addMultiServer($servers = array());

    /**
     * 删除单个服务器
     *
     * @param int $key
     * @return mixed
     */
    function deleteServer($key);
}

/**
 * 缓存集群分组接口
 *
 * @author lucasho
 * @created 2016-05-24
 * @modified 2016-05-24
 * @version 1.0
 * @link http://www.iamhby.com
 */
interface ICacheClusterGroup {
    /**
     * 获取指定分组信息
     *
     * @param string $name 分组名称
     * @return mixed
     */
    function fetchGroup($name);

    /**
     * 设置当前使用分组名称
     *
     * @param string $name 分组名称
     * @return mixed
     */
    function setCurrentGroupName($name);

    /**
     * 添加新分组
     *
     * @param string $name 分组名称
     * @param array $value
     * @return mixed
     */
    function addGroup($name, $value = null);

    /**
     * 修改指定分组信息
     * @example:
     *  $value = array($key => $value, $key1 => $value1, ...); 根据$key值, 作为读分组信息的修改
     *  $value = '127.0.0.1:11211'; 作为写分组信息的修改
     *
     * @param string $name 分组名称
     * @param mixed $value
     * @return mixed
     */
    function modifyGroup($name, $value = null);

    /**
     * 删除分组
     *
     * @param string $name 分组名称
     * @return mixed
     */
    function deleteGroup($name);

    /**
     * 释放所有分组信息
     *
     * @return mixed
     */
    function flushGroup();

    /**
     * 追加服务器到指定分组
     *
     * @param string $name 分组名称
     * @param string $key 分组中的服务器下标
     * @param string $value 服务器信息
     * @return mixed
     */
    function appendServerToGroup($name, $value, $key = null);

    /**
     * 删除指定分组中的服务器
     *
     * @param string $name 分组名称
     * @param string $key 分组中的服务器下标
     * @return mixed
     */
    function deleteServerFromGroup($name, $key);
}