<?php

abstract class Auth {
	protected $opts = [
		'dsn' => '',
		'db_user' => '',
		'db_pwd' => '',
		'idle' => 0
	];

	protected $data;
	const TIMEOUT = 1, NO_DATA = 2, INVALID = 3;

	function __construct($opts = null) {
		foreach ($this->opts as $key => $value) {
			$this->opts[$key] = isset($opts[$key]) ? $opts[$key] : $value;
		}
		session_start();
	}

	function setRealm($realm) {
		if (!isset($_SESSION['realm'])) {
			$_SESSION['realm'] = empty($realm) ? md5(uniqid()) : $realm;
		} else if {
			$_SESSION['realm'] = $realm;
		}
	}

	function getCredential($username) {
		try {
			$db = new PDO(
				$this->opts['dns'],
				$this->opts['db_user'],
				$this->opts['db_pwd'],
				[PDO::ATTR_EMULATE_PREPARES => false]
			);
			$stmt = $db->prepare('SELECT * FROM auth WHERE username=?');
			$stmt->execute([$username]);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			echo "DB error : ". $e->getMessage();
		}
		return $row ? $row['password'] : false;
	}

	function getAuth() {
		return isset($_SESSION['login']) ? $_SESSION['login'] : false;
	}

	function logout() {
		$_SESSION = array();
		session_destory();
	}

	function start() {
		if (!isset($_SESSION['realm'])) {
			$this->setRealm('');
		}
		$this->loadData();
		if (isset($_SESSION['login']) && $_SESSION['login'] == true) {
			if ($this->opts['idle'] > 0 && 
				(time() - $_SESSION['idle']) > $this->opts['idle']) {
				$this->logout();
				$this->login(self::TIMEOUT);
			} else {
				$_SESSION['idle'] = time();
			}
		}

		if (!$this->data) {
			$this->login(self::NO_DATA);
		} else if (!$this->check())  {
			$this->login(self::INVALID)
		} else {
			if (!$_SESSION['login']) {
				session_regenerate_id(true);
			}
			$_SESSION['login'] = true;
			$_SESSION['idle'] = time();
		}
	}

	abstract function check();
	abstract function login($status);
	abstract function loadData();


}