<?php
/**
 *  @package    IndieGo Chat
 *  @file       command.php - Управляет командами чата. Команды накапливаются и выполняются для каждой сессии в отдельности
 *  при обращении клиента, относящегося к данной сессии
 *  
 *  @version    1.0
 *  @date       2015-06-11
 *  @author     IndieGo (indiego.mttt@gmail.com)
 *  @sponsor    Volkula (volkula@gmail.com)
 *  
 *  @copyright  Copyright (c) IndieGo, 2015
 */

namespace IndieGoChat
{
    class Command
    {
        public static function add($sid, $name, $params)
        {
            Chat::$db->query(
                'INSERT INTO $$tbl ' .
                '(session, command, params) ' .
                'VALUES ($$0, $$1, $$2)',
                array('tbl' => Config::DB_COMMANDS_TABLE),
                array(
                    $sid,
                    $name,
                    ($params ? (is_array($params) ? join(',', $params) : $params) : "")
                )
            );
        }
        
        public static function flushCommands($sid)
        {
            $result = Chat::$db->query(
                'SELECT * FROM $$tbl WHERE session=$$0',
                array('tbl' => Config::DB_COMMANDS_TABLE),
                array($sid)
            );
            
            $commands = array();
            
            foreach($result as &$row)
            {
                $command = array();
                $name = &$row['command'];
                $params = explode(',', $row['params']);
                
                $command['code'] = $name;
                
                switch($name)
                {
                    case 'am':
                        $command['message'] = Chat::getMessage($params[0]);
                        break;
                    
                    case 'um':
                        $command['message'] = Chat::getMessage($params[0]);
                        break;
                    
                    case 'dm':
                        $command['msg_id'] = $params[0];
                        break;
                    
                    default:
                        continue 2;
                }
                
                $commands[] = $command;
            }
            
            static::clearAll($sid);
            
            return $commands;
        }
        
        public static function broadcast($name, $params, $exclude_src = true)
        {
            $srcsid = Session::id();
            
            $result = Chat::$db->query(
                'SELECT sid FROM $$tbl ' . ($exclude_src ? 'WHERE sid <> $$0' : ''),
                array('tbl' => Config::DB_SESSIONS_TABLE),
                array($srcsid)
            );
            
            foreach($result as &$session)
                static::add($session['sid'], $name, $params);
        }
        
        public static function clearAll($sid)
        {
            Chat::$db->query(
                'DELETE FROM $$tbl WHERE session=$$0',
                array('tbl' => Config::DB_COMMANDS_TABLE),
                array($sid)
            );
        }
    }
}

?>