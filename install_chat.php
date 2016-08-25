<?php
/**
 *  @package    IndieGo Chat
 *  @file       install_chat.php - Отображает страницу установки/удаления чата
 *  
 *  @version    1.0
 *  @date       2015-06-09
 *  @copyright  Copyright (c) IndieGo, 2015
 */

namespace IndieGoChat
{
    require_once 'chat/config.php';
    require_once 'chat/environment.php';
    require_once 'chat/' . Config::ADAPTER . '/install.php';
}
?>