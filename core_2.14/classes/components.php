<?php

class components{

	public function load(){
		
		//Стандартные
		$system_prepares=array(
			'recs'=>array('function'=>'prepareRecs','title'=>'Список всех записей'),
			'parent'=>array('function'=>'prepareParent','title'=>'Родительская запись', 'hidden'=>true),
			'tags'=>array('function'=>'prepareTags','title'=>'Облако тегов раздела'),
			'map'=>array('function'=>'prepareMap','title'=>'Дерево раздела'),

			//Устаревщие
			'rec'=>array('function'=>'prepareRec','title'=>'Одна запись', 'hidden'=>true),
			'anons'=>array('function'=>'prepareAnons','title'=>'Анонс одной записи', 'hidden'=>true),
			'anonslist'=>array('function'=>'prepareAnonsList','title'=>'Анонс нескольких записей', 'hidden'=>true),
			'random'=>array('function'=>'prepareRandom','title'=>'Меню случайной записи', 'hidden'=>true),
			'randomlist'=>array('function'=>'prepareRandomList','title'=>'Меню списка случайных записей', 'hidden'=>true),
			'pages'=>array('function'=>'preparePages','title'=>'Страницы записей', 'hidden'=>true),
		);

		//Предобъявленные в модуле
		foreach($system_prepares as $sid=>$prepare)
			if(!IsSet($this->prepares[$sid])){
				$this->prepares[$sid]=$prepare;
			}
	}
	
	//Запуск подготовки данных в компоненте
	public function init($prepare, $params){
		if( IsSet( $this->prepares[$prepare] ) ){
			$function_name=$this->prepares[ $prepare ]['function'];
			
			if( method_exists( model::$modules[ $params['module'] ], $function_name) )
				return model::$modules[ $params['module'] ]->$function_name($params);
			
			elseif( method_exists('components', $function_name) )
				return components::$function_name($params);
		}
		return false;
	}

	

	//Анонс - последние N записей
	public function prepareRec($params){

		//Получаем условия
		$where=components::convertParamsToWhere($params);

		//Забираем запись
		$rec=$this->model->makeSql(
			array(
				'tables'=>array($this->getCurrentTable('rec')),
				'where'=>$where,
				'order'=>'order by `date_public` desc',
			),
			'getrow'
		);

		//Раскрываем сложные поля
		$rec=$this->explodeRecord($rec,'rec');
		$rec=$this->insertRecordUrlType($rec);

		//Готово
		return $rec;
	}

	//Анонс - последние N записей
	public function prepareAnons($params){

		//Получаем условия
		$where=components::convertParamsToWhere($params);

		//Забираем запись
		$rec=$this->model->makeSql(
			array(
				'tables'=>array($this->getCurrentTable('rec')),
				'where'=>$where,
				'order'=>'order by `date_public` desc',
			),
			'getrow'
		);

		//Раскрываем сложные поля
		$rec=$this->explodeRecord($rec,'rec');
		$rec=$this->insertRecordUrlType($rec);

		//Готово
		return $rec;
	}

	//Анонс - последние N записей
	public function prepareAnonsList($params){

		//Получаем условия
		$where=components::convertParamsToWhere($params);

		//Условия отображения на сайте
		$where['and'][]='`shw`=1';
		if( IsSet($this->structure['rec']['fields']['show_in_anons']) )
			$where['and'][]='`show_in_anons`=1';

		//Забираем записи
		$recs=$this->model->makeSql(
			array(
				'tables'=>array($this->getCurrentTable('rec')),
				'where'=>$where,
				'order'=>'order by `date_public` desc',
				'limit'=>(isSet($params['limit'])?'limit '.(IsSet($params['start'])?intval($params['start']).', ':'').intval($params['limit']):'')
			),
			'getall'
		);

		//Раскрываем сложные поля
		if($recs)
		foreach($recs as $i=>$rec){
			$rec=$this->explodeRecord($rec,'rec');
			$rec=$this->insertRecordUrlType($rec);
			$recs[$i]=$rec;
		}

		//Готово
		return $recs;
	}

