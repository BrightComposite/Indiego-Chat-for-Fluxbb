<?php
/**
 *  @package    IndieGo Chat
 *  @file       log.php - Простой лог
 *  
 *  @version    1.0
 *  @date       2015-06-09
 *  @copyright  Copyright (c) IndieGo, 2015
 */

namespace IndieGoChat
{
    class Log
    {
        /**
         *  Объект для автоматического освобождения ресурсов (RAII)
         *  @access protected
         *  @var Log
         */
        protected static $instance;
        
        /**
         *  Файловый дескриптор лога
         *  @access protected
         *  @var resource
         */
        protected $fd;
        
        const new_line =
"
";
        
        protected function __construct($filename)
        {
            $this->fd = fopen($filename, "a");
        }
        
        public function __destruct()
        {
            fclose($this->fd);
        }
        
        public static function start($filename)
        {
            if(!Config::ENABLE_LOG)
                return;
            
            static::$instance = new Log($filename);
        }
        
        public static function write($text)
        {
            if(!Config::ENABLE_LOG)
                return;
            
            fwrite(static::$instance->fd, $text . static::new_line);
        }
    }
}

?>