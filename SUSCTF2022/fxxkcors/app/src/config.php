<?php
error_reporting(0);
session_start();
class User {
	public $host;
	public $user;
	public $pass;
	public $database;
	public $conn;
	function __construct() {
        $this->host = "mysql";
        $this->user = "root";
        $this->pass = $_ENV['MYSQL_ROOT_PASSWORD'] ?? "mysql_root_password_for_this_challenge";
        $this->database = "ctf";
		$this->conn = new mysqli($this->host, $this->user, $this->pass, $this->database);
		if (mysqli_connect_errno()) {
			die('connect error');
		}
	}
	function find_normal($username) {
        $stmt = $this->conn->prepare("select * from normal_users where username=? LIMIT 0,1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

		if ($res->num_rows > 0) {
			return $res->fetch_assoc();
		} else {
			return False;
		}

	}

    function find_super($username) {
        $stmt = $this->conn->prepare("select * from super_users where username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            return True;
        } else {
            return False;
        }

    }

	function insert_normal($username, $password) {
        $stmt = $this->conn->prepare("insert into normal_users (username, password) values (?, ?)");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $res = $stmt->get_result();
		if ($res) {
			return True;
		} else {
			return False;
		}
	}

    function insert_super($username) {
        $stmt = $this->conn->prepare("insert into super_users (username) values (?)");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            return True;
        } else {
            return False;
        }
    }

}
