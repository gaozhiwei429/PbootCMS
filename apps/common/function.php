<?php
/**
 * @ copyright (C)2016-2099 Hnaoyun Inc.
 * @ license This is not a freeware, use is subject to license terms
 * @ author XingMeng
 * @ email hnxsh@foxmail.com
 * @ date 2017年4月14日
 * 公共处理函数
 */
use core\basic\Config;

// 获取字符串型自动编码
function get_auto_code($string, $start = '1')
{
    if (! $string)
        return $start;
    if (is_numeric($string)) { // 如果纯数字则直接加1
        return sprintf('%0' . strlen($string) . 's', $string + 1);
    } else { // 非纯数字则先分拆
        $reg = '/^([a-zA-Z-_]+)([0-9]+)$/';
        $str = preg_replace($reg, '$1', $string); // 字母部分
        $num = preg_replace($reg, '$2', $string); // 数字部分
        return $str . sprintf('%0' . (strlen($string) - strlen($str)) . 's', $num + 1);
    }
}

// 获取指定分类列表
function get_type($tcode)
{
    $type_model = model('admin.system.Type');
    if (! ! $result = $type_model->getItem($tcode)) {
        return $result;
    } else {
        return array();
    }
}

// 生成区域选择
function make_area_Select($tree, $selectid = null)
{
    $list_html = '';
    global $blank;
    foreach ($tree as $values) {
        // 默认选择项
        if ($selectid == $values->acode) {
            $select = "selected='selected'";
        } else {
            $select = '';
        }
        
        // 禁用父栏目选择功能
        if ($values->son) {
            $disabled = "disabled='disabled'";
        } else {
            $disabled = '';
        }
        $list_html .= "<option value='{$values->acode}' $select $disabled>{$blank}{$values->acode} {$values->name}";
        
        // 子菜单处理
        if ($values->son) {
            $blank .= '　　';
            $list_html .= make_area_Select($values->son, $selectid);
        }
    }
    // 循环完后回归位置
    $blank = substr($blank, 0, - 6);
    return $list_html;
}

// 检测指定的方法是否拥有权限
function check_level($btnAction, $isPath = false)
{
    $user_level = session('levels');
    if ($isPath) {
        if (in_array($btnAction, $user_level)) {
            return true;
        }
    } else {
        if (in_array('/' . M . '/' . C . '/' . $btnAction, $user_level) || session('id') == 1) {
            return true;
        }
    }
}

// 获取返回按钮
function get_btn_back($btnName = '返 回')
{
    if (! ! $backurl = get('backurl')) {
        $url = base64_decode($backurl);
    } elseif (isset($_SERVER["HTTP_REFERER"])) {
        $url = $_SERVER["HTTP_REFERER"];
    } else {
        $url = url('/' . M . '/' . C . '/index');
    }
    
    $btn_html = "<a href='" . $url . "' class='layui-btn layui-btn-primary'>$btnName</a>";
    return $btn_html;
}

// 获取新增按钮
function get_btn_add($btnName = '新 增')
{
    $user_level = session('levels');
    if (! in_array('/' . M . '/' . C . '/add', $user_level) && session('id') != 1)
        return;
    $btn_html = "<a href='" . url("/" . M . '/' . C . "/add") . "?backurl=" . base64_encode(URL) . "' class='layui-btn layui-btn-primary'>$btnName</a>";
    return $btn_html;
}

// 获取更多按钮
function get_btn_more($idValue, $id = 'id', $btnName = '详情')
{
    $btn_html = "<a href='" . url("/" . M . '/' . C . "/index/$id/$idValue") . "' class='layui-btn layui-btn-xs layui-btn-primary' title='$btnName'>$btnName</a>";
    return $btn_html;
}

// 获取删除按钮
function get_btn_del($idValue, $id = 'id', $btnName = '删除')
{
    $user_level = session('levels');
    if (! in_array('/' . M . '/' . C . '/del', $user_level) && session('id') != 1)
        return;
    $btn_html = "<a href='" . url('/' . M . '/' . C . "/del/$id/$idValue") . "' onclick='return confirm(\"您确定要删除么？\")' class='layui-btn layui-btn-xs layui-btn-danger' title='$btnName'>$btnName</a>";
    return $btn_html;
}

// 获取修改按钮
function get_btn_mod($idValue, $id = 'id', $btnName = '修改')
{
    $user_level = session('levels');
    if (! in_array('/' . M . '/' . C . '/mod', $user_level) && session('id') != 1)
        return;
    $btn_html = "<a href='" . url("/" . M . '/' . C . "/mod/$id/$idValue") . "?backurl=" . base64_encode(URL) . "'  class='layui-btn layui-btn-xs'>$btnName</a>";
    return $btn_html;
}

// 获取其它按钮
function get_btn($btnName, $theme, $btnAction, $idValue, $id = 'id')
{
    $user_level = session('levels');
    if (! in_array('/' . M . '/' . C . '/' . $btnAction, $user_level) && session('id') != 1)
        return;
    $btn_html = "<a href='" . url("/" . M . '/' . C . "/$btnAction/$id/$idValue") . "?backurl=" . base64_encode(URL) . "'  class='layui-btn layui-btn-xs $theme'>$btnName</a>";
    return $btn_html;
}

