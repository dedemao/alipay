<?php
header('Content-type:text/html; Charset=utf-8');
/*** 请填写以下配置信息 ***/
$appid = 'xxxxx';  //https://open.alipay.com 账户中心->密钥管理->开放平台密钥，填写添加了“当面付”的应用的APPID
$notifyUrl = 'http://www.xxx.com/alipay/notify.php';     //付款成功后的异步回调地址
$outTradeNo = uniqid();     //你自己的商品订单号，不能重复
$payAmount = 0.01;         //付款金额，单位:元
$orderName = '支付测试';    //订单标题
$signType = 'RSA2';       //签名算法类型，支持RSA2和RSA，推荐使用RSA2
$authCode = '';           //用户付款码（商户设备扫描用户二维码读取到的条码数字，或 点击支付宝APP-》付钱-》上面有显示一串数字）
$rsaPrivateKey='';		  //商户私钥，填写对应签名算法类型的私钥，如何生成密钥参考：https://docs.open.alipay.com/291/105971和https://docs.open.alipay.com/200/105310
/*** 配置结束 ***/
$aliPay = new AlipayService();
$aliPay->setAppid($appid);
$aliPay->setNotifyUrl($notifyUrl);
$aliPay->setRsaPrivateKey($rsaPrivateKey);
$aliPay->setTotalFee($payAmount);
$aliPay->setOutTradeNo($outTradeNo);
$aliPay->setOrderName($orderName);
$aliPay->setAuthCode($authCode);
$result = $aliPay->doPay();
$result = $result['alipay_trade_pay_response'];
if($result['code'] && $result['code']=='10000'){
    echo '支付成功';
}elseif($result['code'] && $result['code']=='10003'){
    echo '等待用户付款';
}else{
    echo $result['msg'].' : '.$result['sub_msg'];
}
class AlipayService
{
    protected $appId;
    protected $charset;
    protected $notifyUrl;
    //私钥值
    protected $rsaPrivateKey;
    protected $totalFee;
    protected $outTradeNo;
    protected $orderName;
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

    public function setRsaPrivateKey($rsaPrivateKey)
    {
        $this->rsaPrivateKey = $rsaPrivateKey;
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

    public function setAuthCode($authCode)
    {
        $this->authCode = $authCode;
    }

    /**
     * 发起订单
     * @return array
     */
    public function doPay()
    {
        //请求参数
        $requestConfigs = array(
            'out_trade_no'=>$this->outTradeNo,
            'scene'=>'bar_code',                //条码支付固定传入bar_code
            'auth_code'=>$this->authCode,         //用户付款码，25~30开头的长度为16~24位的数字，实际字符串长度以开发者获取的付款码长度为准
            'total_amount'=>$this->totalFee,      //单位 元
            'subject'=>$this->orderName,          //订单标题
            'store_id'=>'DEDEMAO_001',          //商户门店编号
            'timeout_express'=>'2m',            //交易超时时间
        );
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => 'alipay.trade.pay',             //接口名称
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
        $result = iconv('GBK','UTF-8',$result);
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
