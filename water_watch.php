<?php
	require('base.php');

	class Water_Watch{
		//MySQL Link
		public $link;
		//ArctroBase
		public $base;
		
		public $radius = 50;
		public $limit = 25;
		
		//Setup function
		function __construct($link){
			$this->link = $link;
			$this->base = new ArctroBase($link);
		}
		
		//Post a message
        function post_message($title, $message, $lat, $lng, $post_id, $user_id){
			
			$message = $this->base->filter_spam($message);
			
			$result = $this->base->mysqli_results("INSERT INTO `posts`(`id`, `user_id`, `post_id`, `title`, `content`, `lat`, `lng`, `ups`, `downs`, `post_date`) VALUES (NULL, '". $user_id ."', '". $post_id ."', '". $title ."', '". $message ."', ". $lat .", ". $lng .", 0, 0, CURRENT_TIMESTAMP)");
			return $result;
		}
		
        //Vote on post
		function vote($post_id, $value){
			$sql = "";
			
			if($value > 0){
				$sql = "UPDATE `Water_Watch`.`posts` SET `ups` = `ups` + '1' WHERE `posts`.`post_id` = " . $post_id;
			}else{
				$sql = "UPDATE `Water_Watch`.`posts` SET `downs` = `downs` + '1' WHERE `posts`.`post_id` = " . $post_id;
			}
			
			$result = $this->base->mysqli_results($sql)['return'];
			return $result;
		}
				
		//Get messages in area
		function get_messages($lat, $lng, $radius, $offset=0){
			$latRight = $lat + ($radius * 0.00904371733);
			$latLeft = $lat - ($radius * 0.00904371733);
			
			$lngTop = $lng + ((1/(111.320 * cos($lat))) * $radius);
			$lngBottom = $lng - ((1/(111.320 * cos($lat))) * $radius);
			
			$offset = intval($offset);
		
			$result = $this->base->mysqli_results("SELECT * FROM `posts` WHERE `lat` > '".$latLeft."' AND `lat` < '".$latRight."' AND `lng` > '".$lngBottom."' AND `lng` < '".$lngTop."' ORDER BY `post_date` DESC LIMIT ". $this->limit ." OFFSET " . $offset)['return'];
			return $result;
		}
		
		function get_messages_hot($lat, $lng, $radius, $offset=0){
			$latRight = $lat + ($radius * 0.00904371733);
			$latLeft = $lat - ($radius * 0.00904371733);
			
			$lngTop = $lng + ((1/(111.320 * cos($lat))) * $radius);
			$lngBottom = $lng - ((1/(111.320 * cos($lat))) * $radius);
			
			$offset = intval($offset);
		
			$result = $this->base->mysqli_results("SELECT * FROM `hot` WHERE `lat` > '".$latLeft."' AND `lat` < '".$latRight."' AND `lng` > '".$lngBottom."' AND `lng` < '".$lngTop."' LIMIT ". $this->limit ." OFFSET " . $offset)['return'];
			return $result;
		}
		
		//Get single message
		function get_message($post_id){
			$result = $this->base->mysqli_results("SELECT * FROM `posts` WHERE `post_id` LIKE '". $post_id ."'")['return'][0];
			return $result;
		}
		
		function get_auth($key){
			$sql = "SELECT * FROM `auth_keys` WHERE `key` LIKE '". $key ."'";
			$result = $this->base->mysqli_results($sql);
			return $result['return'][0];
		}
	}
	
	/*
	Permissions:
	u = User permissions
	s = Signup permissions
	p = Post permissions
	e = Edit permissions
	d = Delete permissions
	a = Post Advertisement permissions
	k = Create Key permissions
	*/
	
	class Water_WatchAPI{
		//MySQL Link
		public $link = null;
		//Water_Watch
		public $Water_Watch = null;
		//ArctroBase
		public $base = null;
		
		//Setup function
		function __construct($link){
			$this->link = $link;
			$this->Water_Watch = new Water_Watch($link);
			$this->base = new ArctroBase($link);
		}
		
		function handle_api($input){
			$request = $input['request'];
			$auth_key = $input['key'];
			
			$auth = $this->Water_Watch->get_auth($auth_key);
			$permissions = json_decode($auth['permissions'], true);
			$enabled = $auth['enabled'];
			
			if($request == "POST_MESSAGE"){
				if($permissions['p'] == 1 && $enabled == 1){
					if(!$this->base->keys_set(array("title", "message", "lat", "lng"), $input)){
						return array("error"=>"Title, Message, Lat or Lng not entered");
					}
					return $this->Water_Watch->post_message($input['title'],$input['message'],$input['lat'],$input['lng'], $input['post_id'], $input['user_id']);
				}
				return array("error"=>"Auth key invalid or not right permission");
			}
			if($request == "GET_MESSAGES"){
				if(!$this->base->keys_set(array("lat", "lng"), $input)){
					return array("error"=>"Lat or Lng not entered");
				}
				if($input['s']=='h'){
					return $this->Water_Watch->get_messages_hot($input['lat'], $input['lng'], $this->Water_Watch->radius, $input['offset']);
				}
				return $this->Water_Watch->get_messages($input['lat'], $input['lng'], $this->Water_Watch->radius, $input['offset']);
			}
			if($request == "GET_MESSAGE"){
				if(!$this->base->keys_set(array("id"), $input)){
					return array("error"=>"ID not entered");
				}
				return $this->Water_Watch->get_message($input['id']);
			}
			if($request == "UPVOTE"){
				if($permissions['p'] == 1 && $enabled == 1){
					if(!$this->base->keys_set(array("id"), $input)){
						return array("error"=>"ID not entered");
					}
					return $this->Water_Watch->vote($input['post_id'], 1);
				}
				return array("error"=>"Auth key invalid or not right permission");
			}
			if($request == "DOWNVOTE"){
				if($permissions['p'] == 1 && $enabled == 1){
					if(!$this->base->keys_set(array("id"), $input)){
						return array("error"=>"ID not entered");
					}
					return $this->Water_Watch->vote($input['post_id'], -1);
				}
				return array("error"=>"Auth key invalid or not right permission");
			}
			return array("error"=>"Request Invalid");
		}
	}
?>
