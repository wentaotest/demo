<?php render_view('apps\modules\portal\views\header.php', array('param_1'=>'xxx'));?>

<h1>【微信公众号】创建自定义菜单</h1>

<a href="index.php?action=portal.wechat.menu">返回功能目录</a>

<form action="index.php?action=portal.wechat.createmenu" method="post">
	<fieldset>
		<legend>一级菜单(0)</legend>
		<p>
			<p>菜单名称：
				<input type="text" name="menu[0][name]" value="" />
			</p>
			
			<p id="button_type_section_0">菜单类型：
				<select name="menu[0][type]" onchange='changeMenuVarType(this.value, 0)'>
					<option value="click">点击推事件</option>
					<option value="view">跳转URL</option>
					<option value="scancode_push">扫码推事件</option>
					<option value="scancode_waitmsg">扫码推事件且弹出“消息接收中”提示框</option>
					<option value="pic_sysphoto">弹出系统拍照发图</option>
					<option value="pic_photo_or_album">弹出拍照或相册发图</option>
					<option value="pic_wexin">弹出微信相册发图器</option>
					<option value="location_select">弹出地理位置选择器</option>
				</select>
			</p>
			
			<p id="button_var_section_0">菜单参数：
				<input type='text' name='menu[0][key]' id='input_menu_var_0' value='' placeholder='key' />
			</p>
			
			<p id="sub_buttons_section_0">
				<input type="hidden" id="sub_buttons_0_cnt" value="0" />
			</p>
			
			<button type="button" onclick="addSubButton(0)">增加二级菜单</button>
		</p>
	</fieldset>
	
	<fieldset>
		<legend>一级菜单(1)</legend>
		<p>
			<p>菜单名称：
				<input type="text" name="menu[1][name]" value="" />
			</p>
			
			<p id="button_type_section_1">菜单类型：
				<select name="menu[1][type]" onchange='changeMenuVarType(this.value, 1)'>
					<option value="click">点击推事件</option>
					<option value="view">跳转URL</option>
					<option value="scancode_push">扫码推事件</option>
					<option value="scancode_waitmsg">扫码推事件且弹出“消息接收中”提示框</option>
					<option value="pic_sysphoto">弹出系统拍照发图</option>
					<option value="pic_photo_or_album">弹出拍照或相册发图</option>
					<option value="pic_weixin">弹出微信相册发图器</option>
					<option value="location_select">弹出地理位置选择器</option>
				</select>
			</p>
			
			<p id="button_var_section_1">菜单参数：
				<input type='text' name='menu[1][key]' id='input_menu_var_1' value='' placeholder='key' />
			</p>
			
			<p id="sub_buttons_section_1">
				<input type="hidden" id="sub_buttons_1_cnt" value="0" />
			</p>
			
			<button type="button" onclick="addSubButton(1)">增加二级菜单</button>
		</p>
	</fieldset>
	
	<fieldset>
		<legend>一级菜单(2)</legend>
		<p>
			<p>菜单名称：
				<input type="text" name="menu[2][name]" value="" />
			</p>
			
			<p id="button_type_section_0">菜单类型：
				<select name="menu[2][type]" onchange='changeMenuVarType(this.value, 2)'>
					<option value="click">点击推事件</option>
					<option value="view">跳转URL</option>
					<option value="scancode_push">扫码推事件</option>
					<option value="scancode_waitmsg">扫码推事件且弹出“消息接收中”提示框</option>
					<option value="pic_sysphoto">弹出系统拍照发图</option>
					<option value="pic_photo_or_album">弹出拍照或相册发图</option>
					<option value="pic_weixin">弹出微信相册发图器</option>
					<option value="location_select">弹出地理位置选择器</option>
				</select>
			</p>
			
			<p id="button_var_section_2">菜单参数：
				<input type='text' name='menu[2][key]' id='input_menu_var_2' value='' placeholder='key' />
			</p>
			
			<p id="sub_buttons_section_2">
				<input type="hidden" id="sub_buttons_2_cnt" value="0" />
			</p>
			
			<button type="button" onclick="addSubButton(2)">增加二级菜单</button>
		</p>
	</fieldset>
	
	<button type="submit">提交</button>
</form>

<?php render_view('apps\modules\portal\views\footer.php', array('param_1'=>'xxx'));?>

<script>
function addSubButton(parent_btn_num) {
	// 如果添加二级菜单，则需要清除一级菜单对应的类型和参数
	$("#button_type_section_"+parent_btn_num).remove();
	$("#button_var_section_"+parent_btn_num).remove();
	
	var sub_btn_num = $("#sub_buttons_"+parent_btn_num+"_cnt").val();

	var sub_btn_html = "<fieldset>" +
					   "	<legend>二级菜单("+parent_btn_num+"."+sub_btn_num+")</legend>" +
					   "	<p>菜单名称：<input type='text' name='menu["+parent_btn_num+"][sub_button]["+sub_btn_num+"][name]' value='' /></p>" +
					   "	<p>菜单类型：<select name='menu["+parent_btn_num+"][sub_button]["+sub_btn_num+"][type]' id='sel_menu_type_"+parent_btn_num+"_"+sub_btn_num+"'"+
					   "						onchange='changeMenuVarType(this.value,\""+parent_btn_num+"_"+sub_btn_num+"\")'>" +
					   "		<option value='click'>点击推事件</option>" +
					   "		<option value='view'>跳转URL</option>" +
					   "		<option value='scancode_push'>扫码推事件</option>" +
					   "		<option value='scancode_waitmsg'>扫码推事件且弹出“消息接收中”提示框</option>" +
					   "		<option value='pic_sysphoto'>弹出系统拍照发图</option>" +
					   "		<option value='pic_photo_or_album'>弹出拍照或相册发图</option>" +
					   "		<option value='pic_weixin'>弹出微信相册发图器</option>" +
					   "		<option value='location_select'>弹出地理位置选择器</option>" +
					   "	</select></p>" +
					   "	<p>菜单参数：<input type='text' name='menu["+parent_btn_num+"][sub_button]["+sub_btn_num+"][key]' id='input_menu_var_"+parent_btn_num+"_"+sub_btn_num+"' value='' placeholder='key' /></p>" +
					   "</fieldset>";
					   
	$("#sub_buttons_section_"+parent_btn_num).append(sub_btn_html);

	$("#sub_buttons_"+parent_btn_num+"_cnt").val(sub_btn_num*1+1);
}

function removeSubButton(btn_num) {
	
}

function changeMenuVarType(menu_type, id_suffix) {
	var input_name = $("#input_menu_var_"+id_suffix).attr("name");
	var placeholder = "";
	
	if (menu_type == 'view') {
		input_name = input_name.replace('key', 'url');
		placeholder = "url";
	} else {
		input_name = input_name.replace('url', 'key');
		placeholder = "key";
	}

	$("#input_menu_var_"+id_suffix).attr("name", input_name);
	$("#input_menu_var_"+id_suffix).attr("placeholder", placeholder);
	
}
</script>