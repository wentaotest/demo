<?php namespace June\apps\services;

use June\apps\services\WxManagerService;
use June\Core\JuneException;
/**
 * 微信公众号素材服务类
 *
 * @author 安佰胜
 */

class WxMaterialService {
	static private $_inst;
	private $_db;

	private function __construct() {
		$this->_db = june_get_apps_db_conn_pool();
	}

	static public function getInstance() {
		if (empty(self::$_inst)) {
			self::$_inst = new WxMaterialService();
		}

		return self::$_inst;
	}
	
	/**
	 * 添加临时素材（上传多媒体文件）
	 * 
	 * 注意：
	 * 1、临时素材在微信服务器上只能保存3天，如需长久保存，则应及时转存至本地或自己的服务器；
	 * 2、微信服务器返回的media_id是可复用的；
	 * 3、图片大小不超过2M，支持bmp/png/jpeg/jpg/gif格式，语音大小不超过5M，长度不超过60秒，支持mp3/wma/wav/amr格式
	 * 
	 * @param string $media_type 媒体文件类型，包括image、voice、视频video和thumb（主要用于视频与音乐格式的缩略图）
	 * @param file $media 媒体文件
	 * @return $ret 返回结果 包含media_id
	 * 
	 * @author 安佰胜
	 */
	public function createMedia($media_type, $media) {
		$access_token = WxManagerService::getInstance()->getAccessToken();
		
		if (empty($media_type) || empty($media)) {
			throw new JuneException('素材类型及素材文件不能为空！');
		}
		
		$url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token=$access_token";
		$post_data = array('type'  => $media_type, 'media' => $media);
		$options = array('url' => $url, 'method' => 'POST', 'post_data' => $post_data, 'data_type' => 'json');
		
		$res = curl($options);
		
		$ret = $res;
		if (empty($res['errcode'])) {
			$ret['is_ok'] = true;
		} else {
			$ret['is_false'] = false;
		}
		
		return $ret;
	}
	
	/**
	 * 获得临时素材（下载多媒体文件）
	 * 
	 * 注意：视频文件不支持https下载，调用该接口需http协议。
	 * 
	 * @param string $media_id
	 * @return array $media
	 * 
	 * @author 安佰胜
	 */
	public function getMedia($media_id) {
		$access_token = WxManagerService::getInstance()->getAccessToken();
		
		if (empty($media_id)) {
			throw new JuneException('素材编号不能为空！');
		}
		
		$url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=$access_token&media_id=$media_id)";
		$options = array('url' => $url, 'method' => 'GET', 'data_type' => 'json');
		
		$res = curl($options);
		
		if (empty($res['errcode'])) {
			$ret = array('is_ok' => true, 'media_data' => $res);
		} else {
			$ret = $res;
			$ret['is_ok'] = false;
		}
		
