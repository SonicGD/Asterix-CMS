<?php

/************************************************************/
/*															*/
/*	Ядро системы управления Asterix	CMS						*/
/*		Прототип модуля										*/
/*															*/
/*	Версия ядра 2.14										*/
/*	Версия скрипта 2.0										*/
/*															*/
/*	Copyright (c) 2009  Мишин Олег							*/
/*	Разработчик: Мишин Олег									*/
/*	Email: dekmabot@gmail.com								*/
/*	WWW: http://mishinoleg.ru								*/
/*	Создан: 10 февраля 2009	года							*/
/*	Модифицирован: 27 декабря 2011 года						*/
/*															*/
/************************************************************/

//Модуль по умолчанию
class default_module{

	//Приставка перед таблицей в дазе данных - пока не используется
	public $database_table_preface=false;

	// Идентификатор базы данных, у основного модуля всегда system,
	public $db_sid='system';

	//Шаблоны в модуле по умолчанию
	public $templates=array(
		'index'=>				array('sid'=>'index',			'title'=>'Главная страница модуля'),
		'content'=>				array('sid'=>'content',			'title'=>'Страница модуля одной записи'),
	);

	//Шаблоны в модуле по умолчанию
	public $prepares=array();
	public $interfaces=array();

////////////////////////////
/// ИНИЦИАЛИЗАЦИЯ МОДУЛЯ ///
////////////////////////////

	//Инициализация модуля
	public function __construct($model, $module_info){
		$this->model = $model;
		$this->info = $module_info;

		require_once model::$config['path']['core'].'/classes/structures.php';
		require_once model::$config['path']['core'].'/classes/components.php';
		require_once model::$config['path']['core'].'/classes/interfaces.php';
		require_once model::$config['path']['core'].'/classes/acms_trees.php';
		
		structures::load();
		components::load();
		interfaces::load();
	}

	//Инициализация структуры модуля
	public function initStructure(){
		structures::initStructure();
	}

	//Инициализация компонентов
	public function initComponent($prepare,$params){
		return components::init($prepare,$params);
	}

	//Эти функции используются в модулях для донастройки структуры и интерфейсов
	public function setStructure(){}
	public function setInterfaces(){}


//////////////////
/// ИНТЕРФЕЙСЫ ///
//////////////////

	//Получить содержимое интерфейса
	public function prepareInterface($prepare,$params, $public = false){
		return interfaces::prepareInterface($prepare,$params, $public);
	}
	
	//Запустить обработчик интерфейса
	public function controlInterface($interface,$params, $public = false){
		return interfaces::controlInterface($interface,$params, $public);
	}
	
	//Ответ на запрос интерфейса
	public function answerInterface($interface,$result){
		return interfaces::answerInterface($interface,$result);
	}

////////////////////////////////////////////
/// РАБОТА С ДЕРЕВЬЯМИ МОДУЛЯ И СТРУКТУР ///
////////////////////////////////////////////

	//Показать краткое дерево модуля
	public function getModuleShirtTree($root_record_id = false,$structure_sid = 'rec',$levels_to_show=0,$conditions=array()){
		return acms_trees::getStructureShirtTree($root_record_id,$structure_sid,$levels_to_show,$conditions);
	}

	//Показать краткое дерево сруктуры
	public function getStructureShirtTree($root_record_id,$structure_sid,$levels_to_show,$conditions){
		return acms_trees::getStructureShirtTree($root_record_id,$structure_sid,$levels_to_show,$conditions);
	}

//////////////////////
/// ПОИСКИ ЗАПИСЕЙ ///
//////////////////////

	//Забрать запись по ID
	public function getRecordById($structure_sid,$id){
		return ModelFinder::getRecordById($structure_sid,$id);
	}

	//Забрать запись по SID
	public function getRecordBySid($structure_sid,$sid){
		return ModelFinder::getRecordBySid($structure_sid,$sid);
	}

	//Забрать запись по WHERE
	public function getRecordsByWhere($structure_sid,$where){
		return ModelFinder::getRecordsByWhere($structure_sid,$where);
	}


//////////////////////////////////////////////////////////////////////
/// ДЕЙСТВИЯ МОДУЛЯ ПО ОТНОШЕНИЮ К СВОИМ ОБЪЕКТАМ ///
/////////////////////////////////////////////////////////////////////

	//Добавление записи в структуру модуля
	public function addRecord($record, $structure_sid = 'rec', $conditions=false){
		//Для версий до 2.14 параметры шли наоборот, сохраняем обратную совместимость
		if( !is_array($record) ){$k = $record; $record = $structure_sid; $structure_sid = $k; }
		return interfaces::addRecord($record, $structure_sid, $conditions);
	}

