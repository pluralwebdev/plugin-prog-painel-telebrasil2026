(function ($) {
	'use strict';

	var PTEventAdmin = {

		init: function () {
			this.initSearch();
			this.initSortable();
			this.initRemove();
			this.initMediaUpload();
			this.initBgImageUpload();
			this.initPatrocinadorLogoUpload();
		},

		// =====================================================================
		// Busca de participantes via AJAX
		// =====================================================================
		initSearch: function () {
			var $input = $('#pt-event-search-input');
			var $results = $('#pt-event-search-results');
			var timer = null;

			if (!$input.length) return;

			$input.on('keyup', function () {
				var term = $(this).val();
				clearTimeout(timer);

				if (term.length < 2) {
					$results.removeClass('active').empty();
					return;
				}

				timer = setTimeout(function () {
					$.ajax({
						url: ptEventAdmin.ajaxUrl,
						data: {
							action: 'pt_event_search_participantes',
							nonce: ptEventAdmin.nonce,
							term: term
						},
						success: function (response) {
							if (response.success && response.data.length) {
								var html = '';
								$.each(response.data, function (i, item) {
									html += '<div class="pt-event-search-result-item" data-id="' + item.id + '" data-nome="' + PTEventAdmin.escapeHtml(item.nome) + '">';
									html += '<div class="nome">' + PTEventAdmin.escapeHtml(item.nome) + '</div>';
									if (item.cargo) {
										html += '<div class="cargo">' + PTEventAdmin.escapeHtml(item.cargo) + '</div>';
									}
									html += '</div>';
								});
								$results.html(html).addClass('active');
							} else {
								$results.html('<div class="pt-event-search-result-item"><em>Nenhum resultado</em></div>').addClass('active');
							}
						}
					});
				}, 300);
			});

			$results.on('click', '.pt-event-search-result-item', function () {
				var id = $(this).data('id');
				var nome = $(this).data('nome');

				if (!id || !nome) return;

				// Verificar duplicata
				var exists = false;
				$('#pt-event-participantes-list tr').each(function () {
					if ($(this).data('participante-id') == id) {
						exists = true;
						return false;
					}
				});

				if (exists) {
					alert('Este participante já está adicionado.');
					return;
				}

				PTEventAdmin.addRow(id, nome);
				$input.val('');
				$results.removeClass('active').empty();
			});

			// Fechar dropdown ao clicar fora
			$(document).on('click', function (e) {
				if (!$(e.target).closest('.pt-event-search-bar').length) {
					$results.removeClass('active').empty();
				}
			});
		},

		// =====================================================================
		// Adicionar linha de participante
		// =====================================================================
		addRow: function (id, nome) {
			var $tbody = $('#pt-event-participantes-list');
			var index = $tbody.find('tr').length;

			var tmpl = wp.template('pt-event-participante-row');
			var html = tmpl({ id: id, nome: nome, index: index });

			$tbody.append(html);
			this.updateOrdens();
		},

		// =====================================================================
		// Sortable (drag & drop)
		// =====================================================================
		initSortable: function () {
			var $tbody = $('#pt-event-participantes-list');
			if (!$tbody.length) return;

			$tbody.sortable({
				handle: '.pt-event-sortable-handle',
				axis: 'y',
				placeholder: 'ui-sortable-placeholder',
				update: function () {
					PTEventAdmin.updateOrdens();
				}
			});
		},

		// =====================================================================
		// Remover participante
		// =====================================================================
		initRemove: function () {
			$(document).on('click', '.pt-event-remove-participante', function () {
				$(this).closest('tr').fadeOut(200, function () {
					$(this).remove();
					PTEventAdmin.updateOrdens();
				});
			});
		},

		// =====================================================================
		// Atualizar índices e ordens
		// =====================================================================
		updateOrdens: function () {
			$('#pt-event-participantes-list tr').each(function (i) {
				$(this).find('input, select').each(function () {
					var name = $(this).attr('name');
					if (name) {
						$(this).attr('name', name.replace(/\[\d+\]/, '[' + i + ']'));
					}
				});
				$(this).find('.pt-event-ordem').val(i);
			});
		},

		// =====================================================================
		// Media Upload (foto do participante)
		// =====================================================================
		initMediaUpload: function () {
			var frame;

			$(document).on('click', '.pt-event-upload-foto', function (e) {
				e.preventDefault();
				var $btn = $(this);

				if (frame) {
					frame.open();
					return;
				}

				frame = wp.media({
					title: 'Selecionar Foto',
					button: { text: 'Usar esta foto' },
					multiple: false,
					library: { type: 'image' }
				});

				frame.on('select', function () {
					var attachment = frame.state().get('selection').first().toJSON();
					var url = attachment.sizes && attachment.sizes.thumbnail
						? attachment.sizes.thumbnail.url
						: attachment.url;

					$btn.siblings('input[type="hidden"]').val(attachment.id);
					$btn.siblings('.pt-event-foto-preview').html('<img src="' + url + '" />');
					$btn.siblings('.pt-event-remove-foto').show();
				});

				frame.open();
			});

			$(document).on('click', '.pt-event-remove-foto', function (e) {
				e.preventDefault();
				$(this).siblings('input[type="hidden"]').val('');
				$(this).siblings('.pt-event-foto-preview').html('');
				$(this).hide();
			});
		},

		// =====================================================================
		// Media Upload (imagem de fundo do participante — Settings page)
		// =====================================================================
		initBgImageUpload: function () {
			$(document).on('click', '.pt-event-upload-bg-image', function (e) {
				e.preventDefault();
				var $btn = $(this);
				var targetId = $btn.data('target');

				var frame = wp.media({
					title: 'Selecionar Imagem de Fundo',
					button: { text: 'Usar esta imagem' },
					multiple: false,
					library: { type: 'image' }
				});

				frame.on('select', function () {
					var attachment = frame.state().get('selection').first().toJSON();
					var url = attachment.sizes && attachment.sizes.medium
						? attachment.sizes.medium.url
						: attachment.url;

					$('#' + targetId).val(attachment.id);
					$btn.closest('.pt-event-image-upload-wrapper')
						.find('.pt-event-image-preview')
						.html('<img src="' + url + '" style="max-width:200px;max-height:200px;border-radius:8px;border:1px solid #ddd;" />');
					$btn.siblings('.pt-event-remove-bg-image').show();
				});

				frame.open();
			});

			$(document).on('click', '.pt-event-remove-bg-image', function (e) {
				e.preventDefault();
				var targetId = $(this).data('target');
				$('#' + targetId).val('');
				$(this).closest('.pt-event-image-upload-wrapper')
					.find('.pt-event-image-preview').html('');
				$(this).hide();
			});
		},

		// =====================================================================
		// Media Upload (logo do patrocinador)
		// =====================================================================
		initPatrocinadorLogoUpload: function () {
			var frame;

			$(document).on('click', '.pt-event-upload-patrocinador-logo', function (e) {
				e.preventDefault();
				var $btn = $(this);

				if (frame) {
					frame.open();
					return;
				}

				frame = wp.media({
					title: 'Selecionar Logo',
					button: { text: 'Usar esta imagem' },
					multiple: false,
					library: { type: 'image' }
				});

				frame.on('select', function () {
					var attachment = frame.state().get('selection').first().toJSON();
					var url = attachment.sizes && attachment.sizes.medium
						? attachment.sizes.medium.url
						: attachment.url;

					$btn.closest('.pt-event-foto-wrapper').find('input[type="hidden"]').val(attachment.id);
					$btn.closest('.pt-event-foto-wrapper').find('.pt-event-foto-preview').html('<img src="' + url + '" style="max-width:100%;height:auto;" />');
					$btn.siblings('.pt-event-remove-patrocinador-logo').show();
				});

				frame.open();
			});

			$(document).on('click', '.pt-event-remove-patrocinador-logo', function (e) {
				e.preventDefault();
				$(this).closest('.pt-event-foto-wrapper').find('input[type="hidden"]').val('');
				$(this).closest('.pt-event-foto-wrapper').find('.pt-event-foto-preview').html('');
				$(this).hide();
			});
		},

		// =====================================================================
		// Escape HTML helper
		// =====================================================================
		escapeHtml: function (str) {
			if (!str) return '';
			var div = document.createElement('div');
			div.appendChild(document.createTextNode(str));
			return div.innerHTML;
		}
	};

	$(document).ready(function () {
		PTEventAdmin.init();
	});

})(jQuery);
