jQuery('[name="enroll-course"]').chosen({ no_results_text: "Курс не найден: ", allow_single_deselect: true });

const regUserPattern = document.querySelector('.reg-user-pattern');

init();

function init() {
	let regUser = cloneRegUser();

	document.querySelector('.reg-users').appendChild(regUser);
	checkRemoveUser();

	document.querySelector('.user-reg-button').addEventListener('click', registerUser);
}

function registerUser() {
	let commonFieldNames = ['org-name', 'corp-email', 'phone', 'sity', 'enroll-course', 'org-id'];
	let data = serializeFormData(document, commonFieldNames);
	data['users'] = [];

	let users = document.querySelectorAll('.reg-users .reg-user');
	let userFields = ['firstname', 'lastname', 'post'];
	users.forEach(function (regUser) {
		let userData = serializeFormData(regUser, userFields);
		data.users.push(userData);
	});

	if (document.querySelector('.empty-input') === null) {
		let request = new XMLHttpRequest();
		request.open('POST', document.querySelector('input[name="handler_url"]').value, true);

		request.onreadystatechange = function() {
			if (request.readyState == 4) {
				if (request.status == 200) {
					let data = JSON.parse(request.responseText);
					if (data.success) {
						alert("Пользователи зарегистрированы");
					}
					else {
						alert(data.data);
					}
					// location.reload();
				}
				else {
					let data = JSON.parse(request.responseText);
					alert('Не удалось выполнить запрос.' + ('\n' + data.data || ''));
				}
			}
		}

		let formData = new FormData();
		formData.append('_wpnonce', document.querySelector('input[name="_wpnonce"]').value);
		formData.append('action', 'group_reg_user');
		formData.append('data', JSON.stringify(data));

		request.send(formData);
	}
	else {
		alert('Некоторые поля обязательны для заполнения, но не были заполнены. Пожалуйста заполните поля выделенные красной обводкой');
	}
}


function appendUser(event) {
	let regUser = findParent(event.currentTarget, 'reg-user');
	if (null === regUser) {
		return;
	}

	regUser.after(cloneRegUser());

	checkRemoveUser();
}

function removeUser(event) {
	let regUser = findParent(event.currentTarget, 'reg-user');

	if (null !== regUser) {
		regUser.remove();
		checkRemoveUser();
	}

}

function checkRemoveUser() {
	let regUsers = document.querySelectorAll('.reg-users .reg-user');
	let fistRemoveUserBtn = document.querySelector('.reg-user_control_remove');

	if (regUsers.length > 1) {
		fistRemoveUserBtn.removeAttribute('disabled');
	}
	else {
		fistRemoveUserBtn.setAttribute('disabled', true);
	}
}

function initControlButton(regUser) {
	regUser.querySelector('.reg-user_control_append').addEventListener('click', appendUser);
	regUser.querySelector('.reg-user_control_remove').addEventListener('click', removeUser);
}

function cloneRegUser() {
	let clone = regUserPattern.cloneNode(true);

	clone.classList.remove('reg-user-pattern');
	clone.removeAttribute('style');
	initControlButton(clone);

	return clone;
}

function findParent(element, parentClassName) {
	while (element !== null) {
		if (element.classList.contains(parentClassName)) {
			break;
		}
		else {
			element = element.parentElement;
		}
	}

	return element;
}

function serializeFormData(formElement, fields) {
	let data = {};

	fields.forEach((fieldName) => {
		let input = formElement.querySelector(`*[name="${fieldName}"]`);
		if (input === null) {
			console.debug(`Нет поля ${fieldName}`);
			return;
		}

		if (input.classList.contains('required-input')) {
			if (input.value.length === 0) {
				input.classList.add('empty-input');
			}
			else {
				input.classList.remove('empty-input');
			}
		}

		data[fieldName] = input.value;
	});

	return data;
}
