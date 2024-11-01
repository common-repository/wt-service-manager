<?php
/*
Plugin Name: WT Менеджер услуг
Plugin URI: http://web-technology.biz/cms-wordpress/plugin-wt-service-manager-for-cms-wordpress
Description: Удобное управление услугами и ценами
Version: 1.1.0
Author: Роман Кусты, АИТ "WebTechology"
Author URI: http://web-technology.biz
*/

require('admin_panel.php');
require('wt-service-widget.php');

class WtServiceManager
{
	var $admin;

	function __construct(){	
		add_action('init', array($this, 'register_post_type_service'));
		add_action('init', array($this, 'register_taxonomy_service_category'), 0);
		add_filter('post_updated_messages', array($this, 'post_type_service_messages'));

		// Подключаем виджет
		add_action('widgets_init', array($this, 'widget_init'));

		// Регистрируем шорткод и хук для него
		add_shortcode('wt_service_table', array (&$this, 'shortcode_service_table_action'));

		// Подключаем панель администратора
		if (defined('ABSPATH') && is_admin()) {
		    $this->admin = new WtServiceManagerAdmin();
		}		
	}

	public static function basename() {
        return plugin_basename(__FILE__);
    }

    // Регистрация типа постов "Услуги"
    function register_post_type_service() {
		$labels = array(
			'name' => 'Услуги',
			'singular_name' => 'Услугу', // админ панель Добавить->Функцию
			'add_new' => 'Добавить услугу',
			'add_new_item' => 'Добавить новую услугу', // заголовок тега <title>
			'edit_item' => 'Редактировать услугу',
			'new_item' => 'Новая услуга',
			'all_items' => 'Все услуги',
			'view_item' => 'Просмотр услуги на сайте',
			'search_items' => 'Искать услугу',
			'not_found' =>  'Услуг не найдено.',
			'not_found_in_trash' => 'В корзине нет услуг.',
			'menu_name' => 'Услуги' // ссылка в меню в админке
		);
		$args = array(
			'labels' => $labels,
			'public' => true,
			'show_ui' => true, // показывать интерфейс в админке
			'has_archive' => true, 
			'menu_icon' => 'dashicons-book', // иконка в меню
			'menu_position' => 20, // порядок в меню
			'supports' => array( 'title', 'editor', 'revisions', 'page-attributes'),
			'taxonomies' => array('service_type')
		);
		register_post_type('service', $args);
	}

	// Регистрация таксономии "Категория услуг"
	function register_taxonomy_service_category() {	

		register_taxonomy(
			'service_category',
			array('service'),
			array(
				'hierarchical' => true, /* true - по типу рубрик, false - по типу меток, по умолчанию - false */
				'labels' => array(
					/* ярлыки, нужные при создании UI, можете
					не писать ничего, тогда будут использованы
					ярлыки по умолчанию */
					'name' => 'Категории услуг',
					'singular_name' => 'Категория услуги',
					'search_items' =>  'Найти категорию',
					'popular_items' => 'Популярные категории',
					'all_items' => 'Все категории',
					'parent_item' => null,
					'parent_item_colon' => null,
					'edit_item' => 'Редактировать категорию услуги', 
					'update_item' => 'Обновить категории услуг',
					'add_new_item' => 'Добавить новую категорию',
					'new_item_name' => 'Название новой категории услуг',
					'add_or_remove_items' => 'Добавить или удалить категорию услуги',
					'choose_from_most_used' => 'Выбрать из наиболее часто используемых категорий услуг',
					'not_found' =>  'Категории услуг не найдены.',
					'not_found_in_trash' => 'В корзине нет категорий услуг.',
					'menu_name' => 'Категории'
				),
				'public' => true, 
				/* каждый может использовать таксономию, либо
				только администраторы, по умолчанию - true */
				'show_in_nav_menus' => true,
				/* добавить на страницу создания меню */
				'show_ui' => true,
				/* добавить интерфейс создания и редактирования */
				'show_tagcloud' => true,
				/* нужно ли разрешить облако тегов для этой таксономии */
				'update_count_callback' => '_update_post_term_count',
				/* callback-функция для обновления счетчика $object_type */
				'query_var' => true,
				/* разрешено ли использование query_var, также можно 
				указать строку, которая будет использоваться в качестве 
				него, по умолчанию - имя таксономии */
				'rewrite' => array(
				/* настройки URL пермалинков */
					'slug' => 'service-category', // ярлык
					'hierarchical' => false // разрешить вложенность
	 
				),
			)
		);
	}

