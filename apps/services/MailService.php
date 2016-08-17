<?php namespace June\apps\services;

require '/Plugins/PHPMailer/PHPMailerAutoload.php';

class MailService {
	static private $_inst;

	private function __construct() {
	}

	static public function getInstance() {
		if (empty(self::$_inst)) {
			self::$_inst = new MailService();
		}

		return self::$_inst;
	}
    
    /**
     * 获取用户的邮箱地址
     * 
     * @param MongoId $uid 用户的MongoId
     * @param String $tc_name 用户所在的集合（表）名称
     * @return Mixed 用户邮箱地址
     * 
     * @author 安佰胜
     */
	public function getMailAddr($uid, $tc_name) {

	}
    
    /**
     * 获取邮件落款签名模板
     * 
     * @param String $type 模板类型
     * @return String $signature 落款签名内容
     * 
     * @author 安佰胜
     */
    public function getSignature($type) {
        
    }
    
    /**
     * 创建邮件信息内容
     * 
     * @param Array $user_info 用户信息
     * @param String $subject 邮件标题
     * @param String $content 消息正文内容
     * @param String $attachment 附件
     * @param String $signature 落款签名
     * @return Array $message 邮件信息内容
     * 
     * @author 安佰胜
     */
	public function createMessage($user_info, $subject, $content, $attachment=null, $signature=null) {
        $message = array();
        
        $message['to'] = $user_info['email'];
        $message['recipient'] = $user_info['username'];
        
        $message['subject'] = $subject;
        $message['body_content'] = $content . $signature;
        
        if ($attachment) {
            $message['attachment'] = $attachment;
        }
        
        return $message;
	}
    
    /**
     * 保存邮件信息
     * 
     * @param Array $message 邮件信息内容
     * @return Boolean $res 
     * 
     * @author 安佰胜
     */
    public function saveMessage($message) {
        
    }
    
    /**
     * 发送邮件
     * 
     * @param Array $message 邮件信息内容
     * @return Boolean $res
     * 
     * @author 安佰胜
     */
	public function send($message) {
		$method = C('mail_conf', 'method');
        $doSend = "_send_with_" . $method;
        
        $this->beforeSend($message);
        
        $res = $this->$doSend($message);
        
        $this->afterSend($message, $res['is_ok']);
        
        return $res;
	}
    
    /**
     * 使用php的mail函数发送邮件
     * 
     * @param Array $message 邮件内容
     * @return Array $send_res 发送结果
     * 
     * @author 安佰胜
     */
    private function _send_with_mail($message) {
        $mail = new PHPMailer();
        
        // 设置发信人地址及名称
        $mail->setFrom(C('mail_conf', 'from.mail_address'), C('mail_conf', 'from.sender_name'));
        
        // 设置回信人地址及名称
        if (isset($message['reply'])) {
            $mail->addReplyTo($message['reply']['to'], $message['reply']['recipient']);
        }
        
        // 设置收信人地址及名称
        $mail->addAddress($message['to'], $message['recipient']);
        
        // 设置邮件主题
        $mail->Subject = $message['subject'];
        
        // 发送HTML内容邮件
        $mail->msgHTML($message['body_content'], dirname(__FILE__));
        
        // 添加邮件附件
        if (isset($message['attachment'])) {
            $mail->addAttachment($message['attachment']);
        }
        
        // 发送
        if (!$mail->send()) {
            return array('is_ok' => false, 'error' => $mail->ErrorInfo);
        } else {
            return array('is_ok' => true, 'error' => '');
        }
    }
    
    /**
     * 使用smtp发送邮件
     * 
     * @param Array $message 邮件内容
     * @return Array $send_res 发送结果
     * 
     * @author 安佰胜
     */
    private function _send_with_smtp($message) {
        $mail = new \PHPMailer();
        
        // 使用smtp
        $mail->isSMTP();
        
        // 是否开启smtp调试模式
        // 0 = off (for production use)
        // 1 = client messages
        // 2 = client and server messages
        $mail->SMTPDebug = 0;
        
        // 调试模式输出格式
        $mail->Debugoutput = 'html';
        
        // 设置邮件服务器主机名
        $mail->Host = C('mail_conf', 'smtp.host');
        
        // 设置邮件服务器smtp端口，例如：25, 465 or 587
        $mail->Port = C('mail_conf', 'smtp.port');
        
        // 是否使用smtp认证
        $mail->SMTPAuth = true;
        
        // 设置smtp认证用户名
        $mail->Username = C('mail_conf', 'smtp.username');;
        
        // 设置smtp认证密码
        $mail->Password = C('mail_conf', 'smtp.password');;
        
        // 设置发信人地址及名称
        $mail->setFrom(C('mail_conf', 'from.mail_address'), C('mail_conf', 'from.sender_name'));
        
        // 设置回信人地址及名称
        if (isset($message['reply'])) {
            $mail->addReplyTo($message['reply']['to'], $message['reply']['recipient']);
        }
        
        // 设置收信人地址及名称
        $mail->addAddress($message['to'], $message['recipient']);
        
        // 设置邮件主题
        $mail->Subject = $message['subject'];
        
        // 发送HTML内容邮件
        $mail->msgHTML($message['body_content'], dirname(__FILE__));
        
        // 添加邮件附件
        if (isset($message['attachment'])) {
            $mail->addAttachment($message['attachment']);
        }//echo "<pre>";var_dump($mail);echo "</pre>";exit;
        
        // 发送
        if (!$mail->send()) {
            return array('is_ok' => false, 'error' => $mail->ErrorInfo);
        } else {
            return array('is_ok' => true, 'error' => '');
        }
    }
    
    /**
     * 邮件发送前触发的动作
     * 
     * @param Array $message 邮件信息内容
     * 
     * @author 安佰胜
     */
	public function beforeSend($message) {

	}
    
    /**
     * 邮件发送后触发的动作
     * 
     * @param Array $message 邮件信息内容
     * @param Boolean $is_successful 邮件是否发送成功
     * 
     * @author 安佰胜
     */
	public function afterSend($message, $is_successful) {

	}
}