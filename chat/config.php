<?php
/**
 *  @package    IndieGo Chat
 *  @file       config.php - Конфигурация чата
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
    define('CHAT_CONFIG', '1');
    
    class Config
    {
        const ADAPTER = 'fluxbb';
        
        const DB_MESSAGES_TABLE = 'indiego_chat_messages';
        const DB_SESSIONS_TABLE = 'indiego_chat_sessions';
        const DB_COMMANDS_TABLE = 'indiego_chat_commands';
        
        const TITLE = 'Чат';
        const LABEL_INPUT = 'Сообщение: ';
        const CAPTION_SEND = 'Отправить';
        const NOT_REGISTERED = '<strong>Зарегистрируйтесь, чтобы писать в чате</strong>';
        const IS_BANNED = '<strong>Вы были забанены и не можете писать в чат!</strong>';
        
        const HELP_TEXT =
"<p><strong>Эксклюзивный чат для форума проекта Minecraft@Setera</strong></p>
<p>Открыт только для зарегистрированных пользователей</p>
<p>Поддерживается технология drag'n'drop для быстрой вставки ссылок (в том числе ссылок на картинки)</p>
<p>Доступна навигация по истории отправленных сообщений при помощи стрелок вверх/вниз</p>";
        
        const MESSAGE_STORAGE_MIN_LIMIT = 30;
        const MESSAGE_STORAGE_MAX_LIMIT = 60;
        const MESSAGE_INITIAL_RESPONSE_LIMIT = 10;
        
        const CLIENT_UPDATE_PERIOD = 2; // seconds
        
        const ENABLE_LOG = false;
    }
}

?>