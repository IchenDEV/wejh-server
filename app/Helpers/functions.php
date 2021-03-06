<?php
/**
 * Created by PhpStorm.
 * User: cccRaim
 * Date: 2017/7/3
 * Time: 00:11
 */
use App\Models\SystemSetting;
use App\Models\User;

/**
 * 响应json数据
 * @param  mixed    $data
 * @param  integer  $err_code
 * @param  string   $err_msg
 * @param  string   $redirect_url
 * @return \Symfony\Component\HttpFoundation\Response
 */
function RJM($data, $err_code, $err_msg = '', $redirect_url = null)
{
    return response([
        'errcode' => $err_code,
        'errmsg' => $err_msg,
        'data' => $data,
        'redirect' => $redirect_url,
    ]);
}

/**
 * 获取对应的系统设置
 * @param  string   $varname
 * @return mixed
 */
function setting($varname)
{
    $result = \App\Models\SystemSetting::where('varname', $varname)->first();
    return $result->value;
}

/**
 * 使用CURL的POST请求资源
 * @param  string   $url        资源路径
 * @param  array    $post_data  请求参数
 * @param  int      $timeout    超时时间，毫秒级
 * @return mixed
 */
function http_post($url, $post_data = null, $timeout = 500, $type = 'default'){//curl
    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_POST, 1);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_setopt ($ch, CURLOPT_TIMEOUT_MS, $timeout);
    curl_setopt($ch, CURLOPT_HEADER, false);
    if($post_data){
        if ($type === 'json') {
            $post_data = json_encode($post_data, JSON_UNESCAPED_UNICODE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($post_data))
            );
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    $file_contents = curl_exec($ch);
    curl_close($ch);
    if (env('APP_DEBUG') === true) {
        logger(json_encode([
            'url' => $url,
            'data' => $post_data,
            'result' => $file_contents
        ]));
    }
    return $file_contents;
}

/**
 * 使用CURL的GET请求资源
 * @param  string   $url        资源路径
 * @param  array    $post_data  请求参数
 * @param  int      $timeout    超时时间，毫秒级
 * @return mixed
 */
function http_get($url, $data = null, $timeout = 10000){//curl
    $ch = curl_init();
    if($data){
        if(strpos($url, '?') == false) {
            $url .= '?';
        } else {
            $url .= '&';
        }
        $url .= http_build_query($data);
    }
    curl_setopt ($ch, CURLOPT_URL, $url);
    // logger()->error(json_encode([
    //         'url' => $url
    //     ]));
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_setopt ($ch, CURLOPT_TIMEOUT_MS, $timeout);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $file_contents = curl_exec($ch);
    curl_close($ch);
    if (env('APP_DEBUG') === true) {
        logger(json_encode([
            'url' => $url,
            'data' => $data,
            'result' => $file_contents
        ]));
    }
    return $file_contents;
}

function cdn($path, $is_secure = null) {
    $url = '//cdn.wejh.imcr.me/' . $path;
    if ($is_secure === true) {
        $url = 'https:' . $url;
    } else if ($is_secure === false) {
        $url = 'http:' . $url;
    }
    return $url;
}

function api($key, $isExt)
{
    $configs = config('api');
    if ($isExt === null) {
        $isExt = env('API_EXT', false);
    }
    $route = array_get($configs, $key);
    if(!$route) {
        return false;
    }
    $compatible = env('API_COMPATIBLE', false);
    if(is_array($route)) {
        if ($compatible === true) {
            return $configs['compatibleURL'] . urlencode($route['api']);
        }
        return $isExt ? $route['ext'] : $route['api'];
    }
    $url = '';
    if($isExt) {
        $url = $configs['prefix']['ext'] . $route;
    } else {
        $url = $configs['prefix']['api'] . $route;
    }
    if ($compatible) {
        $url = $configs['compatibleURL'] . urlencode($configs['prefix']['api'] . $route);
    }
    return $url;

}

function getCurrentTerm()
{
    $year = intval(date('Y'));
    $month = intval(date('m'));
    if($month <= 2) {
        $term = (($year - 1) . '/' . $year . '(1)');
    } else if ($month >= 6 && $month < 10) {
        $term = (($year - 1) . '/' . $year . '(2)');
    } else {
        $term = ($year . '/' . ($year + 1) . '(1)');
    }
    return $term;
}

function addYcjwPortError($port)
{
    return \App\Models\Log::addLog('YCJW_PORT_ERROR', $port, "原创教务服务器错误或无响应");
}

function resetCurrentYcjwPort()
{
    $log = new \App\Models\Log;
    $start_time = date('Y-m-d H:00:00', time());
    $min_log_count = count($log->getLogsByAction('YCJW_PORT_ERROR', 0, $start_time));
    $min_log_port = 0;
    for ($i = 83; $i < 87; $i++)
    {
        $log_count = count($log->getLogsByAction('YCJW_PORT_ERROR', $i, $start_time));
        if($min_log_count > $log_count)
        {
            $min_log_count = $log_count;
            $min_log_port = $i;
        }
    }
    (new SystemSetting)->setVars([
        'ycjw_port' => $min_log_port,
    ]);
    return $min_log_port;
}

function createTestUser () {
    $username = '200200000000';
    $password = 123456;
    $user = new User;
    $user->uno = $username;
    $user->password = bcrypt($password);
    $ext = [];
    $ext['passwords']['jh_password'] = encrypt($password);
    $ext['passwords']['yc_password'] = encrypt($password);
    $ext['passwords']['card_password'] = encrypt(substr($username,-6));
    $ext['passwords']['lib_password'] = encrypt($username);
    $user->ext = $ext;
    return $user;
}

function getTestUser () {
    return User::where('uno', '200200000000')->first();
}

function isTestAccount($username)
{
    return $username == '200200000000' || $username == -1 || $username === 'Bearer -1';
}