根据db_conf_template.php的内容创建自己的数据库配置文件db_conf.php，并将db_conf.php文件放在svn的忽略列表中，禁止上传。

使用前使用rockmongo在数据库中创建admins集合，并添加以下记录作为后台登录账户，请自行替换成自己熟悉的密码加密串。

"access_type": "admin",
"enable": true,
"login_ip": "127.0.0.1",
"login_ts": ISODate("2016-02-19T07:16:53.0Z"),
"password": "0907873fced4e00e77f59175a08aca69",
"rmb_token": "c8937e70688bfb908c22070ca61cf712",
"rmb_token_expired_ts": ISODate("2016-02-24T11:14:49.0Z"),
"update_ts": ISODate("2016-02-19T07:16:53.0Z"),
"username": "admin" 