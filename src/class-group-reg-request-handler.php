<?php

require_once dirname( dirname( __DIR__ ) ) . '/wp-amo-madex/amo.php';

class Group_Reg_Request_Handler {
	/**
	 * Данные формы
	 * @var array<string, mixed>
	 */
	private $data = array();

	/**
	 * Инициализирует обработчик групповой регистрации пользователей
	 * @return void
	 */
	public function init() {
		add_action( 'wp_ajax_group_reg_user', array( $this, 'handler' ) );
	}

	/**
	 * Обрабатывает запрос групповой регистрации пользователей
	 * @return void
	 */
	public function handler() {
		if ( ! check_ajax_referer( 'group_reg_user', false, false ) ) {
			wp_send_json_error( 'Некоректный запрос', 403 );
			return;
		}

		$this->data = json_decode( str_replace( '\\', '', $_POST['data'] ), true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log(
				'Не удалось обработать запрос, данные: ' . print_r( $_POST['data'] )
				. PHP_EOL . 'Ошибка: ' . json_last_error_msg()
			);

			wp_send_json_error( 'В запросе переданы некорректные данные');
			return;
		}

		$errors = $this->check_common_data();
		if ( ! empty( $errors ) ) {
			wp_send_json_error( 'Некорректно заполнены поля: ' . PHP_EOL . join( PHP_EOL, $errors ) );
			return;
		}

		if ( ! isset( $this->data['users'] ) || ! is_array( $this->data['users'] ) ) {
			wp_send_json_error( 'Не получены данные по регистрируемым пользователям' );
			return;
		}

		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );

		$errors    = array();
		$user_data = array();

		foreach ( $this->data['users'] as $user ) {
			if ( ! is_array( $user ) ) {
				wp_send_json_error( 'Не получены данные для одного из пользователей' );
				$wpdb->query( 'ROLLBACK' );
				return;
			}

			$output_user_data = array();
			$error            = $this->reg_user( $user, $output_user_data );
			if ( true !== $error ) {
				$errors[] = $error;
			} else {
				$user_data[] = $output_user_data;
			}
		}

		error_log("Завершение регистрации пользователей");

