<?php

/************************************************************/
/*															*/
/*	Ядро системы управления Asterix	CMS						*/
/*		Тип данных - Фотоизображение						*/
/*															*/
/*	Версия ядра 2.0.b5										*/
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

class field_type_image extends field_type_default
{
	public $default_settings = array(
		'sid' => false, 
		'title' => 'Картинка', 
		'value' => 0,
		'resize_type' => 'inner', 
		'resize_width' => 250, 
		'resize_height' => 250, 
	);
	
	//Разрешённые форматы файлов для загрузки
	private $allowed_extensions = array(
		'image/jpeg' => 'jpg', 
		'image/gif' => 'gif', 
		'image/png' => 'png'
	);
	
	public $template_file = 'types/image.tpl';
	
	//Поле участввует в поиске
	public $searchable = false;
	
	public function creatingString($name){
		return '`' . $name . '` text not null';
	}
	
	//Подготавливаем значение для SQL-запроса
	public function toValue($value_sid, $values, $old_values = array(), $settings = false, $module_sid, $structure_sid){
		$data = false;
	
		//Коррекция типа данных
		$this->correctFieldType($module_sid, $structure_sid, $value_sid);
		
		require_once $this->model->config['path']['libraries'].'/acmsFiles.php';
		require_once $this->model->config['path']['libraries'].'/acmsImages.php';
		
		//Удаление фотки
		if ($values[$value_sid . '_delete']) {
			$old_path = substr( $values[ $value_sid.'_old_id' ], 0, strpos( $values[ $value_sid.'_old_id' ], '|' ) );
			acmsFiles::delete($this->model->config['path']['www'] . $this->model->config['path']['public_images'] . '/' . $old_path);
			
		//Файл передан
		} elseif (strlen( $values[$value_sid]['tmp_name'] ) ) {
			
			//Обновление картинки
			if( @$values[$value_sid . '_old_id'] ){
				$old_path = substr( $values[ $value_sid.'_old_id' ], 0, strpos( $values[ $value_sid.'_old_id' ], '|' ) );
				acmsFiles::delete($this->model->config['path']['www'] . $this->model->config['path']['public_images'] . '' . $old_path);
				$image_id = 0;
			}
			
			//Проверка уникальности имени файла
			$name = acmsFiles::unique( $values[$value_sid]['name'], $this->model->config['path']['www'] . $this->model->config['path']['public_images'] );
			
			//Проверка корректности имени файла
			$name = acmsFiles::filename_filter( $name );
			
			//Расширение файла
			$ext = substr($name, strrpos($name, '.') + 1);
			
			//Загружаем файл
			$filename = acmsFiles::upload( $values[$value_sid]['tmp_name'], $this->model->config['path']['www'] . $this->model->config['path']['public_images'] . '/' . $name );
			
			//Ужимаем до нужного размера и перезаписываем
			$acmsImages = new acmsImages;
			$data = $acmsImages->resize( $filename, false, $settings['resize_type'], @$settings['resize_width'], @$settings['resize_height'] );
			
			//Доп.характеристики
			$data['type'] = $values[$value_sid]['type'];
			$data['path'] = $this->model->config['path']['public_images'] . '/'. $name;
			$data['title'] = strip_tags( $values[$value_sid . '_title'] );
			
			//Определяем основные цвета картинки
			$data['colors'] = $acmsImages->colors( $filename );
		
			//Обрезка по маске
			if( @$values[$value_sid.'_cut_mask']['size']>0 ){
				$filename = $acmsImages->cut_mask( $filename, false, $values[$value_sid.'_cut_mask'] );
				$data['path'] = str_replace( '.'.$ext, '.png', $data['path'] );
				$ext = 'png';
			}
	
			//Установка Watermark
			if( @$values[$value_sid.'_watermark']['size']>0 )
				$acmsImages->put_watermark( $filename, false, $values[$value_sid.'_watermark'], $values[$value_sid.'_watermark_side']);
	
			//Делаем превьюшки
			if( IsSet( $settings['pre'] ) )
				foreach( $settings['pre'] as $sid => $pre){
					$pre_filename = str_replace( '.'.$ext, '_'.$sid.'.'.$ext, $filename );
					$data[ $sid ] = $this->model->config['path']['public_images'] . '/' . str_replace( '.'.$ext, '_'.$sid.'.'.$ext, basename($data['path']) );
					$acmsImages->resize( $filename, $pre_filename, $pre['resize_type'], @$pre['resize_width'], @$pre['resize_height'] );
					
					//Фильтры - чёлно-белый
					if( is_array($values[$value_sid.'_filter']['bw']) )
					if( in_array($sid, $values[$value_sid.'_filter']['bw']) )
						$acmsImages->filter_bw($pre_filename);
					
				}
		
		//Файл не передан, просто обновление Alt
		} elseif (strlen($values[$value_sid . '_old_id'])) {
			$data = $this->getValueExplode( $old_values[$value_sid] );
			$data['title'] = strip_tags( $values[$value_sid . '_title'] );
		}

		//Готово
		if( $data )
			return serialize( $data );
		else
			return 0;
	}
	
	
	//Получить развёрнутое значение из простого значения
	public function getValueExplode($value, $settings = false, $record = array()){
		if( is_string($value) )
			$rec = unserialize( $value );
		
		//Совместимость со старым форматом хранения
		if ( !$rec ) {
			$rec = array();
			
			//Данные
			if(!is_array($value)){
				list($rec['path'], $rec['type'], $rec['size'], $rec['width'], $rec['height'], $rec['title'], $rec['realname'], $rec['colors']) = explode('|', $value);
				$rec['colors'] = explode(',', $rec['colors']);
			}
			
			//ID
			$rec['id'] = $value;
			
			//Превьюшки
			if (IsSet($settings['pre'])) {
				//Новое имя
				$name = substr($rec['path'], 0, strrpos($rec['path'], '.'));
				$ext  = substr($rec['path'], strrpos($rec['path'], '.') + 1);
				
				//Создаём данные о превьюшках
				foreach ($settings['pre'] as $sid => $pre) {
					$rec[$sid] = $name . '_' . $sid . '.' . $ext;
				}
			}
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
		$sql = 'select DATA_TYPE from information_schema.COLUMNS where TABLE_SCHEMA="'.$this->model->config['db']['system']['name'].'" and TABLE_NAME="'.$this->model->modules[ $module_sid ]->getCurrentTable($structure_sid).'" and COLUMN_NAME="'.$field_sid.'"';
		$res = $this->model->execSql($sql, 'getrow');
		if( $res['DATA_TYPE'] != 'text' ){
			$sql = 'alter table `'.$this->model->modules[ $module_sid ]->getCurrentTable($structure_sid).'` modify '.$this->creatingString( $field_sid );
			$res = $this->model->execSql($sql, 'update');
		}
	}
	
}

?>