		return $ret;
	}
	
	/**
	 * 上传图文消息内的图片获取URL
	 * 
	 * 注意：
	 * 1、本接口所上传的图片不占用公众号的素材库中图片数量的5000个的限制
	 * 2、图片仅支持jpg/png格式，大小必须在1MB以下
	 * 
	 * @param file $img_file
	 * @return array $ret
	 * 
	 * @author 安佰胜
	 */
	public function uploadNewsImage($img_file) {
		$access_token = WxManagerService::getInstance()->getAccessToken();
		
		if (empty($img_file)) {
			throw new JuneException('图片文件不能为空！');
		}
		
		$url = "https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=$access_token";
		$options = array('url' => $url, 'method' => 'POST', 'post_data' => array('media' => $img_file), 'data_type' => 'json');
		
		$res = curl($options);
		
		if (empty($res['errcode'])) {
			$ret = array('is_ok' => true, 'url' => $res['url']);
		} else {
			$ret = $res;
			$ret['is_ok'] = false;
		}
		
		return $ret;
	}
	
	/**
	 * 创建永久图文素材
	 * 
	 * 注意：
	 * 1、永久素材的数量是有上限的，请谨慎新增。图文消息素材和图片素材的上限为5000，其他类型为1000
	 * 2、图片大小不超过2M，支持bmp/png/jpeg/jpg/gif格式，语音大小不超过5M，长度不超过60秒，支持mp3/wma/wav/amr格式
	 * 3、图文消息内容里的图片不占用图片素材的数量限制
	 * 
	 * @param array $articles 图文内容
	 * @return array $ret
	 * 
	 * @author 安佰胜
	 */
	public function createNews($articles) {
		$access_token = WxManagerService::getInstance()->getAccessToken();
		
		if (empty($articles)) {
			throw new JuneException('图文消息内容不能为空！');
		}
		
		$url = "https://api.weixin.qq.com/cgi-bin/material/add_news?access_token=$access_token";
		$options = array('url' => $url, 'method' => 'POST', 'post_data' => array('articles' => $articles), 'data_type' => 'json');
		
		$res = curl($options);
		
		if (empty($res['errcode'])) {
			$ret = array('is_ok' => true, 'media_id' => $res['media_id']);
		} else {
			$ret = $res;
			$ret['is_ok'] = false;
		}
		
		return $ret;
	}
	
	/**
	 * 获取图文永久素材
	 * 
	 * @param string $media_id
	 * @return array $ret
	 * 
	 * 注意！$ret['news_item']的结构如下：
	 * 
	 * [
	 * 	{
	 * 		"title":TITLE,
	 * 		"thumb_media_id"::THUMB_MEDIA_ID,
	 * 		"show_cover_pic":SHOW_COVER_PIC(0/1),
	 * 		"author":AUTHOR,
	 * 		"digest":DIGEST,
	 * 		"content":CONTENT,
	 * 		"url":URL,
	 * 		"content_source_url":CONTENT_SOURCE_URL
	 * 	},
	 * 	//多图文消息有多篇文章
	 * ]
	 * 
	 * @author 安佰胜
	 */
	public function getNews($media_id) {
		$res = $this->getMaterial($media_id);
		
		$ret = $res;
		if ($res['is_ok']) {
			$ret['news_item'] = $res['media_data']['news_item'];
			unset($ret['media_data']);
		}
		
		return $ret;
	}
	
	/**
	 * 更新图文永久素材
	 * 
	 * @param string $media_id
	 * @param integer $index
	 * @param array $article
	 * @throws \Exception
	 * @return array $ret
	 * 
	 * @author 安佰胜
	 */
	public function updateNews($media_id, $index, $article) {
		$access_token = WxManagerService::getInstance()->getAccessToken();
		
		if (empty($media_id) || empty($index) || empty($article)) {
			throw new JuneException('图文素材参数不能为空！');
		}
		
		$url = "https://api.weixin.qq.com/cgi-bin/material/update_news?access_token=$access_token";
		$post_data = array('index' => $index, 'articles' => $article);
		$options = array('url' => $url, 'method' => 'POST', 'post_data' => $post_data, 'data_type' => 'json');
		
		$res = curl($options);
		
		if (empty($res['errcode'])) {
			$ret = array('is_ok' => true);
		} else {
			$ret = $res;
			$ret['is_ok'] = false;
		}
		
		return $ret;
	}
	
	/**
	 * 获取视频永久素材
	 * 
	 * @param string $media_id
	 * @return array $ret
	 * 
	 * 注意！$ret['media_data']的结构如下：
	 * {
	 * 		"title":TITLE,
	 * 		"description":DESCRIPTION,
	 * 		"down_url":DOWN_URL,
	 * }
	 * 
	 * @author 安佰胜
	 */
	public function getVideo($media_id) {
		$res = $this->getMaterial($media_id);
		
		$ret = $res;
		if ($res['is_ok']) {
			$ret['news_item'] = $res['media_data'];
			unset($ret['media_data']);
		}
		
		return $ret;
	}
	
	/**
	 * 新增其他类型永久素材
	 * 
	 * @param string $type 素材类型，图片（image）、语音（voice）、视频（video）和缩略图（thumb）
	 * @param file $media 素材文件
	 * @throws \Exception
	 * 
	 * @author 安佰胜
	 */
	public function createMaterial($type, $media, $description = array('title' => '', 'introduction' => '')) {
		$access_token = WxManagerService::getInstance()->getAccessToken();
        
        if (empty($type) || empty($media)) {
            throw new JuneException('素材类型和素材文件不能为空！');
        }
        
        $url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=$access_token";
        $post_data = array('type'  => $type, 'media' => $media);
        
        // 如果是永久视频素材，需要添加额外的描述信息
        if ($type == 'video') {
        	$post_data['description'] = json_encode($description);
        }
        
        $options = array('url' => $url, 'method' => 'POST', 'post_data' => $post_data, 'data_type' => 'json');
        
        $res = curl($options);
        
        if (empty($res['errcode'])) {
			$ret = array('is_ok' => true, 'media_id' => $res['media_id']);
		} else {
			$ret = $res;
			$ret['is_ok'] = false;
		}
		
		return $ret;
	}
	
	/**
	 * 获取永久素材（主要是图片、语音和缩略图）
	 * 
	 * @param string $media_id
	 * @throws \Exception
	 * @return array $ret 如果是图片、语音或缩略图，则$ret['media_data']的内容为素材文件数据，否则为素材描述信息
	 * 
	 * @author 安佰胜
	 */
	public function getMaterial($media_id) {
		$access_token = WxManagerService::getInstance()->getAccessToken();
		
		if (empty($media_id)) {
			throw new JuneException('素材编号不能为空！');
		}
		
		$url = "https://api.weixin.qq.com/cgi-bin/material/get_material?access_token=$access_token&media_id=$media_id";
		$options = array('url' => $url, 'method' => 'GET', 'data_type' => 'json');
		
		$res = curl($options);
		
		$ret = array();
		if (empty($res['errcode'])) {
			$ret = array('is_ok' => true, 'media_data' => $res);
		} else {
			$ret = $res;
			$ret['is_ok'] = false;
		}
		
		return $ret;
	}
	
	/**
	 * 删除永久素材
	 * 
	 * @param string $media_id
	 * @throws \Exception
	 * @return $ret
	 * 
	 * @author 安佰胜
	 */
	public function deleteMedia($media_id) {
		$access_token = WxManagerService::getInstance()->getAccessToken();
		
		if (empty($media_id)) {
			throw new JuneException('素材编号不能为空！');
		}
		
		$url = "https://api.weixin.qq.com/cgi-bin/material/del_material?access_token=$access_token&media_id=$media_id";
		$options = array('url' => $url, 'method' => 'GET', 'data_type' => 'json');
		
		$res = curl($options);
		
		$ret = array();
		if (empty($res['errcode'])) {
			$ret = array('is_ok' => true);
		} else {
			$ret = $res;
			$ret['is_ok'] = false;
		}
		
		return $ret;
	}
	
	/**
	 * 获取素材总数
	 * 
	 * @author 安佰胜
	 */
	public function getMaterialCount() {
		$access_token = WxManagerService::getInstance()->getAccessToken();
		
		$url = "https://api.weixin.qq.com/cgi-bin/material/get_materialcount?access_token=$access_token";
		$options = array('url' => $url, 'method' => 'GET', 'data_type' => 'json');
		
		$res = curl($options);
		
		$ret = $res;
		if (empty($res['errcode'])) {
			$ret = array('is_ok' => true);
		} else {
			$ret = $res;
			$ret['is_ok'] = false;
		}
		
		return $ret;
	}
	
	/**
	 * 获得素材列表
	 * 
	 * @author 安佰胜
	 */
	public function getMaterialList() {
		$access_token = WxManagerService::getInstance()->getAccessToken();
		
		$url = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=$access_token";
		$options = array('url' => $url, 'method' => 'GET', 'data_type' => 'json');
		
		$res = curl($options);
		
		$ret = $res;
		if (empty($res['errcode'])) {
			$ret['is_ok'] = true;
		} else {
			$ret['is_ok'] = false;
		}
		
		return $ret;
	}
	
}
?>