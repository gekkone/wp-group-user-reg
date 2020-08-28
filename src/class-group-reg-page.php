<?php

class Group_Reg_Page {


	/**
	 * Инициализирует страницу группового добавления пользователей
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_sub_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'include_assets' ) );
	}

	/**
	 * Регистрирует подменю
	 *
	 * @return void
	 */
	public function register_sub_menu() {
		add_submenu_page(
			'users.php',
			'Групповая регистрация пользователей',
			'Групповая регистрация',
			'manage_options',
			'group_reg_user',
			array( $this, 'menu_page_callback' )
		);
	}

	/**
	 * Выводит содержимое страницы
	 *
	 * @return void
	 */
	public function menu_page_callback() {
		$courses = get_pages( 'post_type=sfwd-courses' );
		include dirname( __DIR__ ) . '/assets/html/user-reg-page.php';
	}

	/**
	 * Подключает ресурсы используемые на странице регистрации пользователей
	 *
	 * @param string $hook_suffix название страницы админки
	 * @return void
	 */
	public function include_assets( $hook_suffix ) {
		if ( 'users_page_group_reg_user' === $hook_suffix ) {
			$sfwd_lms_dir_url = plugin_dir_url( dirname( dirname( __DIR__ ) ) . '/sfwd-lms/sfwd-lms.php' );

			wp_enqueue_style(
				'chosen-css',
				"$sfwd_lms_dir_url/assets/chosen.css",
				array(),
				'1.0.0'
			);

			wp_enqueue_script(
				'chosen-js',
				"$sfwd_lms_dir_url/assets/chosen.jquery.min.js",
				array( 'jquery' ),
				'1.0.0',
				false
			);

			wp_enqueue_script(
				'group-user-reg-page-script',
				plugins_url( 'assets/js/group-user-reg-page.min.js', __DIR__ ),
				array( 'chosen-js' ),
				'1.0.0',
				true
			);

			wp_enqueue_style(
				'group-user-reg-page-style',
				plugins_url( 'assets/css/group-user-reg-page.min.css', __DIR__ ),
				array(),
				'1.0.0'
			);
		}
	}
}
