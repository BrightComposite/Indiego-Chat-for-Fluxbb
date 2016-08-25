<?php
/**
 *  @package    IndieGo Chat
 *  @file       requester.php - Обрабатывает запросы клиентов и возвращает требуемую информацию
 *  
 *  @version    1.0
 *  @date       2015-06-09
 *  @author     IndieGo (indiego.mttt@gmail.com)
 *  @sponsor    Volkula (volkula@gmail.com)
 *  
 *  @copyright  Copyright (c) IndieGo, 2015
 */

namespace IndieGoChat
{
    class Requester
    {
        public static function process($request)
        {
            switch($request)
            {
                case 'userdata':
                    echo json_encode(User::$current->getData());
                    return;
                case 'update':
                    echo json_encode(Command::flushCommands(Session::id()));
                    return;
                case 'messages':
                    echo json_encode(Chat::getMessages());
                    return;
                case 'addmessage':
                    if(User::$current->isBanned())
                        throw new \Exception("You were banned!");
                    
                    if(!isset($_GET["message"]))
                        throw new \Exception("There is no message provided!");
                    
                    $id = Chat::addMessage($_GET["message"]);
                    Command::broadcast('am', $id, false);
                    echo '{"status": "Ok"}';
                    
                    return;
                case 'editmessage':
                    if(!User::$current->isPowerful())
                        throw new \Exception("You have not rights to perform this action!");
                    
                    if(!isset($_GET["msg_id"]))
                        throw new \Exception("There is no message id provided!");
                    
                    if(!isset($_GET["message"]))
                        throw new \Exception("There is no message text provided!");
                    
                    Chat::updateMessage($_GET["msg_id"], $_GET["message"]);
                    Command::broadcast('um', $_GET["msg_id"]);
                    echo '{"status": "Ok"}';
                    
                    return;
                case 'deletemessage':
                    if(!User::$current->isPowerful())
                        throw new \Exception("You have not rights to perform this action!");
                    
                    if(!isset($_GET["msg_id"]))
                        throw new \Exception("There is no message id provided!");
                    
                    Chat::deleteMessage($_GET["msg_id"]);
                    Command::broadcast('dm', $_GET["msg_id"]);
                    echo '{"status": "Ok"}';
                    
                    return;
            }
        }
    }
}