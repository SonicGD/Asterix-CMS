<?php

/************************************************************/
/*															*/
/*	Ядро системы управления Asterix	CMS						*/
/*		Класс пользователя									*/
/*															*/
/*	Версия ядра 2.0											*/
/*	Версия скрипта 1.00										*/
/*															*/
/*	Copyright (c) 2009  Мишин Олег							*/
/*	Разработчик: Мишин Олег									*/
/*	Email: dekmabot@gmail.com								*/
/*	WWW: http://mishinoleg.ru								*/
/*	Создан: 10 февраля 2009	года							*/
/*	Модифицирован: 25 сентября 2009 года					*/
/*															*/
/************************************************************/

class user
{
	var $title = 'Auth';
	public static $table_name = 'users';
	public static $info;
	
	public $auth_types = array('user' => 'Регистрация на сайте', 'yandex' => 'Яндекс', 'google' => 'Google', 'livejournal' => 'LiveJournal', 'openid' => 'OpenId');

	public function __construct($model){
		
		//Logout
		if (IsSet($_GET['logout'])) {
			UnSet($_SESSION['auth']);
			session_regenerate_id();
			self::deleteCookie('auth');
			self::$info = array(
				'id' => 0,
				'title' => 'Гость',
				'admin' => false
			);
			header('Location: ?');
			exit();
		}

		//Авторизация
		if (IsSet($_SESSION['auth'])) {
			self::authUser();

		//Авторизация
		} elseif (IsSet($_GET['openid_assoc_handle'])) {
			UnSet($_SESSION['auth']);
			session_regenerate_id();
			self::deleteCookie('auth');
			self::finish_OAuthUser();
			//$this->authUser();

		//Авторизация Oauth
		} elseif (IsSet($_GET['login_oauth'])) {
			self::start_OAuthUser();

		//Авторизация
		} elseif (IsSet($_COOKIE['auth'])) {
			self::authUser();

		//Авторизация
		} elseif (IsSet($_GET['login']) && IsSet($_GET['auth'])) {
			self::authUser();

		//Начальные данные пользователя
		} else {
			$this->info = array(
				'id' => 0,
				'title' => 'Гость',
				'admin' => false
			);
		}
	}

	//Учёт последнего входа в систему
	public static function updateMyLoginDate($my_id){
		model::execSql('update `'.self::$table_name.'` set `date_logged`=NOW() where `id`="'.$my_id.'" limit 1','update');
	}

	//Запоминаем пользователя после удачной авторизации
	public static function all_ok($user){
/*
		if( IsSet(model::$modules['users']) )
			$user = model::$modules['users']->explodeRecord($user,'rec');
*/
		//Аккаунт-ссылка на основной аккаунт
		if( $user['is_link_to'] ){
			//Поиск по базе
			$user = model::makeSql(array(
				'tables' => array(
					self::$table_name
				),
				'where' => array(
					'and' => array(
						'`id`="' . intval( $user['is_link_to'] ) . '"',
						'`active`' => '1'
					)
				)
			), 'getrow');
		}
		
		//Проверка на пустой URL, такое могло случаться на движке моложе 2.14
		if( !$user['url'] )
			model::execSql('update `'.self::$table_name.'` set `url`="/users/'.mysql_real_escape_string( model::$types['sid']->toValue('sid', $user) ).'" where `id`='.intval($user['id']).' limit 1','update');
		
		$_SESSION['auth'] = $user['session_id'];

		$user 						= default_module::insertRecordUrlType($user);
		user::$info       			= $user;
		user::$info['public_auth'] 	= md5($user['session_id']);
		user::updateMyLoginDate(user::$info['id']);
		user::$info = default_module::insertRecordUrlType(user::$info);

		if(IsSet($_GET['login_oauth'])){
			header('Location: /');
			exit();
		}
			
	}
	
	//Выбор способа авторизации пользователя
	public static function authUser(){
	
		//Возврат авторизации по OpenID
		if( 
			IsSet($_GET['openid_assoc_handle']) and 
			IsSet($_GET['openid_identity']) and 
			IsSet($_GET['openid_mode']) and 
			IsSet($_GET['openid_return_to']) and 
			IsSet($_GET['openid_sig']) and 
			IsSet($_GET['openid_signed'])
		){
			self::finish_OAuthUser();
		
		//Авторизация пользователя по локальной базе пользователей
		}else{
			self::authUser_localhost();
		}
	}

