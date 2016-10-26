<?php 
/**
 * User: qiming.c <qiming.c@foxmail.com>
 * Date: 2016/10/26
 * Time: 20:46
 */

class FileCache
{
    protected $_dir;
    protected $_prefix;

    /**
     * @var array 默认配置
     */
    protected $_config = array(
        'dir' => './tmp/cache', //当前目录下
        'prefix' => '',
    );

    /**
     * @param $config array 配置项
     */
    public function __construct($config = array())
    {
        $this->_config = array_merge($this->_config, $config);

        $this->_dir = $this->_config['dir'];
        $this->_prefix = $this->_config['prefix'];
        if (!is_dir($this->_dir)) {
            if (!mkdir($this->_dir, 0775, true)) {
                trigger_error("创建" . $this->_dir . "文件夹失败", E_USER_WARNING);
            }
            chmod($this->_dir, 0775);
        }

    }

    /**
     * 设置缓存
     * @param  string $key      键
     * @param  mixed $data      值
     * @param  int $lifetime    缓存时间，单位为秒
     * @return boolean
     */
    public function set($key, $data, $lifetime = 0)
    {
        $file = $this->getFileDir($key);
        return $this->pushContents($file, $data, (int)($lifetime));
    }

    /**
     * 得到缓存数据
     * @param  string $key
     * @return mixed 数据
     */
    public function get($key)
    {
        if (!$this->has($key)){
            return null;
        }
        $file = $this->getFileDir($key);  
        $data = $this->getContents($file);
        if (!empty($data)) {
            return $data;
        }
        return null;
    }

    /** 删除一条缓存 */
    public function remove($key)
    {
        $file = $this->getFileDir($key);
        if ($this->has($key)) {
            return unlink($file);
        } else {
            return false;
        }
    }

    /** 清除目录下所有缓存 */
    public function flush()
    {
        $files = scandir($this->_dir);
        if ($files) {
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && is_file($this->_dir . '/' . $file)) {
                    unlink($this->_dir . '/' . $file);   
                }
            }
        }
        return true;
    }

    /** 返回缓存是否存在 */
    protected function has($key)
    {
        $file = $this->getFileDir($key);
        return is_file($file);
    }
    
    /** 返回缓存文件带路径的完整名称 */
    protected function getFileDir($key)
    {
        return $this->_dir . '/' . $this->key2FileName($key);
    }

    /** 生成文件名 */
    protected function key2FileName($key)
    {
        return md5($this->_prefix . "$key");
    }

    /**
     * 读取缓存文件内容
     * @param  string $file 带路径的文件名称
     * @return mixed        缓存内容
     */
    protected function getContents($file)
    {
        $contents = file_get_contents($file);

        if ($contents) {
            if ( function_exists('gzuncompress')){
                $contents = gzuncompress($contents);
            }
            $contents =  unserialize($contents);
            if ($contents['time'] == 0 || $contents['time'] > time()){
                return $contents['data'];
            } else {
                unlink($file);
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * 把内容存进文件
     * @param  string   $file        带路径的文件名称
     * @param  mixed    $data        要缓存的内容
     * @param  int      $lifetime    缓存时间,以秒为单位
     * @return boolean
     */
    protected function pushContents($file, $data, $lifetime)
    {
        $contents['time'] = $lifetime == 0 ? 0 : time() + $lifetime;
        $contents['data'] = $data;
        $contents = serialize($contents);
        if (function_exists('gzcompress')) {
            $contents = gzcompress($contents);
        }
        if (is_dir(dirname($file)) && is_writable(dirname($file))) {
            $result = file_put_contents($file, $contents);
            chmod($file, 0775);
        } else {
            trigger_error($file . '没有权限写入.', E_USER_WARNING);
        }
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}