		if ( empty( $errors ) ) {
			$wpdb->query( 'COMMIT' );
			wp_send_json_success();
		} else {
			$wpdb->query( 'ROLLBACK' );
			wp_send_json_error( 'Не удалось зарегистрировать пользователей: ' . PHP_EOL . join( PHP_EOL, $errors ) );
		}
	}

	/**
	 * Генерирует легко запоминающийся пароль
	 * @param int $symbol_count - количество символов в пароле
	 * @return string пароль
	 * @todo Не является криптографически безопасной, т.к. используются псевдослучайные числовые значения
	 */
	public function generate_password( $symbol_count ) {
		$output = '';
		$c1     = 0;
		$c2     = 0;
		$c3     = 0;
		$sum    = 0;
		$nchar  = 0;
		$ranno  = 0;
		$pik    = 0;

		$alphabet = 'abcdefghijklmnopqrstuvwxyz';

		$trigramm = include 'trigramm.php';

		// Pick a random starting point.
		$pik   = wp_rand( 0, getrandmax() ) / mt_getrandmax(); // random float number[0,1]
		$ranno = $pik * 125729.0;
		$sum   = 0;
		for ( $c1 = 0; $c1 < 26; ++$c1 ) {
			for ( $c2 = 0; $c2 < 26; ++$c2 ) {
				for ( $c3 = 0; $c3 < 26; ++$c3 ) {
					$sum += $trigramm[ $c1 ][ $c2 ][ $c3 ];
					if ( $sum > $ranno ) {
						$output .= $alphabet[ $c1 ];
						$output .= $alphabet[ $c2 ];
						$output .= $alphabet[ $c3 ];
						$c1      = 26; // Found start. Break all 3 loops.
						$c2      = 26;
						$c3      = 26;
					} // if sum
				} // for c3
			} // for c2
		} // for c1
		// Now do a random walk.
		$nchar = 3;
		while ( $nchar < $symbol_count ) {
			$c1  = strpos( $alphabet, $output[ $nchar - 2 ] );
			$c2  = strpos( $alphabet, $output[ $nchar - 1 ] );
			$sum = 0;
			for ( $c3 = 0; $c3 < 26; ++$c3 ) {
				$sum += $trigramm[ $c1 ][ $c2 ][ $c3 ];
			}
			if ( 0 === $sum ) {
				break;  // exit while loop
			}

			$pik   = wp_rand( 0, getrandmax() ) / mt_getrandmax();
			$ranno = $pik * $sum;
			$sum   = 0;
			for ( $c3 = 0; $c3 < 26; ++$c3 ) {
				$sum += $trigramm[ $c1 ][ $c2 ][ $c3 ];
				if ( $sum > $ranno ) {
					$output .= $alphabet[ $c3 ];
					$c3      = 26; // break for loop
				} // if sum
			} // for c3
			++$nchar;
		} // while nchar

		return $output;
	}


	/**
	 * Undocumented function
	 * @param array<string, string> $user_data
	 * @return true|string
	 */
	private function reg_user( $user, &$output_user_data ) {
		$firstname = isset( $user['firstname'] ) ? sanitize_user( $user['firstname'] ) : '';
		$lastname  = isset( $user['lastname'] ) ? sanitize_user( $user['lastname'] ) : '';

		if ( empty( $firstname ) || empty( $lastname ) ) {
			return "Не удалось зарегистрировать пользователя не введено имя или фамилия: $firstname $lastname";
		}

		$login          = $firstname . mb_substr( $lastname, 0, 1 );
		$translit_login = transliterator_transliterate( 'Any-Latin; Latin-ASCII; Lower()', $login );

		$result    = null;
		$iterator  = 0;
		$user_data = array(
			'first_name' => $firstname,
			'last_name'  => $lastname,
			'user_pass'  => $this->generate_password( 6 ),
		);

		while ( null === $result || is_wp_error( $result ) ) {
			if ( $iterator > 0 ) {
				$user_data['user_login'] = "$login-$iterator";
				$user_data['user_email'] = "$translit_login-$iterator@" . $_SERVER['HTTP_HOST'];
			} else {
				$user_data['user_login'] = $login;
				$user_data['user_email'] = "$translit_login@" . $_SERVER['HTTP_HOST'];
			}

			$result = wp_insert_user( $user_data );

			if ( is_wp_error( $result ) && $result->get_error_code() !== 'existing_user_login' ) {
				error_log(print_r($user_data, true));
				return "Не удалось зарегистрировать пользователя $lastname $firstname - {$result->get_error_message()}";
			}

			++$iterator;
		}

		$user_id          = $result;
		$output_user_data = $user_data;

		$this->add_user_meta( $user_id, $user );

		return true;
	}

	private function add_user_meta( $user_id, $user_data ) {
		$post = isset( $user_data['post'] ) ? sanitize_text_field( $user_data['post'] ) : '';
		$meta = array(
			'corpmail' => $this->data['corp-email'],
			'phone'    => $this->data['phone'],
			'company'  => $this->data['org-name'],
			'town'     => $this->data['sity'],
			'dolznost' => $post,
		);

		foreach ( $meta as $key => $value ) {
			if ( ! update_user_meta( $user_id, $key, $value ) ) {
				return false;
			}
		}

		return true;
	}

	private function add_user_amo_crm( $user_id, $login, $firstname, $lastname, $meta ) {
		$company_id = $this->data['org-id'];
		if ( empty( $company_id ) ) {
			return true;
		}

		$amo = new Amo();
		$res = $amo->login( AMO_SITE, AMO_LOGIN, AMO_HASH );
		if ( 1 != $res->response->auth ) {
			return 'Не удалось авторизоваться в AmoCrm, это ошибка плагина wp-amo-madex';
		}

		$res = $amo->get_company_by_id( $company_id );
		if ( ! $res ) {
			return "Не удалось найти компанию по id $company_id, проверьте правильность ввода ID компании в AmoCrm";
		}

		$company             = json_decode( $res, true );
		$name                = "$firstname $lastname";
		$responsible_user_id = $company['response']['contacts']['0']['responsible_user_id'];

		// Mдя, знаю что не красиво, но это копипаста, желания разбираться ещё и с этой апихой никакого нет
		// по хорошему конечно вообще нужно смонстрячить нормальное API к Amo и юзать его вместе с amocrm
		$custom_fields = array(
			'0' => array(
				'id'     => 129616,
				'values' => array(
					'0' => array(
						'value' => $meta['dolznost'],
					),
				),
			),
			'1' => array(
				'id'     => 129664,
				'values' => array(
					'0' => array(
						'value' => $login,
					),
				),
			),
			'2' => array(
				'id'     => 129618,
				'values' => array(
					'0' => array(
						'value' => $meta['phone'],
						'enum'  => 303992,
					),
				),
			),
			'3' => array(
				'id'     => 129620,
				'values' => array(
					'0' => array(
						'value' => $this->data['corp-email'],
						'enum'  => 304004,
					),
				),
			),
		);

		$amo->create_lead( $name, array(), $custom_fields, $responsible_user_id, $company_id );
	}

	private function send_mail_notify( $user_data ) {

	}

	/**
	 * Проверят общие данные для пользователей
	 * @return string[] - массив с описание ошибок
	 */
	private function check_common_data() {
		$errors = array();

		$this->check_field( 'org-name', 'название организации', $errors, false );
		$this->check_field( 'org-id', 'ID организации в AmoCrm', $errors, false );
		$this->check_field( 'corp-email', 'корпоративный email', $errors );
		$this->check_field( 'phone', 'телефон', $errors );
		$this->check_field( 'sity', 'город', $errors );

		return $errors;
	}

	/**
	 * Проверяет заполнение обязательных полей
	 * @param string $field_name - наименование поля
	 * @param string $user_field_name - пользовательское наименование поля
	 * @param string[] $errors - массив ошибок возниших при проверке
	 * @return void
	 */
	private function check_field( $field_name, $user_field_name, &$errors, $required = true ) {
		$this->data[ $field_name ] = sanitize_text_field(
			isset( $this->data[ $field_name ] ) ? $this->data[ $field_name ] : ''
		);

		if ( $required && empty( $this->data[ $field_name ] ) ) {
			$errors[] = "Передано пустое(й) $user_field_name";
		}
	}
}