	//Авторизация пользователя по локальной базе пользователей
	private static function authUser_localhost(){
		//Авторизация по логину/паролю
		if (IsSet($_POST['login']) && IsSet($_POST['password']) && (!IsSet($_POST['title'])) ) {
			//Поиск по базе
			$user = model::makeSql(array(
				'tables' => array(
					self::$table_name
				),
				'where' => array(
					'and' => array(
						'`login`="' . mysql_real_escape_string($_POST['login']) . '"',
						'`password`="' . model::$types['password']->encrypt($_POST['password']) . '"',
						'access' => '1'
					)
				)
			), 'getrow');

			UnSet($_POST['login']);
			UnSet($_POST['password']);

			//Запоминаем
			if (IsSet($user['id'])) {
				self::setCookie('auth', $user['session_id']);
				self::all_ok($user);
				$_SESSION['just_logged']=date('H:i:s',strtotime('+10 seconds'));
			}

		//Авторизация по сессии
		} elseif (strlen(@$_SESSION['auth'])) {
			//Поиск по базе
			$user = model::makeSql(array(
				'tables' => array(
					self::$table_name
				),
				'where' => array(
					'and' => array(
						'`session_id`="' . mysql_real_escape_string($_SESSION['auth']) . '"',
						'access' => '1'
					)
				)
			), 'getrow');

			//Запоминаем
			if (IsSet($user['id']))
				self::all_ok($user);

			//Первая страница после авторизации через форму "логин/пароль"
			if($_SESSION['just_logged']){
				if($_SESSION['just_logged'] >= date('H:i:s')){
					self::$info['just_logged']=$_SESSION['just_logged'];
				}else{
					UnSet($_SESSION['just_logged']);
				}
			}
			
			
		//Авторизация по Cookies
		} elseif (strlen(@$_COOKIE['auth'])) {
			//Поиск по базе
			$user = model::makeSql(array(
				'tables' => array(
					self::$table_name
				),
				'where' => array(
					'and' => array(
						'`session_id`="' . mysql_real_escape_string($_COOKIE['auth']) . '"',
						'`active`' => '1'
					)
				)
			), 'getrow');

			//Запоминаем
			if (IsSet($user['id']))
				self::all_ok($user);
	
				
		//Авторизация по GET-параметру
		} elseif (IsSet($_GET['login']) && IsSet($_GET['auth'])) {
			//Поиск по базе
			$user = model::makeSql(array(
				'tables' => array(
					self::$table_name
				),
				'where' => array(
					'and' => array(
						'`login`="' . mysql_real_escape_string($_GET['login']) . '"',
						'MD5(`session_id`)="' . mysql_real_escape_string($_GET['auth']) . '"',
						'access' => '1'
					)
				)
			), 'getrow');

			//Запоминаем
			if (IsSet($user['id'])) {
				self::setCookie('auth', $user['session_id']);
				self::all_ok($user);
			}

		//Не авторизован
		} else {
			self::deleteCookie('auth');
			self::$info = array(
				'id' => 0,
				'title' => 'Гость',
				'admin' => false
			);
		}
	}

	
	//Установка Cookie
	private static function setCookie($name, $value){
		if( !IsSet( $_POST['no_cookie'] ) ){
			$time   = time() + 60 * 60 * 24 * 365;
			$path   = '/';
			$domain = '.' . $_SERVER['HTTP_HOST'];
			setcookie($name, $value, $time, $path, $domain);
		}
	}

