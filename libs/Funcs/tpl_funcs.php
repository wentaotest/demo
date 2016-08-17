<?php 
/**
 * 此处的模板函数只与页面显示有关，与action的业务逻辑无直接关系
 * 
 * @author 安佰胜
 */

/**
 * 获取广告板内容（宽度100%铺满容器，画面比例不变）
 * 
 * @param $name string 广告板名字（英文）
 * 
 * @author 安佰胜
 */
function get_ad_borad($name) {
	
}

/**
 * 获取最热的资讯列表
 * 
 * @param string $type 类型
 * @param string $column 栏目
 * @param integer $limit 数量限制
 * 
 * @author 安佰胜
 */
function get_hot_news_list($type = null, $column = null, $limit = null) {
	
}

/**
 * 获取最新的资讯列表
 * 
 * @param string $type 类型
 * @param string $column 栏目
 * @param integer $limit 数量限制
 * 
 * @author 安佰胜
 */
function get_latest_news_list($type = null, $column = null, $limit = null) {
	
}

/**
 * 获取前一篇新闻的链接
 * 
 * @param Mongoid $news_id
 * 
 * @author 安佰胜
 */
function get_prev_news_link($news_id) {
	
}

/**
 * 获取下一篇新闻的链接
 *
 * @param Mongoid $news_id
 *
 * @author 安佰胜
 */
function get_next_news_link($news_id) {
	
}

/**
 * 获取推荐的资讯列表
 *
 * @param string $type 类型
 * @param string $column 栏目
 * @param integer $limit 数量限制
 *
 * @author 安佰胜
 */
function get_featured_news_list($type = null, $column = null, $limit = null) {
	
}

/**
 * 获取轮播图
 * 
 * @param string $page_name 页面名称
 * @param string $showcase 显示橱窗名称（针对一个页面多个轮播图的情况）
 * @param string $style 样式名称
 * 
 * @author 安佰胜
 */
function get_carousel($page_name, $showcase = null, $style = null) {
	
}

/**
 * 获取推荐的应用列表
 * 
 * @param string $cate
 * @param string $sub_cate
 * @param integer $limit
 * 
 * @author 安佰胜
 */
function get_featured_app_list($cate = null, $sub_cate = null, $limit = null) {
	
}

/**
 * 获取推荐的图片列表
 *
 * @param string $category
 * @param integer $limit
 *
 * @author 安佰胜
 */
function get_featured_img_list($category = null, $limit) {
	
}

/**
 * 获取推荐的视频列表
 *
 * @param string $cate
 * @param string $sub_cate
 * @param integer $limit
 *
 * @author 安佰胜
 */
function get_featured_video_list($cate = null, $sub_cate = null, $limit = null) {
	
}

/**
 * 获取推荐的专区列表
 *
 * @param integer $limit
 *
 * @author 安佰胜
 */
function get_featured_zone_list($limit = null) {
	
}

/**
 * 获取推荐的单页列表（活动、产品单页）
 *
 * @param integer $limit
 *
 * @author 安佰胜
 */
function get_featured_leftlet_list($limit = null) {

}


?>