	//Записи - полный список записей
	public function prepareRecs($params){

		//Брать параметры из GET
		if($params['params_from_get']){
			//Получаем условия
			$where=components::convertParamsToWhere($_GET);

		}else{
			//Получаем условия
			$where=components::convertParamsToWhere($params);

		}
		
		//Определяем структуру к которой обращается
		$structure_sid='rec';
		if(IsSet($params['structure_sid']))$structure_sid=$params['structure_sid'];

		//Условия отображения на сайте
		if(!IsSet($params['shw']))
			$where['and'][]='`shw`=1';

		//Сортировка
		if(IsSet($params['order']))
			$order=$params['order'];
		else
			$order=components::getOrderBy($structure_sid);

		//Требуется разбивка на страницы
		if( $params['chop_to_pages'] ){

			//Текущая страница
			$current_page = model::$ask->rec['page'];

			//Всего записей по запросу
			$num_of_records = $this->model->execSql('select count(`id`) as `counter` from `'.$this->getCurrentTable($structure_sid).'` where '.implode(' and ', $where['and']) . ' and (' . ($where['or']?implode(' or ', $where['or']):'1') .')'.' and '.model::pointDomain().'','getrow');
			$num_of_records = $num_of_records['counter'];

			//Записей на страницу
			if(IsSet($params['items_per_page']))$items_per_page=$params['items_per_page'];
			elseif(IsSet(model::$settings['items_per_page']))$items_per_page=model::$settings['items_per_page'];
			else $items_per_page=10;

			//Количество страниц
			$num_of_pages = ceil( $num_of_records / $items_per_page );

			//Забираем записи
			$recs=model::makeSql(
				array(
					'tables'=>array($this->getCurrentTable($structure_sid)),
					'where'=>$where,
					'order'=>$order,
					'limit'=>'limit '.($current_page*$items_per_page).', '.$items_per_page,
				),
				'getall'
			);

			//Раскрываем сложные поля
			if($recs)
			foreach($recs as $i=>$rec){
				$rec=$this->explodeRecord($rec,$structure_sid);
				$rec=$this->insertRecordUrlType($rec, 'html', $params['insert_host']);
				$recs[$i]=$rec;
			}

			//Перелистывания страниц
			$pages=array();
			if( $num_of_pages > 1 ){

				//Учитываем GET-переменные
				$get_vars=false;
				if(IsSet($_GET))
					foreach($_GET as $var=>$val){
						if( is_array($val) ){
							foreach($val as $v)
								$get_vars[]=$var.'[]='.$v;
						}else{
							$get_vars[]=$var.'='.$val;
						}
					}

				//Учитываем другие модификаторы
				$modifiers=false;
				if( count(model::$ask->mode)>0 ){
					$modifiers='.'.implode('.', model::$ask->mode);
				}

				//Зацикливаем перелистывание страниц вправо и влево.
				if($current_page>0)$prev=$current_page-1;else $prev=$num_of_pages-1;
				if($current_page<$num_of_pages-1)$next=$current_page+1;else $next=0;

				//Предыдущая страница
				$pages['prev']['url'] = model::$ask->rec['url'].$modifiers.'.'.$prev.'.'.model::$ask->output.($get_vars?'?'.implode('&', $get_vars):'');
				$pages['prev']['num'] = $prev;

				//Следующая страница
				$pages['next']['url'] = model::$ask->rec['url'].$modifiers.'.'.$next.'.'.model::$ask->output.($get_vars?'?'.implode('&', $get_vars):'');
				$pages['next']['num'] = $next;

				//Другие страницы
				for($i=0;$i<$num_of_pages;$i++){
					$pages['items'][$i]['url']=model::$ask->rec['url'].$modifiers.'.'.$i.'.'.model::$ask->output.($get_vars?'?'.implode('&', $get_vars):'');
				}
			}

			//Заказанные наименования
			if( IsSet(model::$modules['basket']) )
				if( method_exists( model::$modules['basket'], 'insertOrdered' ) ){
					$recs = model::$modules['basket']->insertOrdered($recs);
				}

			//Результат
			$result=array(
				'current'	=>	$current_page,									//Номер текущей страницы
				'from'		=>	$current_page*$items_per_page,					//Номер первой записи на странице
				'till'		=>	($current_page+1)*$items_per_page,				//Номер последней записи на странице
				'limit'		=>	$items_per_page,								//Количество записей на странице
				'count'		=>	ceil($num_of_records / $items_per_page),		//Общее количество страниц
				'recs'		=>	$recs,											//Все записи на странице
				'pages'		=>	$pages,											//Страницы
			);
			
			if(!count($recs)){
				$result['recs'] = false;
				$result['pages'] = false;
			}
			
			//Готово
			return $result;

		//Без разбивки на страницы
		}else{

			//Забираем записи
			$recs=$this->model->makeSql(
				array(
					'tables'=>array($this->getCurrentTable($structure_sid)),
					'where'=>$where,
					'limit'=>(isSet($params['limit'])?'limit '.(IsSet($params['start'])?intval($params['start']).', ':'').intval($params['limit']):''),
					'order'=>$order,
				),
				'getall'
			);//pr(model::$last_sql);

			//Раскрываем сложные поля
			if($recs)
			foreach($recs as $i=>$rec){
				$rec=$this->explodeRecord($rec,$structure_sid);
				$rec=$this->insertRecordUrlType($rec, 'html', $params['insert_host']);
				$recs[$i]=$rec;
			}
			
			//Заказанные наименования
			if( IsSet(model::$modules['basket']) )
				if( method_exists( model::$modules['basket'], 'insertOrdered' ) ){
					$recs = model::$modules['basket']->insertOrdered($recs);
				}

			//Готово
			return $recs;
		}
	}