	//Установка Cookie
	public static function deleteCookie($name){
		$time   = time() - 3600;
		$path   = '/';
		$domain = '.' . $_SERVER['HTTP_HOST'];
		setcookie($name, $value, $time, $path, $domain);
	}
	
	
	
	
	//Старт авторизации по OAuth - запрос в сторону провайдера
	private static function start_OAuthUser(){
		$provider = $_GET['login_oauth'];
		
		if( in_array($provider, array('vk.com','vk') ) ){
			if( IsSet($_GET['error']))
				return false;
				
			//дефолтные настройки из конфига
			$app_id = model::$settings['oauth_vk_id'];
			$app_secret = model::$settings['oauth_vk_s_key'];
			$my_url = 'http://'.model::$ask->host.'/?login_oauth=vk';

			session_start();
			$code = $_REQUEST["code"];
				
			//получаем код доступа
			if(empty($code)) {
				$_SESSION['state'] = md5(uniqid(rand(), TRUE)); //CSRF protection
				$dialog_url = 'http://api.vk.com/oauth/authorize?client_id='.$app_id.'&redirect_uri=http://'.model::$ask->host.'/?login_oauth=vk';
				echo("<script> top.location.href='" . $dialog_url . "'</script>");
			}
				
			//Получаем Token
			$token_url = 'https://oauth.vkontakte.ru/access_token?client_id='.$app_id.'&client_secret='.$app_secret.'&code='.$code;
			$token = (array)json_decode(@file_get_contents($token_url));
				
			//Запрос данных
			$url2="https://api.vkontakte.ru/method/getProfiles?uid=".$token['user_id']."&access_token=".$token['access_token']."&fields=uid,first_name,last_name,bdate,photo_big,has_mobile";
			$datas = json_decode(@file_get_contents($url2));
			$datas=(array)$datas;
				
			if( !IsSet( $datas['response'] ) )
				return false;
				
			$datas=(array)$datas['response'][0];
			$this->info = array(
				'login' => 'vk'.$datas['uid'],
				'password' => md5($datas['uid'].'thisismyverybigwordformd5'),
				'admin' => false,
				'title' => $datas['first_name'].' '.$datas['last_name'],
				'avatar'=> $datas['photo_big'],
				'photo' => $datas['photo_big'],
				'session_id' => session_id(),
			);
				
			$_POST['login'] = self::$info['login'];
			$_POST['password'] = self::$info['password'];
					
			//Авторизуем
			self::authUser_localhost();
			$login = model::$types['sid']->correctValue( self::$info['login'] );
								
			//Регистрируем
			if( !self::$info['id'] ){
				self::$info['sid'] = $login;
				self::$info['shw'] = true;
				self::$info['admin'] = intval( @model::$config['openid'][ $_GET['login_oauth'] ] == 'admin' );
				self::$info['session_id'] = session_id();

				$_POST['login'] = self::$info['login'];
				$_POST['password'] = self::$info['password'];
				
				model::addRecord('users', 'rec', self::$info);
				self::$authUser_localhost();
			}
			
			//На главную
			header('Location: /');
			exit();
		
		
		}elseif( in_array($provider, array('facebook.com', 'facebook')) ){
			if( IsSet( $_GET['error'] ) )
				break;

			//дефолтные настройки из конфига
			$app_id = model::$settings['oauth_facebook_id'];
			$app_secret = model::$settings['oauth_facebook_s_key'];
			$my_url = 'http://'.model::$ask->host.'/?login_oauth=facebook';
			
			session_start();
			$code = $_REQUEST["code"];
				
			//получаем код доступа
			if(empty($code)) {
				$_SESSION['state'] = md5(uniqid(rand(), TRUE)); //CSRF protection
				$dialog_url = "http://www.facebook.com/dialog/oauth?client_id=".$app_id."&redirect_uri=".urlencode($my_url)."&scope=email&state=".$_SESSION['state'];
				echo("<script> top.location.href='" . $dialog_url . "'</script>");
			}
				
			//получаем токен
			if($_REQUEST['state'] == $_SESSION['state']) {
				$token_url = "https://graph.facebook.com/oauth/access_token?"."client_id=".$app_id."&redirect_uri=".urlencode($my_url)."&client_secret=".$app_secret."&code=".$code;
				$response = @file_get_contents($token_url);
				$params = null;
				parse_str($response, $params);
		
				$graph_url = "https://graph.facebook.com/me?access_token=".$params['access_token'];
				
				//получаем данные пользователя с помощью токена
				$datas = json_decode(@file_get_contents($graph_url));
				$datas=(array)$datas;
				
				self::$info = array(
					'login' => 'facebook'.$datas['id'],
					'password' => md5($datas['id'].'thisismyverybigwordformd5'),
					'admin' => false,
					'title' => $datas['name'],
					'avatar'=> NULL,
					'photo' => NULL,
					'email' => $datas['email'],
					'session_id' => session_id(),
				);
				
				$_POST['login'] = self::$info['login'];
				$_POST['password'] = self::$info['password'];
					
				//Авторизуем
				self::$authUser_localhost();
				$login = model::$types['sid']->correctValue( self::$info['login'] );
								
				//Регистрируем
				if( !self::$info['id'] ){
					self::$info['sid'] = $login;
					self::$info['shw'] = true;
					self::$info['admin'] = intval( @model::$config['openid'][ $_GET['login_oauth'] ] == 'admin' );
					self::$info['session_id'] = session_id();

					$_POST['login'] = self::$info['login'];
					$_POST['password'] = self::$info['password'];
					
					model::addRecord('users', 'rec', self::$info);
					self::authUser_localhost();
				}

				header('Location: /');
				exit();
				
			}else
				echo("Произошла ошибка авторизации. Попробуйте еще раз.");
		
		
		}elseif( in_array($provider, array('twitter.com','twitter') ) ){
			
			//Дефолтные настройки
			$TWITTER_CONSUMER_KEY=model::$settings['oauth_twitter_id'];
			$TWITTER_CONSUMER_SECRET=model::$settings['oauth_twitter_s_key'];
			$TWITTER_URL_CALLBACK='http://'.model::$ask->host.'/?login_oauth=twitter';
				
			$URL_REQUEST_TOKEN='https://api.twitter.com/oauth/request_token';
			$URL_AUTHORIZE='https://api.twitter.com/oauth/authorize';
			$URL_ACCESS_TOKEN='https://api.twitter.com/oauth/access_token';
			$URL_ACCOUNT_DATA='http://twitter.com/users/show';
					
			if( !IsSet( $_GET['oauth_verifier'] ) ){
				
				// рандомная строка (для безопасности)
				$oauth_nonce = md5(uniqid(rand(), true));
				
				// время когда будет выполняться запрос (в секундых)
				$oauth_timestamp = time();
				
				$oauth_base_text = "GET&";
				$oauth_base_text .= urlencode($URL_REQUEST_TOKEN)."&";
				$oauth_base_text .= urlencode("oauth_callback=".urlencode($TWITTER_URL_CALLBACK)."&");
				$oauth_base_text .= urlencode("oauth_consumer_key=".$TWITTER_CONSUMER_KEY."&");
				$oauth_base_text .= urlencode("oauth_nonce=".$oauth_nonce."&");
				$oauth_base_text .= urlencode("oauth_signature_method=HMAC-SHA1&");
				$oauth_base_text .= urlencode("oauth_timestamp=".$oauth_timestamp."&");
				$oauth_base_text .= urlencode("oauth_version=1.0");
	
				//Ключ
				$key = $TWITTER_CONSUMER_SECRET."&";
				$oauth_signature = base64_encode(hash_hmac("sha1", $oauth_base_text, $key, true));
					
				//составляем гет запрос
				$url = $URL_REQUEST_TOKEN;
				$url .= '?oauth_callback='.urlencode($TWITTER_URL_CALLBACK);
				$url .= '&oauth_consumer_key='.$TWITTER_CONSUMER_KEY;
				$url .= '&oauth_nonce='.$oauth_nonce;
				$url .= '&oauth_signature='.urlencode($oauth_signature);
				$url .= '&oauth_signature_method=HMAC-SHA1';
				$url .= '&oauth_timestamp='.$oauth_timestamp;
				$url .= '&oauth_version=1.0';
	
				//отправляем запрос.
				$response = @file_get_contents($url);
				parse_str($response, $result);
					
				$oauth_token = $result['oauth_token'];
				$oauth_token_secret = $result['oauth_token_secret'];
				
				self::setCookie('oauth_token_secret', $oauth_token_secret);
				
				$url = $URL_AUTHORIZE;
				$url .= '?oauth_token='.$oauth_token;
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: ".$url);
				exit();
			
			}else{
				
				$oauth_nonce = md5(uniqid(rand(), true));
				// время когда будет выполняться запрос (в секундых)
				$oauth_timestamp = time();
				
				// oauth_token
				$oauth_token = $_GET['oauth_token'];
				
				// oauth_verifier
				$oauth_verifier = $_GET['oauth_verifier'];
				
				// oauth_token_secret получаем из сессии, которую зарегистрировали
				// во время запроса request_token
				$oauth_token_secret = $_COOKIE['oauth_token_secret'];
				
				$oauth_base_text = "GET&";
				$oauth_base_text .= urlencode($URL_ACCESS_TOKEN)."&";
				$oauth_base_text .= urlencode("oauth_consumer_key=".$TWITTER_CONSUMER_KEY."&");
				$oauth_base_text .= urlencode("oauth_nonce=".$oauth_nonce."&");
				$oauth_base_text .= urlencode("oauth_signature_method=HMAC-SHA1&");
				$oauth_base_text .= urlencode("oauth_token=".$oauth_token."&");
				$oauth_base_text .= urlencode("oauth_timestamp=".$oauth_timestamp."&");
				$oauth_base_text .= urlencode("oauth_verifier=".$oauth_verifier."&");
				$oauth_base_text .= urlencode("oauth_version=1.0");
				
				$oauth_base_text;
				$key = $TWITTER_CONSUMER_SECRET."&".$oauth_token_secret;
				$oauth_signature = base64_encode(hash_hmac("sha1", $oauth_base_text, $key, true));
				
				$url = $URL_ACCESS_TOKEN;
				$url .= '?oauth_nonce='.$oauth_nonce;
				$url .= '&oauth_signature_method=HMAC-SHA1';
				$url .= '&oauth_timestamp='.$oauth_timestamp;
				$url .= '&oauth_consumer_key='.$TWITTER_CONSUMER_KEY;
				$url .= '&oauth_token='.$oauth_token;
				$url .= '&oauth_verifier='.$oauth_verifier;
				$url .= '&oauth_signature='.urlencode($oauth_signature);
				$url .= '&oauth_version=1.0';

				$response = @file_get_contents($url);
				parse_str($response, $result);
				
				$user_id = $result['user_id'];

				$url = 'http://twitter.com/users/show.json?user_id='.$user_id;
				$response = @file_get_contents($url);
				$datas = json_decode($response);
				$datas=(array)$datas;
					
				self::$info = array(
					'login' => 'twitter'.$datas['id'],
					'password' => md5($datas['id'].'thisismyverybigwordformd5'),
					'admin' => false,
					'title' => $datas['name'],
					'avatar'=> $datas['profile_image_url'],
					'photo' => $datas['profile_image_url'],
					'session_id' => session_id(),
				);
					
				$_POST['login'] = self::$info['login'];
				$_POST['password'] = self::$info['password'];
									
				//Авторизуем
				self::authUser_localhost();
				$login = model::$types['sid']->correctValue( self::$info['login'] );
									
				//Регистрируем
				if( !self::$info['id'] ){
					self::$info['sid'] = $login;
					self::$info['shw'] = true;
					self::$info['admin'] = intval( @model::$config['openid'][ $_GET['login_oauth'] ] == 'admin' );
					self::$info['session_id'] = session_id();

					$_POST['login'] = self::$info['login'];
					$_POST['password'] = self::$info['password'];
				
					model::addRecord('users', 'rec', self::$info);
					self::authUser_localhost();
				}
			}
			
			header('Location: /');
			exit();


		}elseif( $provider == 'yandex.ru' ){
			$url = 'http://openid.yandex.ru/trusted_request/
				?openid.ns=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0
				&openid.mode=checkid_setup
				&openid.return_to=http://'.model::$ask->host.'/?login
				&openid.claimed_id=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0%2Fidentifier_select
				&openid.identity=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0%2Fidentifier_select
				&openid.realm=http://'.model::$ask->host.'/
				&openid.ui.ns=http://specs.openid.net/extensions/ui/1.0
				&openid.ui.icon=true&openid.ns.ax=http://openid.net/srv/ax/1.0
				&openid.ax.mode=fetch_request
				&openid.ax.required=firstname,email,language
				&openid.ax.type.firstname=http://axschema.org/namePerson
				&openid.ax.type.email=http://axschema.org/contact/email
				&openid.ax.type.language=http://axschema.org/pref/language
			';
			$url = str_replace("\n",'', $url);
			$url = str_replace("	",'', $url);
			header('Location: '.$url);
			exit();
			
			
		}elseif( $provider == 'google.com' ){
			$url = 'https://www.google.com/accounts/o8/ud
				?openid.ns=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0
				&openid.mode=checkid_setup
				&openid.return_to=http://'.model::$ask->host.'/?login
				&openid.claimed_id=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0%2Fidentifier_select
				&openid.identity=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0%2Fidentifier_select
				&openid.realm=http://'.model::$ask->host.'/
				&openid.ui.ns=http://specs.openid.net/extensions/ui/1.0
				&openid.ui.icon=true
				&openid.ns.ax=http://openid.net/srv/ax/1.0
				&openid.ax.mode=fetch_request
				&openid.ax.required=firstname,lastname,email,language
				&openid.ax.type.firstname=http://axschema.org/namePerson/first
				&openid.ax.type.lastname=http://axschema.org/namePerson/last
				&openid.ax.type.email=http://axschema.org/contact/email
				&openid.ax.type.language=http://axschema.org/pref/language
				';
			$url = str_replace("\n",'', $url);
			$url = str_replace("	",'', $url);
			header('Location: '.$url);
			exit();
		}
	}
	
