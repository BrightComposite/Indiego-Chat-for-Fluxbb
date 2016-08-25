<?php
/**
 *  @package    IndieGo Chat Adaptation for FluxBB
 *  @file       adapter.php - Предоставляет реализации менеджеров и подготавливает окружение форума при необходимости
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
    class Adapter
    {
        public static function createDatabaseManager()
        {
            return new FluxBBDatabaseManager();
        }
        
        public static function createUserManager()
        {
            return new FluxBBUserManager();
        }
    }
    
    if(!defined('PUN_ROOT'))
    {
        define('PUN_TURN_OFF_MAINT', 1);
        define('PUN_ROOT', dirname(dirname(dirname(__FILE__))) . '/');
        
        require PUN_ROOT.'include/common.php';
        
        if (!defined('PUN_DEBUG'))
            define('PUN_DEBUG', 1);
    }
    
    require_once 'fluxbbdb.php';
    require_once 'fluxbbuser.php';
}

?>