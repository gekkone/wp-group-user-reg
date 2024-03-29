<?php
/*
Plugin Name: Групповая регистрация пользователей
Plugin URI: https://github.com/gekkone/wp-group-user-reg
Description: Групповая регистрация пользователей
Зависимости: wp-amo-madex и sfwd-lms (плагины)
Version: 1.1.4
Author: Gekkone
Author URI: https://github.com/gekkone
*/

require_once __DIR__ . '/src/allow-cyrillic-username.php';
require_once __DIR__ . '/src/class-group-reg-page.php';
require_once __DIR__ . '/src/class-group-reg-request-handler.php';

$user_group_reg_sub_menu = new Group_Reg_Page();
$user_group_reg_sub_menu->init();

$user_group_reg_requst_handler = new Group_Reg_Request_Handler();
$user_group_reg_requst_handler->init();
