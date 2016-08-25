<?php
/**
 *  @package    IndieGo Chat
 *  @file       environment.php - Используется скриптами разного рода для создания минимального рабочего окружения.
 *  Обычно включается файлом chat.php, также используется при установке. Перед этим файлом следует включить конфигурацию
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
    require_once 'db.php';
    require_once 'user.php';
    require_once Config::ADAPTER . '/adapter.php';
}

?>