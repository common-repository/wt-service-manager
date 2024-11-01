<?php

class WtServiceManagerAdmin
{
	function __construct(){
		// Подключаем стили и скрипты для админки
		add_action('admin_enqueue_scripts', array($this, 'add_css_js_file_admin'));

		// Добавляем поле "Стоимость" на страницу редактирования услуги
		add_filter('post_updated_messages', array($this, 'post_type_service_add_options_box'));
		add_action('save_post', array($this, 'service_options_update'), 0);

		// Регистрируем колонки
		add_filter( 'manage_edit-service_columns', array($this, 'service_column_register')); 

		// Выводим цену в соответствующую колонку
		add_action( 'manage_service_posts_custom_column', array($this, 'price_column_display'), 10, 2 );
		add_action( 'manage_service_posts_custom_column', array($this, 'service_category_column_display'), 10, 2 );

		// Регистрируем действие Ajax. wp_ajax_nopriv_- действие для незарегистрированных пользователей
		add_action('wp_ajax_service_price_update', array($this, 'ajax_service_price_update'));

		// Добавляем статистику в блок "На виду"
		add_action('dashboard_glance_items', array($this, 'custom_glance_items'));
	}

	//внешний файл стилей в админке из папки плагина
	function add_css_js_file_admin() {
	    $purl = plugins_url() .'/wt-service-manager/';

	    wp_register_style('admin_style', $purl . 'css/admin.css');
	    wp_enqueue_style('admin_style');

		wp_register_script('admin_style', $purl . 'js/admin.js');
		wp_enqueue_script('admin_style');
	}

	/* Добавляем блоки в основную колонку на страницах постов и пост. страниц */
	function post_type_service_add_options_box() {
			add_meta_box('wt_service_options', 'Дополнительные настройки', array($this, 'service_options_html'), 'service', 'normal', 'high');
	}

	/* HTML код блока */
	function service_options_html($post) {
		echo '<p><label>Стоимость <input type="text" name="service_options[price]" value="' . 
		get_post_meta($post->ID, 'price', 1) . 
		'" style="width:50%" /></label></p>';
		echo '<input type="hidden" name="service_options_nonce" value="' . wp_create_nonce(__FILE__) . '" />';
	}

	/* Сохраняем данные, при сохранении поста */
	function service_options_update( $post_id){
		if (isset($_POST['service_options_nonce']) && !wp_verify_nonce($_POST['service_options_nonce'], __FILE__) ) return false; // проверка
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE  ) return false; // выходим если это автосохранение
		if ( !current_user_can('edit_post', $post_id) ) return false; // выходим если юзер не имеет право редактировать запись

		if( !isset($_POST['service_options']) ) return false; // выходим если данных нет

		// Все ОК! Теперь, нужно сохранить/удалить данные
		$_POST['service_options'] = array_map('trim', $_POST['service_options']); // чистим все данные от пробелов по краям
		foreach( $_POST['service_options'] as $key=>$value ){
			if( empty($value) ){
				delete_post_meta($post_id, $key); // удаляем поле если значение пустое
				continue;
			}

			update_post_meta($post_id, $key, $value); // add_post_meta() работает автоматически
		}
		return $post_id;
	}

	// Регистрируем колонки для вывода в панеле администратора. 
	function service_column_register( $columns ) {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'service_category' => 'Категории',
			'title' => 'Заголовок',
			'price' => 'Стоимость',
			'date' => 'Дата',
			);
		return $columns;
	}

	// Вывод цены в списке услуг
	function price_column_display( $column_name, $post_id ) {
		if ( 'price' != $column_name) return;
		 
		$price = get_post_meta($post_id, 'price', true);

		if (!$price){
			$price = '';
			$price_view = 'Добавить';
		}else $price_view = $price;

		echo '<a onclick="service_price_input_show('.$post_id.');" class="link update" title="Редактировать"
				id="service_'.$post_id.'_price">'.$price_view.'</a><br>';

		echo '<input id="service_'.$post_id.'_price_input" style="display:none;"
				onblur="service_price_update('.$post_id.');"
				value="'.$price.'" >';
	}

	// Вывод категории в списке услуг
	function service_category_column_display( $column_name, $post_id ) {
		if ( 'service_category' != $column_name ) return;

		$sub = get_the_terms(0, "service_category");

		if (!$sub) return;

		$html = array();
		foreach ($sub as $s ) {
			array_push($html, '<a href="edit.php?post_type=service&service_category='.$s->slug.'"">'. $s->name.'</a>');
			}
		echo implode($html, ", ");
	}

	// Редактируем цену услуги через Ajax
	function ajax_service_price_update(){
		update_post_meta($_POST['post_id'], 'price', $_POST['price_new']);
		die();
	}

	/* Добавляем количество записей всех типов записей в виджет консоли "Прямо сейчас" */
	function custom_glance_items( $items = array() ) {
		$post_types = array('service');

		foreach( $post_types as $type ) {
			if( ! post_type_exists( $type ) ) continue;
			$num_posts = wp_count_posts( $type );

			if( $num_posts ) {

				$published = intval( $num_posts->publish );
				$post_type = get_post_type_object( $type );	// Получаем объект (все данные) указанного типа поста

				$text = _n( '%s ' . $post_type->labels->singular_name, '%s ' . $post_type->labels->name, $published, 'your_textdomain' );
				$text = sprintf( $text, number_format_i18n( $published ) );

				if ( current_user_can( $post_type->cap->edit_posts ) ) {
					$items[] = sprintf( '<a class="%1$s-count" href="edit.php?post_type=%1$s">%2$s</a>', $type, $text ) . "\n";
				} else {
					$items[] = sprintf( '<span class="%1$s-count">%2$s</span>', $type, $text ) . "\n";
				}
			}
		}

		return $items;
	}
}
?>