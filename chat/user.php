<?php
/**
 *  @package    IndieGo Chat
 *  @file       user.php - Предоставляет информацию о пользователе
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
	abstract class UserManager
	{
        abstract public function getCurrentUserData();
        abstract public function getUserData($user_id);
	}
	
    class User
    {
		/**
		 * Текущий пользователь.
		 *
		 * @access public
		 * @var User
		 */
		public static $current = null;
		
		/**
		 * Менеджер пользователей.
		 *
		 * @access protected
		 * @var UserManager
		 */
		public static $manager = null;
		
		/**
		 * Список загруженных пользователей.
		 *
		 * @access public
		 * @var array(User)
		 */
		public static $list = array();
		
		/**
		 * ИД пользователя.
		 *
		 * @access protected
		 * @var int
		 */
		protected $id;
		
		/**
		 * Имя пользователя.
		 *
		 * @access protected
		 * @var string
		 */
		protected $name;
		
		/**
		 * Роль пользователя.
		 *
		 * @access protected
		 * @var int
		 */
		protected $role;
		
		/**
		 * Флаг бана.
		 *
		 * @access protected
		 * @var boolean
		 */
		protected $is_banned;
		
		/**
		 * Имена ролей пользователей.
		 *
		 * @access public
		 * @var array
		 */
		public static $roles = array(
			'guest',
			'user',
			'moderator',
			'admin'
		);
		
		const GUEST = 0;
		const USER = 1;
		const MODERATOR = 2;
		const ADMIN = 3;
		
		protected function __construct($data)
		{
			$this->id   = $data['id'];
			$this->name = $data['name'];
			$this->role = $data['role'];
			$this->is_banned = $data['is_banned'];
		}
		
		public static function initialize()
		{
			static::$manager = Adapter::createUserManager();
			static::$current = static::createCurrentUser();
		}
		
		protected static function createCurrentUser()
		{
			if(isset($_GET['user_id']))
				return static::getUser($_GET['user_id']);
			
			$data = static::$manager->getCurrentUserData();
			
			if(!isset($data))
				throw new Exception("There is no user data provided!");
			
			return new User($data);
		}
		
		public static function getUser($user_id)
		{
			if(!array_key_exists($user_id, static::$list))
			{
				$user = new User(static::$manager->getUserData($user_id));
				static::$list[$user_id] = $user;
				
				return $user;
			}
			
			return static::$list[$user_id];
		}
		
		public function getId()
		{
			return $this->id;
		}
		
		public function getName()
		{
			return $this->name;
		}
		
		public function getRole()
		{
			return $this->role;
		}
		
		public function getRoleName()
		{
			return static::$roles[$this->role];
		}
		
		public function isBanned()
		{
			return $this->is_banned;
		}
		
		public function isRegistered()
		{
			return $this->role != static::GUEST;
		}
		
		public function isPowerful()
		{
			return $this->role == static::ADMIN || $this->role == static::MODERATOR;
		}
		
		public function getData()
		{
			return array(
				'id' => $this->id,
				'name' => $this->name,
				'role' => $this->getRoleName()
			);
		}
    }
}

?>