<?php
/**
 *  @package    IndieGo Chat Adaptation for FluxBB
 *  @file       user.php - Предоставляет интерфейс для получения информации о пользователях
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
    class FluxBBUserManager extends UserManager
    {
        protected $user;
        
        public function __construct()
        {
            global $pun_user;
            $this->user = $pun_user;
        }
        
        /**
         *  Возвращает данные текущего юзера
         */
        public function getCurrentUserData()
        {
            return $this->translateUserData($this->user);
        }
        
        /**
         *  Возвращает данные юзера из БД по id
         */
        public function getUserData($user_id)
        {
            $result = Chat::$db->query(
                'SELECT u.*, g.*, o.logged, o.idle FROM ' .
                    '$$users  AS u INNER JOIN ' .
                    '$$groups AS g ON u.group_id = g.g_id LEFT JOIN ' .
                    '$$online AS o ON o.user_id  = u.id ' .
                'WHERE u.id = $$0 ',
                null,
                array($user_id)
            );
            
            if(count($result) == 0)
                return array();
            
            return $this->translateUserData($result[0]);
        }
        
        protected function translateUserData($data)
        {
            $userData = array();
            
            if($data['is_guest'])
            {
                $userData['id']   = 0;
                $userData['name'] = '';
                $userData['role'] = User::GUEST;
                
                return $userData;
            }
            
            $userData['id'] = $data['id'];
            $userData['name'] = $data['username'];
            
            switch($data['g_id'])
            {
                case PUN_ADMIN:
                    $userData['role'] = User::ADMIN;
                    break;
                case PUN_MOD:
                    $userData['role'] = User::MODERATOR;
                    break;
                default:
                    $userData['role'] = User::USER;
            }
            
            $userData['is_banned'] = isset($pun_user['is_banned']) && $pun_user['is_banned'];
            
            return $userData;
        }
    }
}

?>