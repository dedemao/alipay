<?php
//文档地址：https://docs.open.alipay.com/common/105591
error_reporting(0);
header('Content-type:text/html; Charset=utf-8');
/*** 请填写以下配置信息 ***/
$appid = 'xxxxxx';  //https://open.alipay.com 账户中心->密钥管理->开放平台密钥，填写添加了电脑网站支付的应用的APPID
$notifyUrl = 'http://www.xxx.com';     //付款成功后的异步回调地址
$outTradeNo = uniqid();     //你自己的商品订单号，不能重复
$payAmount = 0.1;          //付款金额，单位:元
$orderName = '支付测试';    //订单标题
$signType = 'RSA2';			//签名算法类型，支持RSA2和RSA，推荐使用RSA2
//商户私钥，填写对应签名算法类型的私钥，如何生成密钥参考：https://docs.open.alipay.com/291/105971和https://docs.open.alipay.com/200/105310
$rsaPrivateKey='xxxx';
/*** 配置结束 ***/
if(!isInAlipayClient()){
    echo '<h3>请使用支付宝扫码打开该网页：</h3><img src="https://my.tv.sohu.com/user/a/wvideo/getQRCode.do?width=300&height=300&text='.getCurrentUrl().'" />';
    exit();
}
$aliPay = new AlipayService();
$aliPay->setAppid($appid);
$aliPay->setScope('auth_base');
$aliPay->setRsaPrivateKey($rsaPrivateKey);
$result = $aliPay->getToken();
$user = array();
if($baseInfo = $result['alipay_system_oauth_token_response']){
    $userid = $baseInfo['user_id'];     //支付用户的支付宝id
}else{
    echo '<h1>'.$result['error_response']['code'].':'.$result['error_response']['sub_msg'].'</h1>';exit();
}
$aliPay->setNotifyUrl($notifyUrl);
$aliPay->setTotalFee($payAmount);
$aliPay->setOutTradeNo($outTradeNo);
$aliPay->setOrderName($orderName);
$aliPay->setBuyerId($userid);
$orderStr = $aliPay->createOrder();
$tradeNo = $orderStr['alipay_trade_create_response']['trade_no'];
if(!$tradeNo){
 exit('获取订单号失败');
}
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta charset="UTF-8">
        <title>支付宝jsapi支付</title>
        <link href="https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdn.bootcss.com/jquery/2.1.0/jquery.min.js"></script>
        <script src="https://gw.alipayobjects.com/as/g/h5-lib/alipayjsapi/3.1.1/alipayjsapi.min.js"></script>
    </head>
    <body>
    <div class="container">
        <?php
        if(!isInAlipayClient()):
            ?>
            <h3>请使用支付宝扫码打开该网页：</h3>
            <img src="https://www.kuaizhan.com/common/encode-png?large=true&data=<?php echo getCurrentUrl()?>" />
            <?php
        else:
            ?>
            <h3>点击唤起支付宝收银台</h3>
            <a href="javascript:void(0)" class="btn btn-primary btns-lg orderstrPay orderstr">点击支付</a>

            <div class="alert alert-success" role="alert" style="margin-top:30px;display: none">

            </div>
            <?php
        endif;
        ?>
    </div>

    <p id="result"></p>
    <div class="alert alert-success" role="alert" style="margin-top:30px;display: none">

    </div>

    <script type="application/javascript">
        // 调试时可以通过在页面定义一个元素，打印信息，使用alert方法不够优雅
        function log(obj) {
            $("#result").append(obj).append(" ").append("<br />");
        }

        $(document).ready(function(){
            // 页面载入完成后即唤起收银台
            // 点击payButton按钮后唤起收银台
            $(".orderstr").click(function() {
                tradePay("<?=$tradeNo?>");
            });

            // 通过jsapi关闭当前窗口，仅供参考，更多jsapi请访问
            // /aod/54/104510
            $("#closeButton").click(function() {
                AlipayJSBridge.call('closeWebview');
            });
        });

        // 由于js的载入是异步的，所以可以通过该方法，当AlipayJSBridgeReady事件发生后，再执行callback方法
        function ready(callback) {
            if (window.AlipayJSBridge) {
                callback && callback();
            } else {
                document.addEventListener('AlipayJSBridgeReady', callback, false);
            }
        }

        function tradePay(tradeNO) {
            ready(function(){
                // 通过传入交易号唤起快捷调用方式(注意tradeNO大小写严格)
                AlipayJSBridge.call("tradePay", {
                    tradeNO: tradeNO
                }, function (data) {
//                    log(JSON.stringify(data));
                    if(data.resultCode!=9000){
                        //支付失败
                        alert(data.resultCode+"："+data.memo);
                    }else{
                        //支付成功
                        $(".alert-success").html("<strong>支付成功！</strong> 订单号：<?=$tradeNo?>");
                        $(".alert-success").show();
                    }
                });
            });
        }
    </script>

    </body>
    </html>

