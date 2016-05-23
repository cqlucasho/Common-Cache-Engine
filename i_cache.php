<?php
/**
 * 缓存引擎接口，提供不同缓存解决方案。
 * @package library.cache
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

    /**
     * 缓存集群
     *
     * @return mixed
     */
    function cluster();
}

/**
 * 缓存集群接口
 * @package library.cache
 */
interface ICacheCluster {
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
 * @package library.cache
 */
interface ICacheClusterGroup {
    /**
     * 获取指定分组信息
     *
     * @param $group
     * @return mixed
     */
    function fetchGroup($group);

    /**
     * 添加新分组
     *
     * @param string $group
     * @param array $value
     * @return mixed
     */
    function addGroup($group, $value = null);

    /**
     * 修改指定分组信息
     * @example:
     *  $value = array($key => $value, $key1 => $value1, ...); 根据$key值, 作为读分组信息的修改
     *  $value = '127.0.0.1:11211'; 作为写分组信息的修改
     *
     * @param string $group
     * @param mixed $value
     * @return mixed
     */
    function modifyGroup($group, $value = null);

    /**
     * 删除分组
     *
     * @param $name
     * @return mixed
     */
    function deleteGroup($group);

    /**
     * 追加服务器到指定分组
     *
     * @param string $group
     * @param string $key
     * @param string $value
     * @return mixed
     */
    function appendServerToGroup($group, $key, $value);

    /**
     * 删除指定分组中的服务器
     *
     * @param string $group
     * @param string $key
     * @return mixed
     */
    function deleteServerFromGroup($group, $key);
}