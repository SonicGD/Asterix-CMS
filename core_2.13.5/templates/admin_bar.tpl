<ul id="acms_bar">
	<li class="a_logo"><img src="http://src.sitko.ru/i/logo_adm.png" style="height:29px;" alt="Asterix CMS" /></li>
	<li class="a_wide"><a href="/" rel="tree" class="call_admin_interface">Дерево сайта</a></li>
	<li class="a_narrow">
		<a rel="add" href="#" OnClick="return false;">Добавить</a>
		<ul class="a_sub">
{foreach from=$add.recs item=rec}
			<li><a href="/add/{$rec.module}/{$rec.structure_sid}.html" class="call_admin_interface" rel="add">{$rec.structure}</a></li>
{/foreach}
			<li><br /></li>
{foreach from=$add.subs item=rec}
			<li><a href="/add/{$rec.module}/{$rec.structure_sid}.html" class="call_admin_interface" rel="add">{$rec.structure}{if $rec.structure_sid != 'rec'} в {$rec.title}{/if}</a></li>
{/foreach}
		</ul>
	</li>
	<li class="a_wide"><a rel="edit" href="" class="call_admin_interface">Изменить</a></li>
	<li class="a_narrow">
		<a href="#">Настройки</a>
		<ul class="a_sub">
			<li><a rel="settings" href="" class="call_admin_interface">Настройки сайта</a></li>
			<li style="margin-bottom:10px;"><a rel="users" href="" class="call_admin_interface">Пользователи</a></li>
			<li><a rel="css" href="" class="call_admin_interface">Стили</a></li>
			<li><a rel="js" href="" class="call_admin_interface">JavaScript</a></li>
			<li><a rel="modules" href="" class="call_admin_interface">Модули</a></li>
			<li><a rel="templates" href="" class="call_admin_interface">Шаблоны</a></li>
		</ul>
	</li>
	<li class="a_narrow">
		<a rel="help" href="#">Помощь</a>
		<ul class="a_sub">
			<li><a href="http://admin.sitko.ru/tree.html" class="out">Помощь по системе управления</a></li>
			<li><a href="http://sitko.ru" class="out">Подробнее о разработчике</a></li>
			<li><a href="http://asterix.opendev.ru" class="out">Подробнее о системе управления</a></li>
		</ul>
	</li>
	<li class="a_narrow">
		<a rel="about" href="#">О сайте</a>
		<ul class="a_sub">
			<li>Сайт: {$settings.domain_title|cut:40}</li>
			<li>Создан: {$domain.date_public.day} {$domain.date_public.month_title} {$domain.date_public.year} года.</li>
			<li>Asterix CMS, <a href="http://asterix.opendev.ru/about/news.html" class="out">версия {$config.version}</a></li>
			<li><a href="https://github.com/dekmabot/Asterix-CMS/commits/master" target="_blank">Обновления ядра на GitHub</a></li>
		</ul>
	</li>
	<li class="a_narrow">
		<a href="#">{$user.title}</a>
		<ul class="a_sub">
			<li><a rel="exit" href="?logout=yes">Выход</a></li>
		</ul>
	</li>
{if $settings.update_available}
	<li OnClick="$j('#acms_update_bar').toggle('fast');" style="cursor:pointer;">
		&nbsp;
		<img src="http://src.sitko.ru/3.0/i/icon_up.gif" alt="Найдено обновление" />
		&nbsp;
	</li>
	<div id="acms_update_bar" style="
		display: none;
		width: 80%;
		background-color: #07B52A;
		height: 28px;
		position: absolute;
		top: 30px;
		margin: 0 10%;
		border-radius: 0 0 10px 10px;
		color: white;
		line-height: 28px;
	">Найдено бесплатное обновление для вашей Asterix CMS 
		<a style="
			color: white;
			text-decoration: underline;	
		" href="http://asterix.opendev.ru/news.html" target="_blank">до версии 2.14</a>.
		<a href="{$settings.update_available}" style="
			background-color: white;
			color: #07B52A;
			display: inline-block;
			padding: 0 5px;
			border-radius: 5px;
			line-height: 20px;
			margin: 0 10px;
		" target="_blank">Установить</a>
	</div>
{/if}

</ul>

<div id="acms_content">
	<div class="acms_submit_out">
		<img class="acms_cancel" src="http://src.sitko.ru/i/error.png" alt="Закрыть без сохранения" />
	</div>
	<div id="bar_content"></div>
</div>