<?php

/**
 * Class WtServiceWidget
 *
 * @since 1.0.0
 *
 * @see WtServiceWidget
 */
class WtServiceWidget extends WP_Widget {

    private $template = '_wt_service_item.php';
    private $service_amount_default = 5;

	/**
     * Создаем новый экземляр виджета "Услуги"
     */
    function __construct() {
        $widget_ops = array(
            'description' => 'Отображение услуг.'
        );

        parent::__construct( 'wt_service_widget', 'Услуги',  $widget_ops);
    }

	/**
     * Фронтэнд виджета
     *
     * @since 1.0.0
     * @access public
     *
     * @param array $args     Аргументы для отображения 'before_title', 'after_title',
     *                        'before_widget', и 'after_widget'.
     * @param array $instance Настройки для виджета.
     */
    public function widget( $args, $instance ) {
        $service_category = apply_filters( 'widget_title', $instance['service_category'] ); // к заголовку применяем фильтр (необязательно)
        $service_amount = $instance['service_amount'];

        $theme_root = get_theme_root().'/'.get_template();

        if (file_exists($theme_root.'/_wt_service_item.php'))
            $this->template = $theme_root.'/_wt_service_item.php';

        // Определяем наличие тегов публикации для отображения
        if (!empty($instance['post_tag']) && !has_tag($instance['post_tag'])) return null;

        $services = get_posts(
            array(
                'service_category' => $service_category,
                'post_type' => 'service',
                'numberposts' => $service_amount,
                'orderby' => 'menu_order',
                'order' => 'ASC',
            )
        );

        foreach ($services as $service) {
            $service_title = $service->post_title;
            $service_content = $service->post_content;
            $service_price = get_post_meta($service->ID, 'price', true);

            echo $args['before_widget'];
            include $this->template;
            echo $args['after_widget'];
        }

    }

	/**
     * Бэкэнд виджета
     *
     * @param array $instance Текущие настройки.
     */
    public function form( $instance ) {
        if (isset( $instance['service_category'])) {
            $service_category = $instance['service_category'];
        }else $service_category = '';

        if (isset($instance[ 'service_amount' ] ) ) {
            $service_amount = $instance['service_amount'];
        }else $service_amount = $this->service_amount_default;

        if (isset( $instance['post_tag'])) {
            $post_tag = $instance['post_tag'];
        }else $post_tag = '';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('service_category'); ?>">Категории:</label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id('service_category'); ?>"
                   name="<?php echo $this->get_field_name('service_category'); ?>"
                   type="text"
                   value="<?php echo esc_attr($service_category); ?>"
                />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('service_amount'); ?>">Количество:</label>
            <input id="<?php echo $this->get_field_id('service_amount'); ?>"
                   name="<?php echo $this->get_field_name('service_amount'); ?>"
                   type="text"
                   value="<?php echo esc_attr( $service_amount ); ?>"
                   size="3"
                />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('post_tag'); ?>">Отображение по тегам поста:</label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id('post_tag'); ?>"
                   name="<?php echo $this->get_field_name('post_tag'); ?>"
                   type="text"
                   value="<?php echo esc_attr($post_tag); ?>"
                />
        </p>
        <?php
    }

	/**
     * Сохранение настроек виджета
     *
     * @param array $new_instance Новые настройки для данного параметра пришедшие из WP_Widget::form().
     * @param array $old_instance Старые настройки для данного экземпляра.
     *
     * @return array Settings to save or bool false to cancel saving.
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['service_category'] = (!empty($new_instance['service_category'])) ? strip_tags($new_instance['service_category']) : '';
        $instance['service_amount'] = (is_numeric($new_instance['service_amount'])) ? $new_instance['service_amount'] : $this->service_amount_default; // по умолчанию выводятся 5 постов
        $instance['post_tag'] = (!empty($new_instance['post_tag'])) ? strip_tags($new_instance['post_tag']) : '';

        return $instance;
    }
}