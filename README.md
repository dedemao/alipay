# alipay
一个PHP文件搞定支付宝支付系列

网上的很多PHP支付宝支付接入教程都颇为复杂，且需要配置和引入较多的文件，本人通过整理后给出一个单文件版的（代码只有200行左右），希望可以给各位想接入支付宝的带来些许帮助和借鉴意义。

一个PHP文件搞定微信支付系列请移步：https://github.com/dedemao/weixinPay

# 在线演示
https://www.dedemao.com/alipay/demo.html

# 环境依赖

PHP5.0以上，且需要开启CURL服务、SSL服务。

# 文件对应说明

pc.php	  电脑网站支付

wap.php   手机网站支付

qrcode.php   当面付（扫码支付）

barcode.php   当面付（条码支付）

transfers.php	单笔转账到支付宝账户

settle.php   交易结算（分账、分润）

authorize.php  网页授权获取用户信息

close.php 交易关闭接口

return.php   同步回调通知

notify.php   异步回调通知

# 注意事项

1.需要用到支付宝哪一种支付方式，就只下载对应的单个文件即可。

2.文件开头的配置信息必须完善


# 若对您有帮助，可以赞助并支持下作者哦，谢谢！

<p align="center">
    <img src="https://www.dedemao.com/uploads/zan.jpg" width="500px">
    <p align="center">联系邮箱：884358@qq.com</p>
</p>
