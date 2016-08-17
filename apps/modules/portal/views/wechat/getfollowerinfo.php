<?php render_view('apps\modules\portal\views\header.php', array('param_1'=>'xxx'));?>

<h1>【微信公众号】关注者信息（<?php echo $follower_info['nickname']; ?>）</h1>

<a href="index.php?action=portal.wechat.menu">返回功能目录</a>

<p>
	<label>OpenId：
		<span><?php echo $follower_info['openid']; ?></span>
	</label>
</p>

<p>
	<label>昵称：
		<span><?php echo $follower_info['nickname']; ?></span>
	</label>
</p>

<p>
	<label>头像：
		<img width="128px" height="128px" src="<?php echo $follower_info['headimgurl']; ?>" />
	</label>
</p>

<p>
	<label>性别：
		<span><?php echo $follower_info['sex']; ?></span>
	</label>
</p>

<p>
	<label>语言：
		<span><?php echo $follower_info['language']; ?></span>
	</label>
</p>

<p>
	<label>国家：
		<span><?php echo $follower_info['country']; ?></span>
	</label>
</p>

<p>
	<label>省份：
		<span><?php echo $follower_info['province']; ?></span>
	</label>
</p>

<p>
	<label>城市：
		<span><?php echo $follower_info['city']; ?></span>
	</label>
</p>

<p>
	<label>UnionId：
		<span><?php echo $follower_info['unionid']; ?></span>
	</label>
</p>

<p>
	<label>关注时间：
		<span><?php echo date('Y-m-d H:i:s', $follower_info['subscribe_time']); ?></span>
	</label>
</p>

<p>
	<label>分组编号：
		<span><?php echo $follower_info['groupid']; ?></span>
	</label>
</p>

<a href="index.php?action=portal.wechat.getfollowers&start_open_id=<?php echo $start_open_id; ?>">返回列表</a>

<?php render_view('apps\modules\portal\views\footer.php', array('param_1'=>'xxx'));?>