	// Тексты уведомлений
	function post_type_service_messages( $messages ) {
		global $post, $post_ID;
	 
		$messages['service'] = array( // service - название созданного нами типа записей
			0 => '', // Данный индекс не используется.
			1 => sprintf( 'Услуга обновлена. <a href="%s">Просмотр</a>', esc_url( get_permalink($post_ID) ) ),
			2 => 'Параметр обновлён.',
			3 => 'Параметр удалён.',
			4 => 'Услуга обновлена',
			5 => isset($_GET['revision']) ? sprintf( 'Услуга восстановлена из редакции: %s', wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( 'Услуга опубликована на сайте. <a href="%s">Просмотр</a>', esc_url( get_permalink($post_ID) ) ),
			7 => 'Услуга сохранена.',
			8 => sprintf( 'Отправлено на проверку. <a target="_blank" href="%s">Просмотр</a>', esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( 'Запланировано на публикацию: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Просмотр</a>', date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			10 => sprintf( 'Черновик обновлён. <a target="_blank" href="%s">Просмотр</a>', esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);
	 
		return $messages;
	}

	function widget_init(){
		register_widget('WtServiceWidget');
	}

	function shortcode_service_table_action($param, $content){

		$args = array(
			'post_type' => 'service',
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'numberposts' => -1 // Количество выводимых позиций
		);

		if (!empty($param['filter_service_category'])) $args['service_category'] = $param['filter_service_category'];

		$services = get_posts($args);

		if (count($services) == 0) {
			echo 'Услуги отсутствуют';
			return;
		}

		// Определяем стили
		$table_class = '';
		$td_service_class = '';
		$td_price_class = '';

		if (!empty($param['table_class'])) $table_class .= ' '.$param['table_class'];

		if (!empty($param['td_class'])){
			$td_service_class .= ' '.$param['td_class'];
			$td_price_class .= ' '.$param['td_class'];
		}

		if (!empty($param['td_service_class'])) $td_service_class .= ' '.$param['td_service_class'];
		if (!empty($param['td_price_class'])) $td_price_class .= ' '.$param['td_price_class'];

		// Выводим таблицу
		echo '<table width="100%" class="'.$table_class.'">';

		// Проверяем наличие вывода заголовков
		if (!isset($param['thead_view']) || (!empty($param['thead_view']) && $param['thead_view'] !== 0)) {

			$th_service_class = '';
			$th_price_class = '';

			if (!empty($param['th_class'])) {
				$th_service_class .= ' '.$param['th_class'];
				$th_price_class .= ' '.$param['th_class'];
			}

			if (!empty($param['th_service_class'])) $th_service_class .= ' '.$param['th_service_class'];
			if (!empty($param['th_price_class'])) $th_price_class .= ' '.$param['th_price_class'];

			$th_service_text = 'Услуги';
			$th_price_text = 'Стоимость';

			if (!empty($param['th_service_text'])) $th_service_text = $param['th_service_text'];
			if (!empty($param['th_price_text'])) $th_price_text = $param['th_price_text'];

			echo '<thead><tr><th align="left" class="'.$th_service_class.'">'.$th_service_text.'</th>
				<th align="right" class="'.$th_price_class.'">'.$th_price_text.'</th></tr></thead>';
		}

		foreach ($services as $service) {
			echo '<tr>
				<td align="left" width="75%" class="'.$td_service_class.'">' . $service->post_title . '</td>
				<td align="right" width="25%" class="'.$td_price_class.'">' . get_post_meta($service->ID, 'price', true). '</td>
				</tr>';
		}
		echo '</table>';

		return;
	}
}

$wt_service_manager = new WtServiceManager();

?>