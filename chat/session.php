<?php
/**
 *  @package    IndieGo Chat
 *  @file       session.php - Управляет сессиями чата
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
    class Session
    {
        public static function start()
        {
            $sid = session_id();
            
            if($sid !== "")
                return true;
            
            session_start();
            
            $sid = session_id();
            
            if($sid === "")
                throw new \Exception("Can't start session!");
            
            $result = Chat::$db->query(
                'SELECT id FROM $$tbl WHERE sid = $$0',
                array('tbl' => Config::DB_SESSIONS_TABLE),
                array($sid)
            );
            
            if(count($result) > 0)
            {
                static::update($result[0]['id']);
                return true;
            }
            
            $result = Chat::$db->query(
                'INSERT INTO $$tbl (sid, last_time) VALUES ($$0, $$1)',
                array('tbl' => Config::DB_SESSIONS_TABLE),
                array($sid, time())
            );
            
            return true;
        }
        
        public static function id()
        {
            return session_id();
        }
        
        public static function has($key)
        {
            return isset($_SESSION[$key]);
        }
        
        public static function get($key)
        {
            return $_SESSION[$key];
        }
        
        public static function set($key, &$value)
        {
            $_SESSION[$key] = $value;
        }
        
        /**
         *  Обновляет временную метку сессии по идентификатору.
         *  Внимание! Имеется ввиду внутренний идентификатор сессии в БД, а не sid!
         *
         *  @param int $id - Внутренний идентификатор сессии
         */
        protected static function update($id)
        {
            $result = Chat::$db->query(
                'UPDATE $$tbl SET last_time = $$0 WHERE id = $$1',
                array(
                    'tbl' => Config::DB_SESSIONS_TABLE
                ),
                array(time(), $id)
            );
        }
        
        /**
         *  Проверяет актуальность сессии.
         *  
         *  @param array $session_data - Строка таблицы с сессиями
         */
        protected static function check($session_data)
        {
            if($session_data['last_time'] < time() - Config::CLIENT_REFRESH_PERIOD * 2)
            {
                $id = $session_data['id'];
                
                Command::clearAll($id);
                
                Chat::$db->query(
                    'DELETE FROM $$tbl WHERE id = $$0',
                    array('tbl' => Config::DB_SESSIONS_TABLE),
                    array($id)
                );
            }
        }
    }
}

?>