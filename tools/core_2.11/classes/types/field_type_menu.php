<?php

/************************************************************/
/*															*/
/*	Ядро системы управления Asterix	CMS						*/
/*		Тип данных - Выбор из списка						*/
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

class field_type_menu extends field_type_default
{
	public $default_settings = array('sid' => false, 'title' => 'Пользователь системы', 'value' => 0, 'width' => '100%');
	
	public $template_file = 'types/menu.tpl';
	
	//Поле, используемое для связки
	public $link_field='sid';
	
	private $table = 'users';
	
	//Поле участввует в поиске
	public $searchable = false;
	
	public function creatingString($name)
	{
		return '`' . $name . '` VARCHAR(255) NOT NULL';
	}
	
	//Подготавливаем значение для SQL-запроса
	public function toValue($value_sid, $values, $old_values = array(), $settings = false)
	{
		//Настройки поля, переданные из модуля
		if ($settings)
			foreach ($settings as $var => $val)
				$this->$var = $val;
		
		//Готово
		return $values[$value_sid];
	}
	
	//Получить развёрнутое значение для системы управления из простого значения
	public function getAdmValueExplode($value, $settings = false, $record = array())
	{

		$res      = array();
	
		//Варианты значений
		$variants = $settings['variants'];
		
		//Тип menu, используемый в dep_path
		if(!$variants)
			if( IsSet( $settings['module'] ) && IsSet( $settings['structure_sid'] ) ){
				$recs=$this->model->makeSql(
					array(
						'tables'=>array( $this->model->modules[ $settings['module'] ]->getCurrentTable( $settings['structure_sid'] ) ),
						'fields'=>array( $this->link_field ),
						'order'=>'order by `'.$this->link_field.'`'
					),
					'getall'
				);
				foreach($recs as $rec){
					$variants[] = $rec[ $this->link_field ];
				}
			}
			
		
		//Отмечаем в массиве выбранные элементы
		if (is_array($variants))
			foreach ($variants as $i => $variant)
				$res[] = array(
					'value' => $variant,
					'title' => $variant,
					'selected' => ($variant == $value)
				);

		//Готово
		return $res;
	}
}

?>