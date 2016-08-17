<?php
// 缓存记录过期时间
define('CACHE_LIFE_TIME', 3600);

// 缓存记录键名
define('CK_LOTTO_INFO',          'l_info_');      // 活动基本信息
define('CK_LOTTO_DETAIL',        'l_detail_');    // 活动详细信息
define('CK_RUNNING_LOTTOS_LIST', 'r_l_list_');    // 活动列表-正在进行中
define('CK_PRODUCT_INFO',        'p_info_');      // 商品基本信息
define('CK_PRODUCT_DESC',        'p_desc_');      // 商品图文详情
define('CK_LOTTO_CATE_NAMES',    'l_cate_names'); // 活动分类列表
define('CK_LOTTO_CATE_LIST',     'l_cate_list_'); // 分类活动列表
define('CK_LOTTO_ZONE_NAMES',    'l_zone_names'); // 活动专区列表
define('CK_LOTTO_ZONE_LIST',     'l_zone_list_'); // 专区活动列表
define('CK_LOTTO_CALC_RESULT',   'l_calc_res_');  // 活动结果计算过程
define('CK_WINNER_INFO',         'winner_info_'); // 幸运者信息

// 活动状态
define('L_STATUS_WAITING',  0); // 排队中
define('L_STATUS_RUNNING',  1); // 运行中
define('L_STATUS_SOLDOUT',  2); // 已售罄/揭晓中
define('L_STATUS_REVEALED', 3); // 已揭晓
define('L_STATUS_SHIPPED',  4); // 已派发

// 支付方式
define('PAY_METHOD_COINS',    0); // 余额支付
define('PAY_METHOD_WECHAT',   1); // 微信支付
define('PAY_METHOD_ALIPAY',   2); // 支付宝
define('PAY_METHOD_UNIONPAY', 3); // 银联支付

// 退款方式
define('REFUND_METHOD_COINS',    1); // 余额支付
define('REFUND_METHOD_WECHAT',   2); // 微信支付
define('REFUND_METHOD_ALIPAY',   3); // 支付宝
define('REFUND_METHOD_UNIONPAY', 4); // 银联支付