	//Комментарии к записи
	public function prepareCount($params){

		//Указание на структуру
		if( IsSet($params['structure_sid']) )
			$structure_sid = $params['structure_sid'];
		else
			$structure_sid = 'rec';

		//Получаем условия
		$where=components::convertParamsToWhere($params);
		$where['and']['shw']='`shw`=1';

		//Получаем записи
		$res=$this->model->makeSql(
			array(
				'tables' => array($this->getCurrentTable($structure_sid)),
				'fields' => array( 'count(`id`) as `counter`' ),
				'where' => $where,
			),
			'getrow'
		);//pr($this->model->last_sql);

		//Готово
		return $res['counter'];
	}

	//Случайные записи
	public function prepareRandom($params){

		//Получаем условия
		$where=components::convertParamsToWhere($params);

		//Забираем запись
		$rec=$this->model->makeSql(
			array(
				'tables'=>array($this->getCurrentTable('rec')),
				'where'=>$where,
				'order'=>'order by RAND()'
			),
			'getrow'
		);//pr($this->model->last_sql);

		//Раскрываем сложные поля
		$rec=$this->explodeRecord($rec,'rec');
		$rec=$this->insertRecordUrlType($rec);

		//Готово
		return $rec;
	}

	//Случайные записи
	public function prepareRandomList($params){

		//Получаем условия
		$where=components::convertParamsToWhere($params);

		//Забираем запись
		$recs=$this->model->makeSql(
			array(
				'tables'=>array($this->getCurrentTable('rec')),
				'where'=>$where,
				'order'=>'order by RAND()',
				'limit'=>(isSet($params['limit'])?'limit '.(IsSet($params['start'])?intval($params['start']).', ':'').intval($params['limit']):''),
			),
			'getall'
		);

		//Раскрываем сложные поля
		foreach($recs as $i=>$rec){
			$rec=$this->explodeRecord($rec,'rec');
			$rec=$this->insertRecordUrlType($rec);
			$recs[$i]=$rec;
		}

		//Готово
		return $recs;
	}

