<?php
header('Content-type:text/html; Charset=utf-8');
$pid = 'xxxxx';  //https://open.alipay.com 账户中心->密钥管理->mapi网关产品密钥->合作伙伴身份（PID）
$returnUrl = 'http://www.xxx.com/alipay/return.php';     //付款成功后的同步回调地址
$notifyUrl = 'http://www.xxx.com/alipay/notify.php';     //付款成功后的异步回调地址
$outTradeNo = uniqid();     //你自己的商品订单号
$payAmount = 0.01;          //付款金额，单位:元
$orderName = '支付测试';    //订单标题
$signType = 'RSA';       //签名算法类型，RSA或MD5，推荐使用RSA
//商户私钥，对应签名算法填写
$privateKey='MIICWwIBAAKBgQCaYZmbUPalRCqo6a5x7Wbl4x2ZaYbTZ5Vmh0h2LDnwhsrHW6yLAe/DQNtc/kwWGXdcUvAZEz+KCmIdDeRDXIoK/fKMkC2/a/kCeimrp7X+EPaSFCEYI/OZ9Kr99LIOlaWGbs5UM8YQaPY8CUz935/0u4vov/zCUr/OGV3Z7vvIRQIDAQABAoGAMgzYXulFvVi3N+Hiw17DEF6sVvNGWb1oS0Aqtt0pv5gDn9RRwRNUpwmc1K5A/p0s/EqLOqXp4JfzLRY23bvfqKW19GzNfxB16wyeN46U+lYlFDfhbGOb9GMqqCdROXCMkfesbQjrBKP/KD2xkT/u5O5qIMunI5P4HZ1bqXwaUMECQQDKMgdEi+Pf0oZkptvVfI9ogNoj9bSStFazY1TcbDsY8smmwgQ73FLE4+43tr2j+cBWqSc8Ead27Zs9FBcByfgRAkEAw3Ze+KQvQ0SBCqn0w94p9FytQ3FaU1k3Pa1DjFV6+snRDnr71v7FbJn4W9AGgFl609FnNmW/GBGOWmMaE/tg9QJAP/AaBD8ITBrSjLmHArdk0fMNCBoGg+2rDQb4Ksu+1GDSC32GwTcmpUQ+bDfJODUd8UmEMqaSgjUYxfO4YsRvQQJAE1diPG5asuhTcI+yxbL1KdYF2xebXYYFflfnyDopKinQQwOOag7Z0E5IEiW6jvbLvawJ74sP5rzN/kmyKeoFBQJAMhwuIKyNob0qBGRFL4NaNiRGuEbVChlIM5dbzC8OTAhwU5jAbexfIUNxweiZB62vkpLMgeEUWOSVb+ngggxrpA==';
$aliPay = new AlipayService($pid,$returnUrl,$notifyUrl,$signType,$privateKey);
$sHtml = $aliPay->doPay($payAmount,$outTradeNo,$orderName,$returnUrl,$notifyUrl);
echo $sHtml;

class AlipayService
{
    protected $appId;
    protected $returnUrl;
    protected $notifyUrl;
    protected $charset;
    protected $signType;
    //私钥值
    protected $privateKey;

    public function __construct($appid, $returnUrl, $notifyUrl,$signType,$privateKey)
    {
        $this->appId = $appid;
        $this->returnUrl = $returnUrl;
        $this->notifyUrl = $notifyUrl;
        $this->charset = 'utf8';
        $this->signType = $signType;
        $this->privateKey=$privateKey;
    }
    /**
     * 发起订单
     * @param float $totalFee 收款总费用 单位元
     * @param string $outTradeNo 唯一的订单号
     * @param string $orderName 订单名称
     * @param string $notifyUrl 支付结果通知url 不要有问号
     * @param string $timestamp 订单发起时间
     * @return array
     */
    public function doPay($totalFee, $outTradeNo, $orderName, $returnUrl,$notifyUrl)
    {
        $requestConfigs = array(
            'partner'=>$this->appId,
            'service' => 'create_direct_pay_by_user',
            '_input_charset'=>strtolower($this->charset),       //gbk或者utf8，小写
            'sign_type'=>strtoupper($this->signType),     //RSA或MD5，必须大写。
            'return_url' => $returnUrl,
            'notify_url' => $notifyUrl,
            'out_trade_no'=>$outTradeNo,
            'total_fee'=>$totalFee, //单位 元
            'subject'=>$orderName,  //订单标题
            'payment_type'=>1,
            'seller_id'=>$this->appId,         //卖家支付宝用户号（以2088开头的纯16位数字）
        );
        return $this->buildRequestForm($requestConfigs);
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp 请求参数数组
     * @return 提交表单HTML文本
     */
    function buildRequestForm($para_temp) {
        //待请求参数数组
        $para = $this->buildRequestPara($para_temp);

        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='https://mapi.alipay.com/gateway.do?_input_charset=".trim(strtolower($this->charset))."' method='post'>";
        while (list ($key, $val) = each ($para)) {
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }
        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit'  value='提交' style='display:none;'></form>";

        $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";

        return $sHtml;
    }

    /**
     * 生成要请求给支付宝的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
    function buildRequestPara($para_temp) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //生成签名结果
        $mysign = $this->buildRequestMysign($para_sort);

        //签名结果与签名方式加入请求提交参数组中
        $para_sort['sign'] = $mysign;
        $para_sort['sign_type'] = strtoupper(trim($this->signType));

        return $para_sort;
    }

    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 生成签名结果
     * @param $para_sort 已排序要签名的数组
     * return 签名结果字符串
     */
    private function buildRequestMysign($para_sort) {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);

        $mysign = "";
        switch (strtoupper(trim($this->signType))) {
            case "MD5" :
                $mysign = $this->md5Sign($prestr, $this->privateKey);
                break;
            case "RSA" :
            default :
                $mysign = $this->rsaSign($prestr, $this->privateKey);
                break;
        }
        return $mysign;
    }

    /**
     * RSA签名
     * @param $data 待签名数据
     * @param $private_key 商户私钥字符串
     * return 签名结果
     */
    private function rsaSign($data, $private_key) {
        //以下为了初始化私钥，保证在您填写私钥时不管是带格式还是不带格式都可以通过验证。
        $private_key=str_replace("-----BEGIN RSA PRIVATE KEY-----","",$private_key);
        $private_key=str_replace("-----END RSA PRIVATE KEY-----","",$private_key);
        $private_key=str_replace("\n","",$private_key);

        $private_key="-----BEGIN RSA PRIVATE KEY-----".PHP_EOL .wordwrap($private_key, 64, "\n", true). PHP_EOL."-----END RSA PRIVATE KEY-----";

        $res=openssl_get_privatekey($private_key);

        if($res)
        {
            openssl_sign($data, $sign,$res);
        }
        else {
            echo "您的私钥格式不正确!"."<br/>"."The format of your private_key is incorrect!";
            exit();
        }
        openssl_free_key($res);
        //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * 签名字符串
     * @param $prestr 需要签名的字符串
     * @param $key 私钥
     * return 签名结果
     */
    function md5Sign($prestr, $key) {
        $prestr = $prestr . $key;
        return md5($prestr);
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    private function createLinkstring($para) {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key."=".$val."&";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

        return $arg;
    }

    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    private function paraFilter($para) {
        $para_filter = array();
        while (list ($key, $val) = each ($para)) {
            if($key == "sign" || $key == "sign_type" || $val == "")continue;
            else	$para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }
}
