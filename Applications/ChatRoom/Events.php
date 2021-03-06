<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;
/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * 
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id) {
        // // 向当前client_id发送数据 
        // Gateway::sendToClient($client_id, "Hello $client_id\n");
        // // 向所有人发送
        // Gateway::sendToAll("$client_id login\n");
    }
    
    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message) {
        $message_data = json_decode($message, true);
        if (!$message_data) {
            return ;
        }
        // 根据类型执行不同的业务
        switch ($message_data['type']) {
            case 'pong':
                return;
            case 'login':
                $_SESSION['client_name'] = htmlspecialchars($message_data['client_name']);
                // 向当前client_id发送数据
                $data['type'] = 'welcome';
                $data['content'] = "你好 {$_SESSION['client_name']}";
                Gateway::sendToClient($client_id, json_encode($data, JSON_UNESCAPED_UNICODE));
                // 向所有人发送
                $data['type'] = 'login';
                $data['content'] = "{$_SESSION['client_name']} 加入了聊天室";
                Gateway::sendToAll(json_encode($data, JSON_UNESCAPED_UNICODE));
                self::getClientList();
                return;
            case 'chat':
                // 向所有人发送 
                $data['type'] = 'chat';
                $data['content'] = "{$_SESSION['client_name']} 说: " . htmlspecialchars($message_data['chat_data']);
                Gateway::sendToAll(json_encode($data, JSON_UNESCAPED_UNICODE));
                return;
            case 'img':
                // 向所有人发送
                $data['type'] = 'img';
                $data['content'] = "{$_SESSION['client_name']} 说: " . "<img src={$message_data['chat_data']}>";
                Gateway::sendToAll(json_encode($data, JSON_UNESCAPED_UNICODE));
                return;
        }
    }
   
    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id) {
        // 向所有人发送 
        $data['type'] = 'logout';
        $data['content'] = "{$_SESSION['client_name']} 退出了聊天室";
        GateWay::sendToAll(json_encode($data, JSON_UNESCAPED_UNICODE));
        self::getClientList();
    }

    public static function getClientList() {
        // 获取房间内所有用户列表
        $clients_name_list = [];
        $clients_list = Gateway::getAllClientSessions();
        foreach ($clients_list as $tmp_client_id => $item) {
            if (isset($item['client_name'])) {
                $clients_name_list[] = $item['client_name'];
            }
        }
        if ($clients_name_list) {
            $data['type'] = 'client_list';
            $data['content'] = $clients_name_list;
            Gateway::sendToAll(json_encode($data, JSON_UNESCAPED_UNICODE));
        }
    }
}
