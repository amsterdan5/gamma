<?php

namespace Base;

/**
 * 多语言处理
 */
class I18n
{
    /**
     * 默认语言
     *
     * @var string
     */
    protected $_default = 'en-us';

    /**
     * 语言目录
     *
     * @var array
     */
    protected $_directories = [];

    /**
     * 已加载的语言包
     *
     * @var array
     */
    protected $_packages = [];

    /**
     * 所有语言缓存
     *
     * @var array
     */
    protected $_cached = [];

    /**
     * 语言别名
     *
     * @var array
     */
    protected $_aliases = [];

    /**
     * 构造方法
     *
     * @param string $default
     */
    public function __construct($default = null)
    {
        if ($default !== null) {
            $this->setDefault($default);
        }
    }

    /**
     * 增加语言目录
     *
     * @param  string|array $dirs
     * @return \Base\I18n
     */
    public function addDirectory($dirs)
    {
        if (!is_array($dirs)) {
            $dirs = [$dirs];
        }

        // 获取新增的目录
        $newDirs = [];
        foreach ($dirs as $dir) {
            $dir = realpath($dir);
            if ($dir !== false && !in_array($dir, $this->_directories)) {
                array_push($newDirs, $dir);
            }
        }

        if ($newDirs) {
            // 加入到目录列表
            $this->_directories = array_merge($this->_directories, $newDirs);

            // 清空缓存
            $this->_cached = [];
        }

        return $this;
    }

    /**
     * 设置默认语言
     *
     * @param  string       $lang
     * @return \Base\I18n
     */
    public function setDefault($lang)
    {
        $this->_default = $this->getLangByAlias($lang);

        return $this;
    }

    /**
     * 返回默认语言
     *
     * @return string
     */
    public function getDefault()
    {
        return $this->_default;
    }

    /**
     * 添加语言别名
     *
     * @param  array        $aliases
     * @return \Base\I18n
     */
    public function addAliases(array $aliases)
    {
        foreach ($aliases as $key => $values) {
            $key = strtolower($key);

            if (!isset($this->_aliases[$key])) {
                $this->_aliases[$key] = [];
            }

            if (!is_array($values)) {
                $values = [$values];
            }

            foreach ($values as $alias) {
                if (!in_array($alias, $this->_aliases[$key])) {
                    array_push($this->_aliases[$key], $alias);
                }
            }
        }

        // reset the default language
        $this->setDefault($this->_default);

        return $this;
    }

    /**
     * 根据别名获取语言类型
     *
     * @param  string   $alias
     * @return string
     */
    public function getLangByAlias($alias)
    {
        $alias = strtolower($alias);

        foreach ($this->_aliases as $lang => $aliases) {
            if (in_array($alias, $aliases)) {
                return $lang;
            }
        }

        return $alias;
    }

    /**
     * 判断语言是否存在
     *
     * @param  string    $lang
     * @return boolean
     */
    public function hasLang($lang)
    {
        $lang = $this->getLangByAlias($lang);

        foreach ($this->_directories as $dir) {
            if (is_dir("{$dir}/{$lang}")) {
                return true;
            }
        }

        return false;
    }

    /**
     * 加载语言包
     *
     * @param  string|array $packages
     * @return \Base\I18n
     */
    public function import($packages)
    {
        if (!is_array($packages)) {
            $packages = [$packages];
        }

        // 移除空包
        $packages = array_filter($packages, function ($p) {
            return !empty($p);
        });

        if (!empty($packages)) {
            // 新增的语言包
            $diff = array_diff($packages, $this->_packages);

            // 合并到已加载的语言包中
            $this->_packages = array_merge($this->_packages, $diff);

            // 载入新增的语言包
            foreach (array_keys($this->_cached) as $lang) {
                $this->_loadPackages($diff, $lang);
            }
        }

        return $this;
    }

    /**
     * 将语言包数据转换为数组
     */
    public function toArray($lang = null)
    {
        if ($lang === null) {
            $lang = $this->getDefault();
        }

        $lang = strtolower($lang);

        // 初始化加载
        if (!isset($this->_cached[$lang])) {
            $this->_initialize($lang);
        }

        return $this->_cached[$lang];
    }

    /**
     * 执行翻译
     *
     * @param  string   $string
     * @param  array    $values
     * @param  string   $lang
     * @return string
     */
    public function translate($string, array $values = null, $lang = null)
    {
        $lang = is_null($lang) ? $this->getDefault() : strtolower($lang);

        // 初始化加载
        if (!isset($this->_cached[$lang])) {
            $this->_initialize($lang);
        }

        // 转换翻译
        $translate = array_get($this->_cached[$lang], $string, $string);

        return is_array($values) ? strtr($translate, $values) : $translate;
    }

    /**
     * 反向翻译
     *
     * @param  string   $string
     * @param  string   $from
     * @param  string   $to
     * @return string
     */
    public function reverseTranslate($string, $from, $to = null)
    {
        if ($from === $to) {
            return $string;
        }

        if (!isset($this->_cached[$from])) {
            $this->_initialize($from);
        }

        $translate = array_get(array_flip($this->_cached[$from]), $string, $string);

        if ($to === null || $to = 'en-us') {
            return $translate;
        }

        if (!isset($this->_cached[$to])) {
            $this->_initialize($to);
        }

        return array_get($this->_cached[$to], $translate, $translate);
    }

    /**
     * 初始化语言
     *
     * @param string $lang
     */
    protected function _initialize($lang)
    {
        $lang     = strtolower($lang);
        $packages = $this->_packages;

        // 将语言默认包加入
        array_unshift($packages, $lang);

        // 加载默认语言包
        $this->_loadPackages($packages, $lang);
    }

    /**
     * 加载语言包
     *
     * @param array  $packages
     * @param string $lang
     */
    protected function _loadPackages(array $packages, $lang)
    {
        $lang = strtolower($lang);

        // 初始化缓存
        if (!isset($this->_cached[$lang])) {
            $this->_cached[$lang] = [];
        }

        $cached = &$this->_cached[$lang];

        // 扫描目录并加载语言包
        foreach ($packages as $package) {
            foreach ($this->_directories as $dir) {
                $file = "{$dir}/{$lang}/" . str_replace('.', '/', $package) . '.php';

                // 语言文件不存在
                if (!is_file($file)) {
                    continue;
                }

                // 无效语言包
                $data = include $file;
                if (!is_array($data)) {
                    continue;
                }

                foreach ($data as $key => $value) {
                    $cached[$key] = $value;
                }
            }
        }
    }
}
