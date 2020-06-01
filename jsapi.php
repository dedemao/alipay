<?php
header('Content-type:text/html; Charset=utf-8');
/*** 请填写以下配置信息 ***/
$appid = 'xxxxx';  //https://open.alipay.com 账户中心->密钥管理->开放平台密钥，填写添加了电脑网站支付的应用的APPID
$notifyUrl = 'http://www.xxx.com/alipay/notify.php';     //付款成功后的异步回调地址
$outTradeNo = uniqid();     //你自己的商品订单号，不能重复
$payAmount = 0.01;          //付款金额，单位:元
$orderName = '支付测试';    //订单标题
$signType = 'RSA2';			//签名算法类型，支持RSA2和RSA，推荐使用RSA2
//商户私钥，填写对应签名算法类型的私钥，如何生成密钥参考：https://docs.open.alipay.com/291/105971和https://docs.open.alipay.com/200/105310
$rsaPrivateKey='xxxxx';  
/*** 配置结束 ***/
$aliPay = new AlipayService();
$aliPay->setAppid($appid);
$aliPay->setNotifyUrl($notifyUrl);
$aliPay->setRsaPrivateKey($rsaPrivateKey);
$aliPay->setTotalFee($payAmount);
$aliPay->setOutTradeNo($outTradeNo);
$aliPay->setOrderName($orderName);
$orderStr = $aliPay->getOrderStr();
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
    <img src="http://qr.liantu.com/api.php?text=<?php echo getCurrentUrl()?>" />
<?php
    else:
?>
<h3>点击以下按钮唤起支付宝支付</h3>
<a href="javascript:void(0)" class="btn btn-primary btns-lg orderstrPay orderstr">点击调起支付宝支付</a>

<div class="alert alert-success" role="alert" style="margin-top:30px;display: none">

</div>
<?php
    endif;
?>
</div>
<script>
    function ready(callback) {
        // 如果jsbridge已经注入则直接调用
        if (window.AlipayJSBridge) {
            callback && callback();
        } else {
            // 如果没有注入则监听注入的事件
            document.addEventListener('AlipayJSBridgeReady', callback, false);
        }
    }
    ready(function(){
        document.querySelector('.orderstr').addEventListener('click', function() {
            AlipayJSBridge.call("tradePay", {
                orderStr: "<?php echo $orderStr?>"
            }, function(result) {
                if(result.resultCode!=9000){
                    //支付失败
                    alert(result.resultCode+"："+result.memo);
                }else{
                    //支付成功
                    var info = eval('(' + result.result + ')');
                    $(".alert-success").html("<strong>支付成功！</strong> 订单号："+info.alipay_trade_app_pay_response.out_trade_no+" 支付金额：￥"+info.alipay_trade_app_pay_response.total_amount);
                    $(".alert-success").show();
                }
                // alert(JSON.stringify(result));
            });
        });
    });
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
    /**
     * 获取orderStr
     * @return array
     */
    public function getOrderStr()
    {
        //请求参数
        $requestConfigs = array(
            'out_trade_no'=>$this->outTradeNo,
            'total_amount'=>$this->totalFee, //单位 元
            'subject'=>$this->orderName,  //订单标题
            'product_code'=>'QUICK_MSECURITY_PAY', //销售产品码，商家和支付宝签约的产品码，为固定值QUICK_MSECURITY_PAY
            'timeout_express'=>'2h',       //该笔订单允许的最晚付款时间，逾期将关闭交易。取值范围：1m～15d。m-分钟，h-小时，d-天，1c-当天（1c-当天的情况下，无论交易何时创建，都在0点关闭）。 该参数数值不接受小数点， 如 1.5h，可转换为 90m。
//            'store_id'=>'',                 //商户门店编号。该参数用于请求参数中以区分各门店，非必传项。
//            'extend_params'=>array(
//                'sys_service_provider_id'=>''       //系统商编号，该参数作为系统商返佣数据提取的依据，请填写系统商签约协议的PID
//            )
        );
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => 'alipay.trade.app.pay',             //接口名称
            'format' => 'JSON',
            'charset'=>$this->charset,
            'sign_type'=>'RSA2',
            'timestamp'=>date('Y-m-d H:i:s'),
            'version'=>'1.0',
            'notify_url' => $this->notifyUrl,
            'biz_content'=>json_encode($requestConfigs),
        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        $result = $this->buildOrderStr($commonConfigs);
        return $result;
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
