<?php
/**
 *  @package    IndieGo Chat
 *  @file       ajax.php - Принимает запросы от клиентов и передает их на обработку, предварительно подготовив окружение
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
    require_once 'config.php';
    require_once 'log.php';
    
    Log::start("ajax.log");
    
    if(!isset($_GET['user_id']))
    {
        $msg = "No user ID";
        Log::write($msg);
        exit('{"error": "' . $msg . '"}');
    }
    
    if(!isset($_GET['request']))
    {
        $msg = "No user ID";
        Log::write($msg);
        exit('{"error": "' . $msg . '"}');
    }
    
    try
    {
        require_once 'chat.php';
        require_once 'requester.php';
    
        Chat::initialize();
        Requester::process($_GET['request']);
    }
    catch(\Exception $ex)
    {
        Log::write("Exception: " . $ex->getMessage());
        exit('{"error": "' . $ex->getMessage() . ' ' . $ex->getTraceAsString() . '"}');
    }
}
?>