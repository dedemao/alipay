<?php
header('Content-type:text/html; Charset=utf-8');
$appid = 'xxxxx';  //https://open.alipay.com 账户中心->密钥管理->开放平台密钥，填写添加了电脑网站支付的应用的APPID
$returnUrl = 'http://www.xxx.com/alipay/return.php';     //付款成功后的同步回调地址
$notifyUrl = 'http://www.xxx.com/alipay/notify.php';     //付款成功后的异步回调地址
$outTradeNo = uniqid();     //你自己的商品订单号
$payAmount = 0.01;          //付款金额，单位:元
$orderName = '支付测试';    //订单标题
$signType = 'RSA2';       //签名算法类型，支持RSA2和RSA，推荐使用RSA2
//商户私钥，填写对应签名算法类型的私钥，如何生成密钥参考：https://docs.open.alipay.com/291/105971和https://docs.open.alipay.com/200/105310
$saPrivateKey='MIIEpAIBAAKCAQEA1MV+OY6MvGfXPM0MkpjT+FdzGmPOvVmX2wF3gjwQpeHBEUP9jLXhVS32fZ1iXI1e7WUGQ5tvXn28P8190kpOn/c/G5t2CAksUvemvF7uJN/N3Z1HFMdt3omvCd14K05lgcFYz7Z4c+A7ZJF5bPCB6oshjjUmbCY3hibuWzX/1j8AgsoD9lLyxoFqxLj98k5ZrYIhk900gMQs/WJ3A1FC09Dln9fuhBUyjtPHaml+4w+sdkdzxPktxdFrMcI7M7rNEwg25XtST5Z49oFpE84AlXM7+oC9jYvIpTGE00WomsgtakN039ucT/59Bup6pLkO08Rv85UXbqzGTcYAhNHLfQIDAQABAoIBAQCbuP258s+j8KgB8ty5yiqRPoeaj+O2h4Txn7A02/sfPQvNtCI0wsTpT5twsihULo+EVYTxJCitUn7df2sP5pyGzTEd5njLRtNu4Zvhj+Thjf8grERiu9b4oXI/WRzjLRxzi+uREi40OK+fWi0xgxDCdROY/eNiEdJfV8zpaqsUxG7VdwZIJQ/8d3Mi31OWv30kr9jfEd15DBInGJgSqR+qwrAB4pBSMcW8hL6PYlzoPi1ygceFjRrnbefG40zt0OUPSexQIgAmFvGqxTl5xo3dFEziGHdfWYsBKZ2M8ubAe+R6LcndxI+o2Hw4TNcC1tDeNMtjw7+h9S5aef5A8uWBAoGBAPxCLWPhUHCYlIXUz0D1SoolZs9WK7Kz1YSWnzqrpegN+foS5/ji93YylGE+KL31TwbnGQLAwknwMX3qTzmkvTovmy8jevXBsCSEFm81q0wG/35e1SKkTXL66RqB2y0xFLdcF3f9s8ZiEclqkYwNSHh0nqzREfIxMMAsj+3n2vHdAoGBANftYkZYrbs4iI/ZcjmBYguYikNfNmrD+Ta6ckOGZqsHfwXJCAz1rF4/XCqVAc9nxuzJR/72qkn9z07uH6qSZCqlZDRkiiKaK2UVqFDB+0abMk/TGHXuMmdvMkyj2jEZxG2rkg0kmg4qYkkg/5tGG1On/0GeZNVPu8JpsFr1pDYhAoGBANr8pCTKC6fDfWP1C3qrtmrY7zhc6RB4d4pjq5UmP5+EypaiZQi2F/dfD1qfuIS3eURXyGmQZtoDDyPtDZvP/ImPnFs+pNbFryD0HfmrEKquhIvyzXoGQknnsgbV5iy3KCTJaII9FxzINAKzZei7+0a+jqUd1kN3Gogp50Sze2ltAoGARaM5Xpaa8RZ6dGocfI9Nn4/Ch5fdZPFvHkdjMoPV+LKiNKtw/Tz+KiclAlasDsfZT+RaY9AJe3NvuHTzoX807swIVR1Xr3EpLaCed+0XrN3AjB34dZAskU87WZw+cjdtMjFzGOoFBSyGJi+OP/WMOp6jo/YBbwoX88tCJROzsgECgYAT8pHHIyPt5Y/5pDb8EDvD3XNES1fBkfZffSoAodsrkeoKgrsKl+9M3rcGX+S9dscyoH0ur3BFTMHtIOOhC5qytt+BhMHIP5mAs4di4u/joQCWQbUyrUggVK5it+6BFgAT+jeB7zTAUtgGpTVFq3kLbV0NZ+XQyEHVlnoJnHYpQg==';
$aliPay = new AlipayService($appid,$returnUrl,$notifyUrl,$saPrivateKey);
$sHtml = $aliPay->doPay($payAmount,$outTradeNo,$orderName,$returnUrl,$notifyUrl);
echo $sHtml;

class AlipayService
{
    protected $appId;
    protected $returnUrl;
    protected $notifyUrl;
    //私钥文件路径
    protected $rsaPrivateKeyFilePath;
    //私钥值
    protected $rsaPrivateKey;

    public function __construct($appid, $returnUrl, $notifyUrl,$saPrivateKey)
    {
        $this->appId = $appid;
        $this->returnUrl = $returnUrl;
        $this->notifyUrl = $notifyUrl;
        $this->charset = 'utf8';
        $this->rsaPrivateKey=$saPrivateKey;
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
        //请求参数
        $requestConfigs = array(
            'out_trade_no'=>$outTradeNo,
            'product_code'=>'FAST_INSTANT_TRADE_PAY',
            'total_amount'=>$totalFee, //单位 元
            'subject'=>$orderName,  //订单标题
        );
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => 'alipay.trade.page.pay',             //接口名称
            'format' => 'JSON',
            'return_url' => $returnUrl,
            'charset'=>$this->charset,
            'sign_type'=>'RSA2',
            'timestamp'=>date('Y-m-d H:i:s'),
            'version'=>'1.0',
            'notify_url' => $notifyUrl,
            'biz_content'=>json_encode($requestConfigs),
        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        return $this->buildRequestForm($commonConfigs);
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp 请求参数数组
     * @return 提交表单HTML文本
     */
    protected function buildRequestForm($para_temp) {

        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='https://openapi.alipay.com/gateway.do?charset=".$this->charset."' method='POST'>";
        while (list ($key, $val) = each ($para_temp)) {
            if (false === $this->checkEmpty($val)) {
                $val = str_replace("'","&apos;",$val);
                $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
            }
        }
        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit' value='ok' style='display:none;''></form>";
        $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
        return $sHtml;
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
}
