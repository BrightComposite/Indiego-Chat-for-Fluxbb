<?php
/**
 *  @package    IndieGo Chat
 *  @file       chat.php - Контроллер чата
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
    require_once 'environment.php';
    require_once 'session.php';
    require_once 'command.php';
    
    class Chat
    {
		/**
		 * Менеджер базы данных.
		 *
		 * @access public
		 * @var DatabaseManager
		 */
        public static $db = null;
            
		/**
		 * Карта смайликов.
		 *
		 * @access protected
		 * @var array
		 */
        public static $smilies = null;
            
        public static function initialize()
        {
            static::$db = Adapter::createDatabaseManager();
            
            Log::write("Initialize chat");
            date_default_timezone_set('UTC');
            
            Session::start();
            User::initialize();
            
            static::$smilies = json_decode(file_get_contents(__DIR__ . '/smilies.json'), true);
        }
        
        /**
         *  Добавляет дополнительные данные о сообщении для клиента
         */
        protected static function prepareMessage(&$msg, $should_return = false)
        {
            $msg['time'] = date('j.n.Y|H:i:s', $msg['date_time']);
            $user = User::getUser(intval($msg['user_id']));
            
            $msg['user_name'] = $user->getName();
            $msg['user_role'] = $user->getRoleName();
            
            if($should_return)
                return $msg;
        }
        
        /**
         *  Возвращает данные сообщения из БД по id
         */
        public static function getMessage($msg_id)
        {
            $result = static::$db->query(
                'SELECT * FROM $$tbl WHERE id=$$0',
                array('tbl' => Config::DB_MESSAGES_TABLE),
                array($msg_id)
            );
            
            if(count($result) == 0)
                return array();
            
            return static::prepareMessage($result[0], true);
        }
        
        /**
         *  Возвращает данные всех сообщений из БД
         */
        public static function getMessages()
        {
            $result = static::$db->query(
                'SELECT * FROM $$tbl ORDER BY date_time',
                array('tbl' => Config::DB_MESSAGES_TABLE)
            );	
            
            foreach($result as &$row)
               static::prepareMessage($row);
            
            return $result;
        }
        
        protected static function validateURL(&$text)
        {
            $matches = array();
            
            if(!preg_match('/(http\:\/\/|https\:\/\/|ftp\:\/\/)?(\S+)?/i', $text, $matches))
                return false;
            
            if($matches[1] == '')
                $matches[2] = 'http://';
            
            $text = $matches[1] . $matches[2];
            
            return true;
        }
        
        public static function translateBBCodes($text)
        {
            foreach(['i', 'b', 'u', 's'] as $code)
            {
                $text = preg_replace_callback("/\[$code\](.*)\[\/$code\]/U", function($matches) use($code)
                {
                    return "<$code>$matches[1]</$code>";
                }, $text);
            }
            
            //[url="example.com"]Example link[/url]
            
            $text = preg_replace_callback('/\[url(?:=&quot;(.+)&quot;)?\](.*)\[\/url\]/U', function($matches) use($code)
            {
                if(!$matches[1] && !$matches[2])
                    return $matches[0];
                
                if(!$matches[1])
                    $matches[1] = $matches[2];
                    
                if(!Chat::validateURL($matches[1]))
                    return $matches[0];
                
                if(!$matches[2])
                    $matches[2] = $matches[1];
                    
                $matches[2] = preg_replace('/http\:\/\/|https\:\/\/|ftp\:\/\//', '', $matches[2]);
                    
                return '<a href="' . $matches[1] . '">' . $matches[2] . '</a>';
            }, $text);
            
            //[color=#f00]Text[/color]
            
            $text = preg_replace_callback("/\[color=&quot;(#\w{3}|#\w{6}|rgb\(.*\)|rgba\(.*\)|\w+)&quot;\](.*)\[\/color\]/U", function($matches) use($code)
            {
                return '<span style="color: ' . $matches[1] . '">' . $matches[2] . '</span>';
            }, $text);
            
            $text = preg_replace_callback("/\[smile\](.+)\[\/smile\]/U", function($matches) use($code)
            {
                if(!array_key_exists($matches[1], Chat::$smilies))
                    return "[smile]$matches[1][/smile]";
                
                return '<img src="' . Chat::$smilies[$matches[1]] . '" alt="' . $matches[1] . '" />';
            }, $text);
            
            return $text;
        }
        
        /**
         *  Добавляет данные сообщения в БД
         */
        public static function addMessage($text)
        {
            $user = &User::$current;
            $time = time();
            
            $text = static::translateBBCodes(htmlspecialchars($text));
            
            Log::write("Add message: " . $text);
            
            static::$db->startTransaction();
            static::$db->query(
                'INSERT INTO $$tbl (user_id, date_time, text) ' .
                'VALUES ($$0, $$1, $$2)',
                array('tbl' => Config::DB_MESSAGES_TABLE),
                array(
                    $user->getId(),
                    $time,
                    $text
                )
            );
            
            $result = static::$db->query(
                'SELECT id FROM $$tbl',
                array('tbl' => Config::DB_MESSAGES_TABLE)
            );
            
            $count = count($result);
            
            $id = $result[$count - 1]['id'];
            
            if($count > Config::MESSAGE_STORAGE_MAX_LIMIT)
                static::limitMessages($count);
                
            static::$db->endTransaction();
            
            return $id;
        }
        
        /**
         *  Обновляет данные сообщения в БД
         */
        public static function updateMessage($msg_id, $msg_data)
        {
            static::$db->query(
                'UPDATE $$tbl ' .
                'SET text=$$0 WHERE id=$$1',
                array('tbl' => Config::DB_MESSAGES_TABLE),
                array(
                    $msg_data,
                    $msg_id
                )
            );
        }
        
        /**
         *  Удаляет данные сообщения из БД
         */
        public static function deleteMessage($msg_id)
        {
            static::$db->query(
                'DELETE FROM $$tbl WHERE id=$$0',
                array('tbl' => Config::DB_MESSAGES_TABLE),
                array($msg_id)
            );
        }
        
        /**
         *  Удаляет устаревшие сообщения в соответствии с конфигурацией чата
         */
        public static function limitMessages($count)
        {
            $result = static::$db->query(
                'DELETE FROM $$tbl ORDER BY date_time LIMIT ' . ($count - Config::MESSAGE_STORAGE_MIN_LIMIT),
                array('tbl' => Config::DB_MESSAGES_TABLE)
            );
        }
        
        public static function htmlInput()
        {
            return
                '<audio id="sound" src="" preload="auto"></audio>' .
                '<input id="user_id" type="hidden" value="' . User::$current->getId() . '" />' .
                '<input id="max_msgs" type="hidden" value="' . Config::MESSAGE_STORAGE_MIN_LIMIT . '" />' .
                '<input id="update_period" type="hidden" value="' . Config::CLIENT_UPDATE_PERIOD . '" />'
            ;
        }
        
        public static function htmlContents()
        {
            return '<div class="contents"></div>';
        }
        
        protected static function insertSmilies()
        {
            $str = '';
            
            if(!static::$smilies)
                return $str;
            
            foreach(static::$smilies as $key => $value)
            {
                $str .= '<img class="smile-inserter" src="' . $value . '" alt="' . $key . '" title="' . $key . '" />';
            }
            
            return $str;
        }
        
        protected static function insertBBs()
        {
            $str = '';
            
            foreach(array('b', 'i', 'u', 's', 'url') as $value)
                $str .= '<img class="bb-inserter" src="chat/images/' . $value . '.png" alt="' . $value . '" title="[' . $value . ']" />';
            
            return $str;
        }
        
        protected static function insertColors()
        {
            $str = '';
            $colors = array('red', 'orange', 'green', 'blue', 'purple');
            $names = array('Красный', 'Оранжевый', 'Зеленый', 'Синий', 'Фиолетовый');
            
            foreach(array_combine($colors, $names) as $key => $value)
                $str .= '<div class="color-inserter" title="' . $value . '" style="background-color: ' . $key . ';"></div>';
            
            return $str;
        }
        
        public static function htmlToolbar()
        {
            return
				'<div class="toolbar">' .
            (
                User::$current->isBanned() ?
                    Config::IS_BANNED
                :
            (
                User::$current->isRegistered() ?
                    '<label for="message-input">' . Config::LABEL_INPUT . '</label>' .
                    '<input id="message-input" type="text" />' .
                    '<input id="message-sender" type="button" value="' . Config::CAPTION_SEND . '"/>'
                :
                    Config::NOT_REGISTERED
            )
            ) .
                    '<div class="chat-controls">' .
            (
                (User::$current->isRegistered() && !User::$current->isBanned()) ?
                        '<div id="indiego-chat-smiles" class="chat-control" tabindex="0">' .
                            '<div class="panel">' .
                                static::insertSmilies() .
                            '</div>' .
                        '</div>' .
                        '<div id="indiego-chat-bb" class="chat-control" tabindex="0">' .
                            '<div class="panel">' .
                                static::insertBBs() .
                            '</div>' .
                        '</div>' .
                        '<div id="indiego-chat-colors" class="chat-control" tabindex="0">' .
                            '<div class="panel">' .
                                static::insertColors() .
                            '</div>' .
                        '</div>' .
                        '<div id="indiego-chat-settings" class="chat-control" tabindex="0">' .
                            '<div class="panel">' .
                                '<input id="sound-toggle" type="button"/>' .
                                '<input id="autoscroll-toggle" type="button"/>' .
                            '</div>' .
                        '</div>'
                :
                        ''
            ) .
                        '<div id="indiego-chat-help" class="chat-control" tabindex="0">' .
                            '<div class="panel">' .
                                '<div class="border">' .
                                    Config::HELP_TEXT .
                                '</div>' .
                            '</div>' .
                        '</div>' .
                    '</div>' .
				'</div>'
            ;
        }
    }
    
    Chat::initialize();
}

?>