	//Авторизация пользователя на удалённом сервере
	private static function finish_OAuthUser(){
	
		//Если есть разрешённые сервера для авторизации
		if( model::$config['openid'] ){
			require_once( model::$config['path']['libraries'].'/openid.php' );
			$openid = new LightOpenID( 'http://' . $_SERVER['HTTP_HOST'] );
			
			try {
				if(!$openid->mode) {
				}elseif($openid->mode == 'cancel') {
					echo 'User has canceled authentication!';
				}else{
				
					$params = $openid->getAttributes();

					//Проверяем email на наличие нужного домена
					if( substr_count($params['contact/email'], '@') === 1 ){
						
						$openid_domain = parse_url( $_GET['openid_op_endpoint'] );
						$openid_domain = $openid_domain['host'];
						$openid_domain = str_replace('openid.','', $openid_domain);
						$openid_domain = str_replace('www.','', $openid_domain);
								
						if( @in_array($openid_domain, model::$settings['oauth_openid']) 
						//Совместимость со старым форматом конфига
						or IsSet(model::$config['openid'][ $openid_domain ]) ){
							//Смотрим на конфиг, давать ли пользователям этого домена админа
							$openid_user_admin = ( model::$config['openid'][ $openid_domain ] == 'admin' );

							$login = model::$types['sid']->correctValue( $openid_domain.'_'.$params['contact/email'] );
							if( IsSet($params['namePerson/first']) ) 
								$title = $params['namePerson/first'].' '.$params['namePerson/last'];
							else
								$title = $params['namePerson'];
								
							//Начинаем регить
							self::$info = array(
								'login' => $login,
								'password' => $_GET['openid_identity'],
								'admin' => $openid_user_admin,
								'title' => $title,
								'email' => $params['contact/email'],
								'session_id' => session_id(),
							);
							$_POST['login'] = self::$info['login'];
							$_POST['password'] = self::$info['password'];
							
							//Авторизуем
							self::authUser_localhost();
							
							//Регистрируем
							if( !self::$info['id'] ){
								self::$info['sid'] = $login;
								self::$info['shw'] = true;
								self::$info['admin'] = intval( @model::$config['openid'][ $_GET['login_oauth'] ] == 'admin' );
								self::$info['session_id'] = session_id();

								$_POST['login'] = self::$info['login'];
								$_POST['password'] = self::$info['password'];
								
								//Проверяем уже заполненный профиль указанного человека
								if( strlen(self::$info['email'])>5 ){
									$old = model::execSql('select `id` from `'.self::$table_name.'` where `email`="'.mysql_real_escape_string( self::$info['email'] ).'"','getrow');
									if( $old )
										self::$info['is_link_to'] = $old['id'];										
								}
				
								model::addRecord('users', 'rec', self::$info);
								self::authUser_localhost();
							}
							
							header('Location: /');
							exit();
						}
					}else{echo "Ошибка передачи данных";}
					
				}
				
			} catch(ErrorException $e) {
				echo $e->getMessage();
			}
		}
	}

	
	
	public static function is_authorized(){
		return !!self::$info['id'];		
	}
	public static function is_admin(){
		return self::$info['admin'];		
	}
	public static function is_moder(){
		return self::$info['moder'];		
	}

}

?>