	//Добавление записи в структуру модуля
	public function editRecord($record, $structure_sid = 'rec', $conditions=false){
		//Для версий до 2.14 параметры шли наоборот, сохраняем обратную совместимость
		if( !is_array($record) ){$k = $record; $record = $structure_sid; $structure_sid = $k; }
		return interfaces::editRecord($record, $structure_sid, $conditions);
	}

	//Удаление записи
	public function deleteRecord($record, $structure_sid = 'rec', $conditions=false){
		//Для версий до 2.14 параметры шли наоборот, сохраняем обратную совместимость
		if( !is_array($record) ){$k = $record; $record = $structure_sid; $structure_sid = $k; }
		return interfaces::deleteRecord($record, $structure_sid, $conditions);
	}

	//Переместить на одну позицию выше
	public function moveUp($record, $structure_sid = 'rec', $conditions=false){
		//Для версий до 2.14 параметры шли наоборот, сохраняем обратную совместимость
		if( !is_array($record) ){$k = $record; $record = $structure_sid; $structure_sid = $k; }
		return interfaces::moveUp($record, $structure_sid, $conditions);
	}

	//Переместить на одну позицию ниже
	public function moveDown($record, $structure_sid = 'rec', $conditions=false){
		//Для версий до 2.14 параметры шли наоборот, сохраняем обратную совместимость
		if( !is_array($record) ){$k = $record; $record = $structure_sid; $structure_sid = $k; }
		return interfaces::moveDown($record, $structure_sid, $conditions);
	}

	//Переместить на одну позицию ниже
	public function moveTo($record, $structure_sid = 'rec', $conditions=false){
		//Для версий до 2.14 параметры шли наоборот, сохраняем обратную совместимость
		if( !is_array($record) ){$k = $record; $record = $structure_sid; $structure_sid = $k; }
		return interfaces::moveTo($record, $structure_sid, $conditions);
	}

	//Переместить на одну позицию ниже
	public function updateChildren($structure_sid, $old_data, $new_data, $new_url, $condition = false, $domain = false){
		return interfaces::moveDownupdateChildren($structure_sid, $old_data, $new_data, $new_url, $condition, $domain);
	}


///////////////////////////////////////////
/// ВНУТРЕННИЕ СЛУЖЕБНЫЕ ФУНКЦИИ МОДУЛЯ ///
///////////////////////////////////////////

	//Дополнительная обработка записи при типе вывода "content"
	public function contentPrepare($rec,$structure_sid='rec'){
		return $rec;
	}

	//Вернуть массив основных полей структуры
	public function getMainFields($structure_sid = 'rec'){
		return structures::getMainFields($structure_sid);
	}

	//Возвращаем название таблицы текущей структуры
	public function getCurrentTable($part = 'rec'){
		return $this->database_table_preface.$this->info['prototype'].'_'.$part;
	}

	//Разворачиваем значения полей перед выводом в браузер
	public function explodeRecord($rec,$structure_sid='rec'){
		return structures::explodeRecord($rec,$structure_sid);
	}

	//Вставка html или других окончаний для URL-ов записей
	public function insertRecordUrlType($recs, $type='html', $insert_host = false){
		return structures::insertRecordUrlType($recs, $type='html', $insert_host = false);
	}
	
	//Получить иерархию структур модуля
	public function getLevels($structure, $level_tree = false){
		$level_tree[]=$structure;

		//Структура без зависимостей
		if($this->structure[$structure]['type']=='tree'){
			$level_tree[]=$structure;

		//Учитываем найденную зависимость
		}elseif($this->structure[$structure]['dep_path']){
			$new_structure=$this->structure[$structure]['dep_path']['structure'];
			$level_tree=$this->getLevels($new_structure, $level_tree);
		}

		return $level_tree;
	}

	//Следующий свободный ID в структуре
	public function genNextId($structure_sid='rec'){
		$last=model::execSql('select `id` from `'.$this->getCurrentTable($structure_sid).'` order by `id` desc','getrow');
		if(!IsSet($last['id']))$last['id']=1;
		return $last['id']+1;
	}
	public function getNextId($structure_sid='rec'){
		return $this->genNextId($structure_sid);	
	}

	//Представить запись в виде вершины социального графа
	public function getGraphTop($record_id, $structure_sid='rec'){
		return array( 'module' => $this->info['sid'], 'structure_sid' => $structure_sid, 'id' => $record_id );
	}

	//Проверка наличия доступа к записи
	public function checkAccess($record, $interface_sid){
		
		//Авторы
		if( $record['author'] == user::$info['id'] )
			return true;
		//Ответ
		return false;
	}
	
	public function unitTests(){
		require_once model::$config['path']['core'].'/tests/units.php';
		unitTests::forModule();
	}
	
	public function convertParamsToWhere($params){
		return components::convertParamsToWhere($params);
	}
	public function getOrderBy($params){
		return components::getOrderBy($params);
	}
}

?>