<?php
class AlipayService
{
    protected $appId;
    protected $notifyUrl;
    protected $charset;
    //私钥值
    protected $rsaPrivateKey;
    protected $totalFee;
    protected $outTradeNo;
    protected $orderName;
    protected $scope;
    protected $buyerId;
    protected $authCode;

    public function __construct()
    {
        $this->charset = 'utf-8';
    }
    public function setAppid($appid)
    {
        $this->appId = $appid;
    }
    public function setNotifyUrl($notifyUrl)
    {
        $this->notifyUrl = $notifyUrl;
    }
    public function setRsaPrivateKey($saPrivateKey)
    {
        $this->rsaPrivateKey = $saPrivateKey;
    }
    public function setTotalFee($payAmount)
    {
        $this->totalFee = $payAmount;
    }
    public function setOutTradeNo($outTradeNo)
    {
        $this->outTradeNo = $outTradeNo;
    }
    public function setOrderName($orderName)
    {
        $this->orderName = $orderName;
    }
    public function setScope($scope)
    {
        $this->scope = $scope;
    }
    public function setBuyerId($buyerId)
    {
        $this->buyerId = $buyerId;
    }
    public function setAuthCode($authCode)
    {
        $this->authCode = $authCode;
    }
    /**
     * 获取orderStr
     * @return array
     */
    public function createOrder()
    {
        //请求参数
        $requestConfigs = array(
            'out_trade_no'=>$this->outTradeNo,
            'total_amount'=>$this->totalFee, //单位 元
            'subject'=>$this->orderName,  //订单标题
            'buyer_id'=>$this->buyerId,  //购买者的userid
            'timeout_express'=>'2h',       //该笔订单允许的最晚付款时间，逾期将关闭交易。取值范围：1m～15d。m-分钟，h-小时，d-天，1c-当天（1c-当天的情况下，无论交易何时创建，都在0点关闭）。 该参数数值不接受小数点， 如 1.5h，可转换为 90m。
//            'store_id'=>'',                 //商户门店编号。该参数用于请求参数中以区分各门店，非必传项。
            //'terminal_id'=>'xxx',
            //'extend_params'=>array(
                //'sys_service_provider_id'=>'xxxx'       //系统商编号，该参数作为系统商返佣数据提取的依据，请填写系统商签约协议的PID
            //)
        );
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => 'alipay.trade.create',             //接口名称
            'format' => 'JSON',
            'charset'=>$this->charset,
            'sign_type'=>'RSA2',
            'timestamp'=>date('Y-m-d H:i:s'),
            'version'=>'1.0',
            'notify_url' => $this->notifyUrl,
            'biz_content'=>json_encode($requestConfigs),
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
    public function buildOrderStr($data)
    {
        return http_build_query($data);
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

    /**
     * 获取access_token和user_id
     */
    public function getToken()
    {
        //通过code获得access_token和user_id
        if (!isset($_GET['auth_code'])){
            //触发微信返回code码
            $scheme = $_SERVER['HTTPS']=='on' ? 'https://' : 'http://';
			$uri = $_SERVER['PHP_SELF'].$_SERVER['QUERY_STRING'];
			if($_SERVER['REQUEST_URI']) $uri = $_SERVER['REQUEST_URI'];
			$baseUrl = $scheme.$_SERVER['HTTP_HOST'].$uri;
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
     * 构造获取token的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["app_id"] = $this->appId;
        $urlObj["redirect_uri"] = urlencode($redirectUrl);
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
            'code'=>$this->authCode,
        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        $result = $this->curlPost('https://openapi.alipay.com/gateway.do?charset='.$this->charset,$commonConfigs);
        $result = iconv('GBK','UTF-8',$result);
        return json_decode($result,true);
    }

}
// 是否支付宝客户端
function isInAlipayClient() {
    if( strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false ) {
        return true;
    }
    return false;
}
function getCurrentUrl()
{
    $scheme = $_SERVER['HTTPS']=='on' ? 'https://' : 'http://';
	$uri = $_SERVER['PHP_SELF'].$_SERVER['QUERY_STRING'];
	if($_SERVER['REQUEST_URI']) $uri = $_SERVER['REQUEST_URI'];
	$baseUrl = urlencode($scheme.$_SERVER['HTTP_HOST'].$uri);
    return $baseUrl;
}