	//Родительский раздел текущей записи
	public function prepareParent($params){

		//Определяем структруру и SID родителя
		if($this->structure['rec']['type']=='simple'){
			$parent_structure_sid=$this->structure['rec']['dep_path']['structure'];
		}else{
			$parent_structure_sid='rec';
		}

		//Поле, при помощи которого происходит связка
		$link_field=model::$types[ $this->structure['rec']['dep_path']['link_type'] ]->link_field;

		//SID родителя
		$parent_sid=$params[$link_field];

		//Сортировка
		$order=$this->getOrderBy('rec');

		//Забираем запись
		$rec=$this->model->makeSql(
			array(
				'tables'=>array($this->getCurrentTable($parent_structure_sid)),
				'where'=>array('and'=>array('`'.$link_field.'`="'.mysql_real_escape_string($parent_sid).'"')),
				'order'=>$order,
			),
			'getrow'
		);//pr($this->model->last_sql);

		//Раскрываем сложные поля
		$rec=$this->explodeRecord($rec,'rec');
		$rec=$this->insertRecordUrlType($rec);

		//Готово
		return $rec;
	}

	//Родительский раздел текущей записи
	public function prepareMap($params){

		//Дерево
		$recs=$this->model->prepareShirtTree('start', 'rec', false,5,array('and'=>array('`shw`=1')));

		//Раскрываем сложные поля
		foreach($recs as $i=>$rec){
			$rec=$this->explodeRecord($rec,'rec');
			$rec=$this->insertRecordUrlType($rec);
			$recs[$i]=$rec;
		}

		//Готово
		return $rec;
	}

	//Список страниц
	public function preparePages($params){

		//Достаём общее количество записей
		if(IsSet($params['count'])){
			$recs['counter']=$params['count'];
		}else{
			$recs=$this->model->execSql('select count(`id`) as `counter` from `'.$this->getCurrentTable(model::$ask->structure_sid).'` where `shw`=1 and '.model::pointDomain().'','getrow');
		}


		//Настройка для разбивки записей на страницы
		if(IsSet($params['limit']))$items_per_page=$params['limit'];
		elseif(IsSet(model::$settings['items_per_page']))$items_per_page=model::$settings['items_per_page'];
		else $items_per_page=10;

		//Текущая страница
		$page=intval(model::$ask->rec['page']);//current_page;

		//Сюда будем складывать страницы
		$pages=array();

		//Если записи найдены
		if($recs['counter']){
			$pages=array(
				'current'=>$page,
				'from'=>$page*$items_per_page,
				'till'=>($page+1)*$items_per_page,
				'limit'=>$items_per_page,
				'count'=>ceil($recs['counter']/$items_per_page),
			);

			//Если страниц больше одной
			if($pages['count']>1){

				//Учитываем GET-переменные
				$get_vars=false;
				if(IsSet($_GET))
					foreach($_GET as $var=>$val)
						$get_vars[]=$var.'='.$val;

				//Учитываем другие модификаторы
				$modifiers=false;
				if( count(model::$ask->mode)>0 ){
					$modifiers='.'.implode('.', model::$ask->mode);
				}

				//Зацикливаем перелистывание страниц вправо и влево.
				if($page>0)$prev=$page-1;else $prev=$pages['count']-1;
				if($page<$pages['count']-1)$next=$page+1;else $next=0;

				//Предыдущая страница
				$pages['prev']['url'] = model::$ask->rec['url'].$modifiers.'.'.$prev.'.'.model::$ask->output.($get_vars?'?'.implode('&', $get_vars):'');
				$pages['prev']['num'] = $prev;

				//Следующая страница
				$pages['next']['url'] = model::$ask->rec['url'].$modifiers.'.'.$next.'.'.model::$ask->output.($get_vars?'?'.implode('&', $get_vars):'');
				$pages['next']['num'] = $next;

				//Другие страницы
				for($i=0;$i<$pages['count'];$i++){
					$pages['items'][$i]['url']=model::$ask->rec['url'].$modifiers.'.'.$i.'.'.model::$ask->output.($get_vars?'?'.implode('&', $get_vars):'');
				}
			}
		}

		//Готово
		return $pages;
	}

	//Вход на сайт
	public function prepareTags($params){
		$tags=model::$types['tags']->getTagsCloud();
		return $tags;
	}

