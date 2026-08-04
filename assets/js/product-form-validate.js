(function ($) {
	'use strict';

	if (!$ || !$.fn.validate) {
		return;
	}

	if (!$.validator.methods.lettersOnly) {
		$.validator.addMethod('lettersOnly', function (value, element) {
			return this.optional(element) || /^[A-Za-z]+(?:\s+[A-Za-z]+)*$/.test($.trim(value));
		}, 'Name must contain only letters and spaces.');
	}

	if (!$.validator.methods.phoneNumber) {
		$.validator.addMethod('phoneNumber', function (value, element) {
			return this.optional(element) || /^\+?[0-9]+$/.test($.trim(value));
		}, 'Phone number must contain only digits (optional + for country code).');
	}

	function getProductMailUrl() {
		// Live Server (e.g. :5500) cannot run PHP — use Laragon vhost instead
		if (window.location.port === '5500' || window.location.port === '5501') {
			return 'http://risagrobotics.local/send_product_mail.php';
		}

		var el = document.querySelector('script[src*="product-form-validate.js"]');
		if (el && el.src) {
			try {
				// Script lives in /assets/js/ — go up two levels to site root
				return new URL('../../send_product_mail.php', el.src).href;
			} catch (e) {
				/* fall through */
			}
		}
		return 'send_product_mail.php';
	}

	function sanitizeName(value) {
		return value.replace(/[^A-Za-z\s]/g, '').replace(/\s+/g, ' ');
	}

	function sanitizePhone(value) {
		var v = value.replace(/[^0-9+]/g, '');
		if (v.indexOf('+') > 0) {
			v = v.replace(/\+/g, '');
		}
		if ((v.match(/\+/g) || []).length > 1) {
			v = (v.charAt(0) === '+' ? '+' : '') + v.replace(/\+/g, '');
		}
		return v;
	}

	function bindInputFilters($form) {
		$form.off('.productValidate');
		$form.on('input.productValidate', '[name="name"]', function () {
			var cleaned = sanitizeName(this.value);
			if (this.value !== cleaned) {
				this.value = cleaned;
			}
		});
		$form.on('input.productValidate', '[name="phone"]', function () {
			var cleaned = sanitizePhone(this.value);
			if (this.value !== cleaned) {
				this.value = cleaned;
			}
		});
	}

	function initExpressInterestForm() {
		var $form = $('#product_form');
		if (!$form.length) {
			return;
		}

		bindInputFilters($form);

		if ($form.data('validator')) {
			return;
		}

		var mailUrl = getProductMailUrl();

		$form.validate({
			ignore: [],
			rules: {
				name: {
					required: true,
					lettersOnly: true
				},
				email: {
					required: true,
					email: true
				},
				phone: {
					required: true,
					phoneNumber: true
				}
			},
			messages: {
				name: {
					required: 'Please enter your name.',
					lettersOnly: 'Name must contain only letters and spaces.'
				},
				email: {
					required: 'Please enter your email address.',
					email: 'Please enter a valid email address.'
				},
				phone: {
					required: 'Please enter your phone number.',
					phoneNumber: 'Phone number must contain only digits (optional + for country code).'
				}
			},
			errorElement: 'label',
			errorClass: 'error',
			errorPlacement: function (error, element) {
				error.addClass('product-form-error');
				element.closest('.form-group').append(error);
			},
			highlight: function (element) {
				$(element).addClass('error');
			},
			unhighlight: function (element) {
				$(element).removeClass('error');
			},
			submitHandler: function (form) {
				var $el = $(form);
				var form_btn = $el.find('button[type="submit"]');

				Swal.fire({
					title: 'Sending...',
					text: 'Please wait while we send your message.',
					allowOutsideClick: false,
					didOpen: function () {
						Swal.showLoading();
					}
				});

				form_btn.prop('disabled', true);

				$el.ajaxSubmit({
					url: mailUrl,
					dataType: 'json',
					success: function (data) {
						Swal.close();
						form_btn.prop('disabled', false);
						if (data.status === 'success') {
							form.reset();
							$('#exampleModalCenter').modal('hide');
							$el.trigger('reset');
							$el.find('label.error, label.product-form-error').remove();
							$el.find('.form-control.error').removeClass('error');
							Swal.fire({
								icon: 'success',
								title: 'Success!',
								text: data.message
							});
						} else {
							Swal.fire({
								icon: 'error',
								title: 'Oops...',
								text: data.message
							});
						}
					},
					error: function (xhr) {
						Swal.close();
						form_btn.prop('disabled', false);
						var msg = 'Something went wrong. Please try again later.';
						if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
							msg = xhr.responseJSON.message;
						} else if (window.location.port === '5500' || window.location.port === '5501') {
							msg = 'Mail could not be sent. Open this page via http://risagrobotics.local/ (Laragon) so PHP can run, then try again.';
						}
						Swal.fire({
							icon: 'error',
							title: 'Oops...',
							text: msg
						});
					}
				});
			}
		});
	}

	$(function () {
		initExpressInterestForm();
	});

	$(document).on('shown.bs.modal', '#exampleModalCenter', function () {
		initExpressInterestForm();
		var $form = $('#product_form');
		if ($form.data('validator')) {
			$form.data('validator').resetForm();
		}
	});

})(jQuery);