// 缓存基础信息
function cache_config($refresh = false)
{
    
    // 多语言缓存，不存在时自动缓存
    $lg_cache = RUN_PATH . '/config/' . md5('language') . '.php';
    if (! file_exists($lg_cache) || $refresh) {
        $model = model('admin.system.Config');
        $area = $model->getAreaTheme(); // 获取所有语言
        $map = array();
        foreach ($area as $key => $value) {
            $map[$value['acode']] = $value;
        }
        if (! $map) {
            error('系统没有任何可用区域，请核对后再试！');
        }
        $lgs['lgs'] = $map;
        Config::set(md5('language'), $lgs, false);
    }
    Config::assign($lg_cache); // 注入多语言
                               
    // 语言绑定域名， 如果匹配到多语言绑定则自动设置当前语言
    $lgs = Config::get('lgs');
    if (count($lgs) > 1) {
        $domain = get_http_host();
        foreach ($lgs as $value) {
            if ($value['domain'] == $domain) {
                cookie('lg', $value['acode']);
            }
        }
    }
    
    // 未设置语言时使用默认语言
    if (! isset($_COOKIE['lg'])) {
        cookie('lg', get_default_lg());
    }
    
    // 系统配置缓存
    $config_cache = RUN_PATH . '/config/' . md5('config') . '.php';
    if (! file_exists($config_cache) || $refresh) {
        if (! isset($model)) {
            $model = model('admin.system.Config');
        }
        Config::set(md5('config'), $model->getConfig(), false);
    }
    Config::assign($config_cache); // 注入语言配置
}

// 获取默认语言
function get_default_lg()
{
    $default = current(Config::get('lgs'));
    return $default['acode'];
}

// 获取当前语言并进行安全处理
function get_lg()
{
    $lg = cookie('lg');
    if (! $lg || ! preg_match('/^[\w\-]+$/', $lg)) {
        $lg = get_default_lg();
        cookie('lg', $lg);
    }
    return $lg;
}

// 获取当前语言主题
function get_theme()
{
    $lgs = Config::get('lgs');
    $lg = get_lg();
    return $lgs[$lg]['theme'];
}

// 推送百度
function post_baidu($api, $urls)
{
    $ch = curl_init();
    $options = array(
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_URL => $api,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => implode("\n", $urls),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: text/plain'
        )
    );
    curl_setopt_array($ch, $options);
    $result = json_decode(curl_exec($ch));
    return $result;
}

// 服务器信息
function get_server_info()
{
    // 定义输出常量
    define('YES', 'Yes');
    define('NO', '<span style="color:red">No</span>');
    
    // 服务器系统
    $data['php_os'] = PHP_OS;
    // 服务器访问地址
    $data['http_host'] = $_SERVER['HTTP_HOST'];
    // 服务器名称
    $data['server_name'] = $_SERVER['SERVER_NAME'];
    // 服务器端口
    $data['server_port'] = $_SERVER['SERVER_PORT'];
    // 服务器地址
    $data['server_addr'] = isset($_SERVER['LOCAL_ADDR']) ? $_SERVER['LOCAL_ADDR'] : $_SERVER['SERVER_ADDR'];
    // 服务器软件
    $data['server_software'] = $_SERVER['SERVER_SOFTWARE'];
    // 站点目录
    $data['document_root'] = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : DOC_PATH;
    // PHP版本
    $data['php_version'] = PHP_VERSION;
    // 数据库驱动
    $data['db_driver'] = Config::get('database.type');
    // php配置文件
    $data['php_ini'] = @php_ini_loaded_file();
    // 最大上传
    $data['upload_max_filesize'] = ini_get('upload_max_filesize');
    // 最大提交
    $data['post_max_size'] = ini_get('post_max_size');
    // 最大提交文件数
    $data['max_file_uploads'] = ini_get('max_file_uploads');
    // 内存限制
    $data['memory_limit'] = ini_get('memory_limit');
    // 检测gd扩展
    $data['gd'] = extension_loaded('gd') ? YES : NO;
    // 检测imap扩展
    $data['imap'] = extension_loaded('imap') ? YES : NO;
    // 检测socket扩展
    $data['sockets'] = extension_loaded('sockets') ? YES : NO;
    // 检测curl扩展
    $data['curl'] = extension_loaded('curl') ? YES : NO;
    // 会话保存路径
    $data['session_save_path'] = session_save_path() ?: $_SERVER['TMP'];
    // 检测standard库是否存在
    $data['standard'] = extension_loaded('standard') ? YES : NO;
    // 检测多线程支持
    $data['pthreads'] = extension_loaded('pthreads') ? YES : NO;
    // 检测XCache支持
    $data['xcache'] = extension_loaded('XCache') ? YES : NO;
    // 检测APC支持
    $data['apc'] = extension_loaded('APC') ? YES : NO;
    // 检测eAccelerator支持
    $data['eaccelerator'] = extension_loaded('eAccelerator') ? YES : NO;
    // 检测wincache支持
    $data['wincache'] = extension_loaded('wincache') ? YES : NO;
    // 检测ZendOPcache支持
    $data['zendopcache'] = extension_loaded('Zend OPcache') ? YES : NO;
    // 检测memcache支持
    $data['memcache'] = extension_loaded('memcache') ? YES : NO;
    // 检测memcached支持
    $data['memcached'] = extension_loaded('memcached') ? YES : NO;
    // 已经安装模块
    $loaded_extensions = get_loaded_extensions();
    $extensions = '';
    foreach ($loaded_extensions as $key => $value) {
        $extensions .= $value . ', ';
    }
    $data['extensions'] = $extensions;
    return json_decode(json_encode($data));
}




