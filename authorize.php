<?php
/**
 * 接口文档地址：https://docs.open.alipay.com/53/104114
 *  注意：要在支付宝开放平台应用设置中设置授权回调地址，且只能在该网址下测试。否则会出现网页找不到的情况
 */
header('Content-type:text/html; Charset=utf-8');
/*** 请填写以下配置信息 ***/
$scope = 'auth_base';       //auth_base或auth_userinfo。如果只需要获取用户id，填写auth_base即可。如需获取头像、昵称等信息，则填写auth_userinfo
$appid = '';    //https://open.alipay.com 账户中心->密钥管理->开放平台密钥，填写开通了“获取会员信息”应用的APPID
$signType = 'RSA2';       //签名算法类型，支持RSA2和RSA，推荐使用RSA2
//商户私钥，填写对应签名算法类型的私钥，如何生成密钥参考：https://docs.open.alipay.com/291/105971和https://docs.open.alipay.com/200/105310
$rsaPrivateKey='';
/*** 配置结束 ***/
$aliPay = new AlipayService();
$aliPay->setAppid($appid);
$aliPay->setScope($scope);
$aliPay->setRsaPrivateKey($rsaPrivateKey);
$result = $aliPay->getToken();
$user = array();
if($baseInfo = $result['alipay_system_oauth_token_response']){
    $userid = $baseInfo['user_id'];
    if($scope=='auth_base'){
        $user['user_id'] = $userid;
		echo '<h1>你的user_id是：'.$user['user_id'];exit();
    }else{
        $userinfo = $aliPay->doGetUserInfo($baseInfo['access_token']);
        if($userinfo['error_response']){
            echo '<h1>'.$userinfo['error_response']['code'].':'.$userinfo['error_response']['sub_msg'].'</h1>';
            exit();
        }
        if($userinfo['alipay_user_userinfo_share_response']){
            $user = $userinfo['alipay_user_userinfo_share_response'];
			//打印user信息
			echo "<pre>";
			print_r($user);die;
        }else{
            exit('异常');
        }
    }
}else{
    echo '<h1>'.$result['error_response']['code'].':'.$result['error_response']['sub_msg'].'</h1>';exit();
}

class AlipayService
{
    protected $appId;
    protected $charset;
    //私钥值
    protected $rsaPrivateKey;
    protected $auth_code;
    public function __construct()
    {
        $this->charset = 'utf-8';
    }

    public function setAppid($appid)
    {
        $this->appId = $appid;
    }

    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    public function setAuthCode($authCode)
    {
        $this->auth_code = $authCode;
    }

    public function setRsaPrivateKey($rsaPrivateKey)
    {
        $this->rsaPrivateKey = $rsaPrivateKey;
    }

    /**
     * 获取access_token和user_id
     * @return array
     */
    public function doAuth()
    {
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => 'alipay.system.oauth.token',//接口名称
            'format' => 'JSON',
            'charset'=>$this->charset,
            'sign_type'=>'RSA2',
            'timestamp'=>date('Y-m-d H:i:s'),
            'version'=>'1.0',
            'grant_type'=>'authorization_code',
            'code'=>$this->auth_code,
        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        $result = $this->curlPost('https://openapi.alipay.com/gateway.do?charset='.$this->charset,$commonConfigs);
        return json_decode($result,true);
    }

    /**
     * 获取access_token和user_id
     */
    public function getToken()
    {
        //通过code获得access_token和user_id
        if (!isset($_GET['auth_code'])){
            //触发微信返回code码
            $scheme = $_SERVER['HTTPS']=='on' ? 'https://' : 'http://';
            $baseUrl = urlencode($scheme.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
            if($_SERVER['QUERY_STRING']) $baseUrl = $baseUrl.'?'.$_SERVER['QUERY_STRING'];
            $url = $this->__CreateOauthUrlForCode($baseUrl);
            Header("Location: $url");
            exit();
        } else {
            //获取code码，以获取openid
            $this->setAuthCode($_GET['auth_code']);
            return $this->doAuth();
        }
    }

    /**
     * 通过code获取access_token和user_id
     * @param string $code 支付宝跳转回来带上的auth_code
     * @return openid
     */
    public function getBaseinfoFromAlipay($code)
    {
        $this->setAuthCode($code);
        return $this->doAuth();
    }

    /**
     * 构造获取token的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["app_id"] = $this->appId;
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["scope"] = $this->scope;
        $urlObj["state"] = 123456;
        $bizString = $this->ToUrlParams($urlObj);
        return "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?".$bizString;
    }

    /**
     * 拼接签名字符串
     * @param array $urlObj
     * @return 返回已经拼接好的字符串
     */
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign") $buff .= $k . "=" . $v . "&";
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 获取用户信息
     * @return array
     */
    public function doGetUserInfo($token)
    {
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => 'alipay.user.userinfo.share',//接口名称
            'format' => 'JSON',
            'charset'=>$this->charset,
            'sign_type'=>'RSA2',
            'timestamp'=>date('Y-m-d H:i:s'),
            'version'=>'1.0',
            'auth_token'=>$token,
        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        $result = $this->curlPost('https://openapi.alipay.com/gateway.do?charset='.$this->charset,$commonConfigs);
        return json_decode($result,true);
    }

    public function generateSign($params, $signType = "RSA") {
        return $this->sign($this->getSignContent($params), $signType);
    }

    protected function sign($data, $signType = "RSA") {
        $priKey=$this->rsaPrivateKey;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, version_compare(PHP_VERSION,'5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256); //OPENSSL_ALGO_SHA256是php5.4.8以上版本才支持
        } else {
            openssl_sign($data, $sign, $res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }
    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;
        return false;
    }
    public function getSignContent($params) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                $v = $this->characet($v, $this->charset);
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }
    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset) {
        if (!empty($data)) {
            $fileType = $this->charset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }
        return $data;
    }
    public function curlPost($url = '', $postData = '', $options = array())
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
