<?php

/************************************************************/
/*															*/
/*	Ядро системы управления Asterix	CMS						*/
/*		Тип данных - Файл									*/
/*															*/
/*	Версия ядра 2.0.b5										*/
/*	Версия скрипта 1.00										*/
/*															*/
/*	Copyright (c) 2009  Мишин Олег							*/
/*	Разработчик: Мишин Олег									*/
/*	Email: dekmabot@gmail.com								*/
/*	WWW: http://mishinoleg.ru								*/
/*	Создан: 10 февраля 2009	года							*/
/*	Модифицирован: 18 марта 2010 года						*/
/*															*/
/************************************************************/

class field_type_file extends field_type_default
{
	public $default_settings = array('sid' => false, 'title' => 'Файл', 'value' => 0, 'width' => '100%', /*
	inner - по минимальному размеру (изображение не более указанных размеров)
	outer - по максимальному разделу  (изобажение не меньше указанных размеров)
	width - по ширине (изображение по ширине соответствует указанному размеру)
	height - по высоте (изображение по высоте соответствует указанному размеру)
	exec - по указанным размерам (изображение соответствует указанным размерам, без сохранения пропорций)
	*/ 'resize_type' => 'inner', 'resize_width' => 250, 'resize_height' => 250, 'resize_proportions' => true);

	//Разрешённые форматы файлов для загрузки
	private $allowed_extensions = array(
		'image/jpeg' => 'jpg',
		'image/gif' => 'gif',
		'image/png' => 'png',
		'image/x-icon' => 'ico',
		'application/msword' => 'doc',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
		'application/octet-stream' => 'docx',
		'xls' => 'xls',
		'application/vnd.ms-excel' => 'xls',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
		'application/vnd.ms-powerpoint' => 'ppt',
		'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
		'application/pdf' => 'pdf',
		'application/vnd.oasis.opendocument.text' => 'odt',
		'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
		'text/plain' => 'txt',
		'text/xml' => 'xml',
		'application/x-zip-compressed' => 'zip',
		'7z' => '7z',
		'application/x-shockwave-flash'=>'swf'
	);

	public $template_file = 'types/file.tpl';

	private $table = 'images';

	//Поле участвует в поиске
	public $searchable = false;

	public function creatingString($name)
	{
		return '`' . $name . '` VARCHAR(255) NOT NULL';
	}

	//Подготавливаем значение для SQL-запроса
	public function toValue($value_sid, $values, $old_values = array(), $settings = false)
	{

		//Коррекция типа данных
		$this->correctFieldType($module_sid, $structure_sid, $value_sid);
		
		require_once model::$config['path']['core'] . '/../libs/acmsFiles.php';

		//Настройки поля, переданные из модуля
		if ($settings)
			foreach ($settings as $var => $val)
				$this->$var = $val;

		//Удаление фотки
		if ($values[$value_sid . '_delete']) {
			$old_data = $this->getValueExplode( $values[$value_sid . '_old_id'] );
			acmsFiles::delete(model::$config['path']['files'] . $old_data['path']);
			
		//Файл передан
		} elseif (strlen($values[$value_sid]['tmp_name'])) {
			
			//Обновление картинки
			if( @$values[$value_sid . '_old_id'] ){
				$old_data = $this->getValueExplode( $values[$value_sid . '_old_id'] );
				acmsFiles::delete(model::$config['path']['files'] . $old_data['path']);
				$image_id = 0;
			}
			
			//Проверка уникальности имени файла
			$name = acmsFiles::unique( $values[$value_sid]['name'], model::$config['path']['files'] );
			
			//Проверка корректности имени файла
			$name = acmsFiles::filename_filter( $name );
			
			//Расширение файла
			$ext = substr($name, strrpos($name, '.') + 1);
			
			//Загружаем файл
			$filename = acmsFiles::upload( $values[$value_sid]['tmp_name'], model::$config['path']['files'] . '/' . $name );

			//Доп.характеристики
			$data['type'] = $values[$value_sid]['type'];
			$data['path'] = model::$config['path']['public_images'] . '/'. $name;
			$data['title'] = strip_tags( $values[$value_sid . '_title'] );
			$data['date'] = date("Y-m-d H:i:s");
			$data['size'] = filesize( model::$config['path']['files'] . '/'. $name );

		
		//Файл не передан, просто обновление Alt
		} elseif (strlen($values[$value_sid . '_title'])) {
			$data = $this->getValueExplode( $values[$value_sid . '_old_id'] );
			$data['title'] = strip_tags( $values[$value_sid . '_title'] );
		}

		//Готово
		if( $data )
			return serialize( $data );
		else
			return 0;
	}


	//Получить развёрнутое значение из простого значения
	public function getValueExplode($rec, $settings = false, $record = array()){
		
		if( !is_array($rec) )
			$rec_old = $rec;
			$rec = unserialize( $rec );
			$rec['old'] = $rec_old;
		
		//Совместимость со старым форматом хранения
		if ( !$rec ) {
			$rec = array();
			
			//Данные
			if(!is_array($value)){
				list($rec['path'], $rec['type'], $rec['size'], $rec['title'], $rec['realname']) = explode('|', $value);
			}
			
			//ID
			$rec['id'] = $value;
			$rec['old'] = serialize($rec);
		}
		
		//Готово
		return $rec;
	}

	//Получить развёрнутое значение для системы управления из простого значения
	public function getAdmValueExplode($value, $settings = false, $record = array()){
		return $this->getValueExplode($value, $settings, $record);
	}





	
	//Проверяем, что поле имеет тим TEXT
	private function correctFieldType($module_sid, $structure_sid, $field_sid){
		if( $module ){
			$sql = 'select DATA_TYPE from information_schema.COLUMNS where TABLE_SCHEMA="'.model::$config['db']['system']['name'].'" and TABLE_NAME="'.model::$modules[ $module_sid ]->getCurrentTable($structure_sid).'" and COLUMN_NAME="'.$field_sid.'"';
			$res = $this->model->execSql($sql, 'getrow');
			if( $res['DATA_TYPE'] != 'text' ){
				$sql = 'alter table `'.model::$modules[ $module_sid ]->getCurrentTable($structure_sid).'` modify '.$this->creatingString( $field_sid );
				$res = $this->model->execSql($sql, 'update');
			}
		}
	}
}

?>