	//Переводим список переданных параметров в условия запроса
	public function convertParamsToWhere($params){
		
		//Текущий вывод
		$prepare=$params['data'];

		//Определяем структуру к которой обращается
		$structure_sid='rec';
		if(IsSet($params['structure_sid']))$structure_sid=$params['structure_sid'];

		//Разрешённые параметры в каждом из выводов
		$allowed_params=array(
			'anons'=>array('nid','dir','access'),
			'anonslist'=>array('nid','dir','access','limit','start'),
			'recs'=>array('nid','dir','access','limit','start'),
			'random'=>array('nid','dir','access'),
			'randomlist'=>array('nid','dir','access','limit','start'),
			'parent'=>array(),
		);

		//Формируем условия
		$where=array();
		foreach( (array)$params as $var=>$val){
			$flag=false;

			//Если текущий параметр присутствует в выводе
			if(IsSet($allowed_params[$prepare]))
			if(in_array($var,$allowed_params[$prepare])){

				//nid - исключить запись с указанным ID из списка искомых
				if($var=='nid'){
					$flag=true;
					$where['and']['id']='(not(`id`="'.mysql_real_escape_string($val).'"))';

				//dir - ограничить записи указанным родительским разделом, чей ID указан
				}elseif( ($var=='dir') and ($val !== false) ){
					//Простые структуры
					if($this->structure[$structure_sid]['type']=='simple')
						$field_name='dep_path_'.$this->structure[$structure_sid]['dep_path']['structure'];
					//Древовидные структуры
					else
						$field_name='dep_path_parent';

					$flag=true;

					$where['and'][$field_name]='`'.$field_name.'`="'.mysql_real_escape_string($val).'"';

				//Доступ таргетирован по группам
				}elseif($var=='access'){
					//Только публичные записи
					if($val=='public'){
						$flag=true;
						$where['and']['access']='`access` LIKE "%|all=r__|%"';
					}elseif($val=='group')
						$flag=true;
						$where['and']['access']='`access` LIKE "%|'.mysql_real_escape_string( user::$info['group'] ).'=r__|%"';
				}
			}

			//Для других полей, объявленных в структуре
			//Flag для того, чтобы исключить повторное добавление системных полей
			if(!$flag){
				if(IsSet($this->structure[$structure_sid]['fields'][$var])){
					if( in_array( $this->structure[$structure_sid]['fields'][$var]['type'] ,array('menum','linkm') ) ){
						if(is_array($val)){
							foreach($val as $i=>$v)if(!strlen($v))UnSet($val[$i]);
							if($val[0])
								$where['and'][$var] = '( (`'.$var.'` LIKE "%|'.implode('|%") or (`'.$var.'` LIKE "%|', urldecode($val) ).'|%") )';
						}else{
							$where['and'][$var] = '`'.$var.'` LIKE "%|'.mysql_real_escape_string( urldecode($val) ).'|%"';
						}
					}elseif( $val === 'notnull' ){
						$where['and'][$var]='`'.$var.'`!="0"';
					}elseif( $val === 'notempty' ){
						$where['and'][$var]='`'.$var.'`!=""';
					}else{
						if(is_array($val)){
							foreach($val as $i=>$v)if(!strlen($v))UnSet($val[$i]);
							if($val[0])
								$where['and'][$var] = '((`'.$var.'`="'.implode('") or (`'.$var.'`="', $val).'") )';
						}else{
							$where['and'][$var] = '`'.$var.'`="'.mysql_real_escape_string($val).'"';
						}
					}
				}
			}
		}

		//Готово
		return $where;
	}

	//Сортировка по умолчанию
	public function getOrderBy($structure_sid){
		//Сортировка деревьев
		if($this->structure[$structure_sid]['type']=='tree')return 'order by `left_key`';
		//Сортирвка по POS
		elseif(IsSet($this->structure[$structure_sid]['fields']['pos']))return 'order by `pos`,`title`';
		//Сортировка по публичной дате
		else return 'order by `date_public` desc,`title`';
	}

	
}

?>