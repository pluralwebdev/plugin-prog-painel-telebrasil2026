/**
 * Programação de Eventos — Frontend JS
 * Handles day switching, time nav building, scroll tracking
 */
(function () {
	'use strict';

	var root = document.querySelector('.pt-programacao');
	if (!root) return;

	var currentDay = null;
	var wrapper = root.querySelector('#ptTimeNavWrapper');
	var nav = root.querySelector('#ptTimeNav');
	if (!wrapper || !nav) return;

	var dayContents = root.querySelectorAll('.pt-day-content');
	if (!dayContents.length) return;

	currentDay = dayContents[0].id;
	var isScrolling = false;

	/**
	 * Build time nav pills for active day
	 */
	function buildTimeNav(dayId) {
		var activeBadge = root.querySelector('.pt-day-badge[data-day="' + dayId + '"]');
		var dayLabel = activeBadge ? activeBadge.getAttribute('data-label') : '';
		var cards = root.querySelectorAll('#' + dayId + ' .pt-sessao-card');
		var seen = {};
		var html = '<span class="pt-time-nav-day-label">' + dayLabel + '</span>';

		cards.forEach(function (card) {
			var hora = card.getAttribute('data-hora');
			if (!hora || seen[hora]) return;
			seen[hora] = true;
			var especial = card.getAttribute('data-especial');
			var cls = 'pt-time-pill' + (especial ? ' pt-time-pill-especial' : '');
			html += '<button class="' + cls + '" data-hora="' + hora + '">' + hora + '</button>';
		});

		nav.innerHTML = html;

		nav.querySelectorAll('.pt-time-pill').forEach(function (pill) {
			pill.addEventListener('click', function () {
				scrollToSession(this.getAttribute('data-hora'));
			});
		});
	}

	/**
	 * Switch day
	 */
	function switchDay(dayId) {
		dayContents.forEach(function (el) { el.classList.remove('active'); });
		root.querySelectorAll('.pt-day-badge').forEach(function (el) { el.classList.remove('active'); });

		var target = document.getElementById(dayId);
		if (target) target.classList.add('active');

		root.querySelectorAll('.pt-day-badge[data-day="' + dayId + '"]').forEach(function (b) {
			b.classList.add('active');
		});

		currentDay = dayId;
		buildTimeNav(dayId);
	}

	/**
	 * Scroll to session by hora
	 */
	function scrollToSession(hora) {
		isScrolling = true;

		nav.querySelectorAll('.pt-time-pill').forEach(function (p) {
			p.classList.toggle('active', p.getAttribute('data-hora') === hora);
		});

		var cards = root.querySelectorAll('#' + currentDay + ' .pt-sessao-card');
		cards.forEach(function (card) {
			if (card.getAttribute('data-hora') === hora) {
				card.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		});

		setTimeout(function () { isScrolling = false; }, 800);
	}

	/**
	 * Highlight active pill on scroll (no scrollIntoView to avoid loop)
	 */
	function updateActivePill() {
		if (isScrolling) return;

		var cards = root.querySelectorAll('#' + currentDay + ' .pt-sessao-card');
		var pills = nav.querySelectorAll('.pt-time-pill');
		var found = -1;

		cards.forEach(function (card, i) {
			var rect = card.getBoundingClientRect();
			if (rect.top <= 120) { found = i; }
		});

		pills.forEach(function (p, i) {
			p.classList.toggle('active', i === found);
		});

		// Horizontal-only scroll of the nav for the active pill
		if (found >= 0 && pills[found]) {
			var pill = pills[found];
			var navRect = nav.getBoundingClientRect();
			var pillRect = pill.getBoundingClientRect();
			if (pillRect.left < navRect.left || pillRect.right > navRect.right) {
				nav.scrollLeft += (pillRect.left - navRect.left) - (navRect.width / 2) + (pillRect.width / 2);
			}
		}
	}

	// Throttled scroll listener
	var scrollTick = false;
	window.addEventListener('scroll', function () {
		if (wrapper) {
			wrapper.classList.toggle('scrolled', window.scrollY > 100);
		}
		if (!scrollTick) {
			scrollTick = true;
			requestAnimationFrame(function () {
				updateActivePill();
				scrollTick = false;
			});
		}
	});

	// Bind all day badges (top and bottom)
	root.querySelectorAll('.pt-day-badge').forEach(function (badge) {
		badge.addEventListener('click', function () {
			var dayId = this.getAttribute('data-day');
			if (dayId) {
				switchDay(dayId);
				// Scroll to the programacao section, not page top
				root.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		});
	});

	// Init
	buildTimeNav(currentDay);

})();
