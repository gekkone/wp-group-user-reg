<h2>Групповая регистрация пользователей</h2>

<div class="user-reg-container">
	<div class="user-reg-base-info">
		<label for="user-reg-input-org-name">Название организации:</label>
		<input id="user-reg-input-org-name" name="org-name" type="text">
		<label for="user-reg-input-org-id">ID организации в AmoCrm:</label>
		<input id="user-reg-input-org-id" name="org-id" type="text">

		<label for="user-reg-input-corp-email">Корпоративный Email:</label>
		<input id="user-reg-input-corp-email" class="required-input" name="corp-email" type="text">

		<label for="user-reg-input-phone">Телефон:</label>
		<input id="user-reg-input-phone" class="required-input" name="phone" type="tel">

		<label for="user-reg-input-city">Город:</label>
		<input id="user-reg-input-city" class="required-input" name="sity" type="tel">

		<label>Записаться на курс:</label>
		<select name="enroll-course" data-placeholder="Выберите курс">
			<option value=""></option>
			<?php foreach ( $courses as $course ) : ?>
				<option value="<?php echo esc_attr( $course->ID ); ?>">
					<?php echo esc_html( $course->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>

	<h3>Cписок пользователей:</h3>
	<div class="reg-users">
	</div>

	<input type="hidden" name="handler_url" value="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
	<?php wp_nonce_field( 'group_reg_user' ); ?>

	<div class="reg-user reg-user-pattern" style="display: none">
		<label>
			Имя:
			<input name="firstname" class="required-input" type="text">
		</label>
		<label>
			Фамилия:
			<input name="lastname" class="required-input" type="text">
		</label>
		<label>
			Должность:
			<input name="post" type="text">
		</label>
		<div class="reg-user_controls">
			<button class="reg-user_control_append button">+</button>
			<button class="reg-user_control_remove button">—</button>
		</div>
	</div>

	<button class="user-reg-button button">Зарегистрировать</button>
</div>

