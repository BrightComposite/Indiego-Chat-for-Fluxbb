<?php
/**
 *  @package    IndieGo Chat Adaptation for FluxBB
 *  @file       db.php - Реализует интерфейс для обращения к базе данных форума
 *  
 *  @version    1.0
 *  @date       2015-06-10
 *  @author     IndieGo (indiego.mttt@gmail.com)
 *  @sponsor    Volkula (volkula@gmail.com)
 *  
 *  @copyright  Copyright (c) IndieGo, 2015
 */

namespace IndieGoChat
{
    class FluxBBDatabaseManager extends DatabaseManager
    {
        /**
         *  Объект абстрактной прослойки базы данных
         *  @var DBLayer
         */
        protected $db;
        
        public function __construct()
        {
            global $db;
            $this->db = $db;
        }
        
        protected function sendQuery($str)
        {
            $result = $this->db->query($str);
            
            if(!$result)
            {
                throw new \Exception('Unable to make query: ' . join(Log::new_line, $this->db->error()));
            }
            
            $array = array();
            
            while(true)
            {
                $row = $this->db->fetch_assoc($result);
                
                if(!$row)
                    break;
                
                $array[] = $row;
            }
            
            return $array;
        }
        
        public function startTransaction()
        {
            $this->db->start_transaction();
        }
        
        public function endTransaction()
        {
            $this->db->end_transaction();
        }
        
        public function truncateTable($name)
        {
            $this->db->truncate_table($name);
        }
        
        protected function getTable($table)
        {
            return $this->db->prefix . $table;
        }
    }
}

?>