<?php render_view('apps\modules\portal\views\header.php', array('param_1'=>'xxx'));?>

<script type="text/javascript" src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script type="text/javascript" src="statics/js/zepto/1.1.6/zepto.min.js"></script>

<h1>【微信公众号】Js_Api测试页面</h1>

<ul>
  <li><button id="checkJsApi">checkJsApi</button></li>
  <li><button id="onMenuShareTimeline">onMenuShareTimeline</button></li>
</ul>


<script type="text/javascript">
wx.config({
    debug: true, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
    appId: '<?php echo $sign_package['appId']; ?>', // 必填，公众号的唯一标识
    timestamp: <?php echo $sign_package['timestamp']; ?>, // 必填，生成签名的时间戳
    nonceStr: '<?php echo $sign_package['nonceStr']; ?>', // 必填，生成签名的随机串
    signature: '<?php echo $sign_package['signature']; ?>',// 必填，签名，见附录1
    jsApiList: ['checkJsApi',
                'onMenuShareTimeline',
                'onMenuShareAppMessage',
                'onMenuShareQQ',
                'onMenuShareWeibo',
                'onMenuShareQZone',
                'hideMenuItems',
                'showMenuItems',
                'hideAllNonBaseMenuItem',
                'showAllNonBaseMenuItem',
                'translateVoice',
                'startRecord',
                'stopRecord',
                'onVoiceRecordEnd',
                'playVoice',
                'onVoicePlayEnd',
                'pauseVoice',
                'stopVoice',
                'uploadVoice',
                'downloadVoice',
                'chooseImage',
                'previewImage',
                'uploadImage',
                'downloadImage',
                'getNetworkType',
                'openLocation',
                'getLocation',
                'hideOptionMenu',
                'showOptionMenu',
                'closeWindow',
                'scanQRCode',
                'chooseWXPay',
                'openProductSpecificView',
                'addCard',
                'chooseCard',
                'openCard'] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
});

wx.ready(function () {
	  // 1 判断当前版本是否支持指定 JS 接口，支持批量判断
	  document.querySelector('#checkJsApi').onclick = function () {
	    wx.checkJsApi({
	      jsApiList: [
	        'getNetworkType',
	        'previewImage'
	      ],
	      success: function (res) {
	        alert(JSON.stringify(res));
	      }
	    });
	  };
});

wx.error(function (res) {
	alert(res.errMsg);
});

</script>

<?php render_view('apps\modules\portal\views\footer.php', array('param_1'=>'xxx'));?>