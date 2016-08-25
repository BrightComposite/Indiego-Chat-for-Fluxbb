<?php
/**
 *  @package    IndieGo Chat
 *  @file       db.php - Объявляет интерфейс для запросов к БД
 *  
 *  @version    1.0
 *  @date       2015-06-010
 *  @author     IndieGo (indiego.mttt@gmail.com)
 *  @sponsor    Volkula (volkula@gmail.com)
 *  
 *  @copyright  Copyright (c) IndieGo, 2015
 */

namespace IndieGoChat
{
    abstract class DatabaseManager
    {
        /**
         *  Выполняет запрос к базе данных.
         *  Названия таблиц в запросе должны быть представлены в таком виде - $$table_key
         *  Места для параметров должны быть представлены в таком виде - $$0, $$1, и так далее.
         *
         *  @param string $query Запрос к базе данных.
         *  @param array $tables Массив с именами таблиц. Каждый элемент массива должен иметь ключ вида table_key.
         *  @param array $params Параметры запроса.
         */
        public function query($query, $tables = null, $params = null)
        {
            if($tables === null)
                $tables = array();
                
            if($params === null)
                $params = array();
                
            $query = preg_replace_callback('/\\$\\$(\d+)/', function($matches) use(&$params)
            {
                $index = intval($matches[1]);
                
                if(array_key_exists($index, $params))
                    return "'" . addslashes($params[$index]) . "'";
                
                return "'$index'";
            }, $query);
            
            $query = preg_replace_callback('/\\$\\$(\w+)/', function($matches) use(&$tables)
            {
                $key = $matches[1];
                
                if(array_key_exists($key, $tables))
                    return "`" . $this->getTable($tables[$key]) . "`";
                
                return "`" . $this->getTable($key) . "`";
            }, $query);
            
            return $this->sendQuery($query);
        }
        
        /**
         *  Начинает транзакцию в БД. Запросы, отправленные в транзакции, будут выполнены как один неделимый запрос,
         *  что позволит избежать конкурентного доступа в БД. К сожалению, данная функция поддерживается не всеми БД.
         */
        public function startTransaction() {}
        
        /**
         *  Заканчивает транзакцию.
         */
        public function endTransaction() {}
        
        /**
         *  Очищает таблицу.
         */
        abstract public function truncateTable($name);
        
        /**
         *  Непосредственно отправляет запрос.
         */
        abstract protected function sendQuery($str);
        
        /**
         *  Преобразует имя таблицы при необходимости.
         */
        protected function getTable($table)
        {
            return $table;
        }
    }
}

?>