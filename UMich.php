<?php
class UMich {
	private $cookie = [];
	private $user_name = '';
	private $password = '';

	public function setup(string $u, string $p){
		$this->user_name = $u;
		$this->password = $p;
	}
	public function login(){
		$prev = file_get_contents('/opt/admit/UMich');
		$prev = json_decode($prev, true);
		if (isset($prev['cookie'])){
			$this->cookie = $prev['cookie'];
			return;
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,'https://enrollmentconnect.umich.edu/account/login');
		curl_setopt($curl, CURLOPT_POST, 1);
		$u = $this->user_name;
		$p = $this->password;
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$request = "email=${u}&password=${p}";
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		$result = curl_exec($curl);

		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
		foreach($matches[1] as $item) {
			parse_str($item, $cookie);
			$this->cookie = array_merge($this->cookie, $cookie);
		}
	}

	public function get_status(){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL,'https://enrollmentconnect.umich.edu/apply/status');
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Cookie: '.$this->cookie_str()));
		curl_setopt($curl, CURLOPT_HEADER, 1);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		$data = curl_exec($curl);
		$raw_data = $data;
		$data = strstr($data, '<a aria-controls="messages" aria-expanded="false" class="btn btn-dark stretched-link mmenu" data-target="#messages" data-toggle="collapse" href="#" role="button">');
		$data = strstr($data, 'data-toggle="collapse" type="button">');
		$ori_data = $data;
		$data = substr(strstr($data, '</button>', true), 37);

		curl_close($curl);
		if(strstr(strtolower($raw_data), 'congrat')) {
			return ['sha' => md5($ori_data), 'data' => '恭喜！确认录取。Congrats!', 'admitted' => true,
				'cookie' => $this->cookie];
		}
		if ($data != ''){
			return ['sha' => md5($ori_data), 'data' => $data,
				'cookie' => $this->cookie];
		}
		return NULL;
	}

	private function cookie_str(){
		foreach($this->cookie as $k => $v){ // this will fail if there are any more -public- variables declared in the class.
			$c[] = "$k=$v";
		}
		return implode('; ', $c);
	}
}