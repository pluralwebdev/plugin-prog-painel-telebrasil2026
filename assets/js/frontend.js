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
	var menuHeight = 0;

	/**
	 * Detect main site menu height and set CSS variable.
	 * Uses manual value from ptEventConfig.menuHeight if > 0.
	 */
	function detectMenuHeight() {
		// Use manually configured value if provided
		if (typeof ptEventConfig !== 'undefined' && parseInt(ptEventConfig.menuHeight, 10) > 0) {
			menuHeight = parseInt(ptEventConfig.menuHeight, 10);
			root.style.setProperty('--pt-menu-height', menuHeight + 'px');
			return;
		}

		// Try common selectors for the site's sticky/fixed header
		var header = document.querySelector(
			'header.elementor-sticky, .elementor-sticky--active, ' +
			'#masthead, .site-header, header#header, ' +
			'nav.navbar, .elementor-location-header, ' +
			'[data-elementor-type="header"]'
		);
		if (header) {
			menuHeight = header.offsetHeight || 80;
		} else {
			// Fallback: check for any fixed/sticky element at top
			var els = document.querySelectorAll('header, nav');
			for (var i = 0; i < els.length; i++) {
				var style = window.getComputedStyle(els[i]);
				if (style.position === 'fixed' || style.position === 'sticky') {
					menuHeight = els[i].offsetHeight || 80;
					break;
				}
			}
			if (!menuHeight) menuHeight = 80;
		}
		root.style.setProperty('--pt-menu-height', menuHeight + 'px');
	}

	/**
	 * Update scroll-fade classes on wrapper based on nav scroll position
	 */
	function updateScrollFade() {
		var scrollLeft = nav.scrollLeft;
		var maxScroll = nav.scrollWidth - nav.clientWidth;

		wrapper.classList.toggle('scroll-start', scrollLeft <= 2);
		wrapper.classList.toggle('scroll-end', scrollLeft >= maxScroll - 2);
	}

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

		// Reset horizontal scroll and update fade
		nav.scrollLeft = 0;
		updateScrollFade();
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
	 * Scroll to session by hora, accounting for menu + nav bar height
	 */
	function scrollToSession(hora) {
		isScrolling = true;

		nav.querySelectorAll('.pt-time-pill').forEach(function (p) {
			p.classList.toggle('active', p.getAttribute('data-hora') === hora);
		});

		var cards = root.querySelectorAll('#' + currentDay + ' .pt-sessao-card');
		var offset = menuHeight + wrapper.offsetHeight + 16;

		cards.forEach(function (card) {
			if (card.getAttribute('data-hora') === hora) {
				var top = card.getBoundingClientRect().top + window.pageYOffset - offset;
				window.scrollTo({ top: top, behavior: 'smooth' });
			}
		});

		// Scroll the pill into horizontal view
		var activePill = nav.querySelector('.pt-time-pill[data-hora="' + hora + '"]');
		if (activePill) {
			var navRect = nav.getBoundingClientRect();
			var pillRect = activePill.getBoundingClientRect();
			var center = (pillRect.left - navRect.left) - (navRect.width / 2) + (pillRect.width / 2);
			nav.scrollBy({ left: center, behavior: 'smooth' });
		}

		setTimeout(function () { isScrolling = false; }, 800);
	}

	/**
	 * Highlight active pill on scroll (no scrollIntoView to avoid loop)
	 */
	function updateActivePill() {
		if (isScrolling) return;

		var threshold = menuHeight + wrapper.offsetHeight + 40;
		var cards = root.querySelectorAll('#' + currentDay + ' .pt-sessao-card');
		var pills = nav.querySelectorAll('.pt-time-pill');
		var found = -1;

		cards.forEach(function (card, i) {
			var rect = card.getBoundingClientRect();
			if (rect.top <= threshold) { found = i; }
		});

		pills.forEach(function (p, i) {
			p.classList.toggle('active', i === found);
		});

		// Horizontal-only scroll of the nav for the active pill
		if (found >= 0 && pills[found]) {
			var pill = pills[found];
			var navRect = nav.getBoundingClientRect();
			var pillRect = pill.getBoundingClientRect();
			if (pillRect.left < navRect.left + 32 || pillRect.right > navRect.right - 32) {
				var center = (pillRect.left - navRect.left) - (navRect.width / 2) + (pillRect.width / 2);
				nav.scrollBy({ left: center, behavior: 'smooth' });
			}
		}
	}

	// Throttled page scroll listener
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

	// Horizontal scroll listener on nav for fade indicators
	nav.addEventListener('scroll', function () {
		requestAnimationFrame(updateScrollFade);
	});

	// Bind all day badges (top and bottom)
	root.querySelectorAll('.pt-day-badge').forEach(function (badge) {
		badge.addEventListener('click', function () {
			var dayId = this.getAttribute('data-day');
			if (dayId) {
				switchDay(dayId);
				var top = root.getBoundingClientRect().top + window.pageYOffset - menuHeight;
				window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
			}
		});
	});

	// Init
	detectMenuHeight();
	buildTimeNav(currentDay);
	// Initial fade state
	wrapper.classList.add('scroll-start');

	// Re-detect menu height on resize
	window.addEventListener('resize', function () {
		detectMenuHeight();
	});

})();

/**
 * Home Carousel — Responsive re-pagination + bullet navigation
 * Mobile ≤480px  → 2 cols × 2 rows = 4 per slide
 * Tablet ≤768px  → 3 cols × 2 rows = 6 per slide
 * Desktop        → data-cols × 2 rows (default 5×2 = 10)
 */
(function () {
	'use strict';

	var carousel = document.querySelector('.pt-home-carousel');
	if (!carousel) return;

	var slidesContainer = carousel.querySelector('#ptHomeSlides');
	var bulletsContainer = carousel.querySelector('#ptHomeBullets');
	if (!slidesContainer) return;

	var desktopCols = parseInt(carousel.getAttribute('data-cols'), 10) || 5;
	var rows = 2;
	var autoplay = carousel.getAttribute('data-autoplay') !== '0';
	var speed = parseInt(carousel.getAttribute('data-speed'), 10) || 6;

	// Collect all cards once (flatten from server-rendered slides)
	var allCards = [];
	var serverSlides = slidesContainer.querySelectorAll('.pt-home-slide');
	serverSlides.forEach(function (slide) {
		var cards = slide.querySelectorAll('.pt-card-participante');
		cards.forEach(function (card) {
			allCards.push(card.cloneNode(true));
		});
	});

	if (allCards.length === 0) return;

	var current = 0;
	var totalSlides = 0;
	var autoTimer = null;
	var lastBreakpoint = null;

	function getBreakpoint() {
		var w = window.innerWidth;
		if (w <= 480) return 'mobile';
		if (w <= 768) return 'tablet';
		return 'desktop';
	}

	function getColsForBreakpoint(bp) {
		if (bp === 'mobile') return 2;
		if (bp === 'tablet') return 3;
		return desktopCols;
	}

	function buildSlides() {
		var bp = getBreakpoint();
		if (bp === lastBreakpoint) return;
		lastBreakpoint = bp;

		stopAuto();
		current = 0;

		var cols = getColsForBreakpoint(bp);
		var perPage = cols * rows;

		// Clear
		slidesContainer.innerHTML = '';
		slidesContainer.style.transform = 'translateX(0)';

		// Chunk cards into pages
		var pages = [];
		for (var i = 0; i < allCards.length; i += perPage) {
			pages.push(allCards.slice(i, i + perPage));
		}
		totalSlides = pages.length;

		pages.forEach(function (page) {
			var slide = document.createElement('div');
			slide.className = 'pt-home-slide';
			var grid = document.createElement('div');
			grid.className = 'pt-home-grid';
			grid.style.gridTemplateColumns = 'repeat(' + cols + ', 1fr)';
			page.forEach(function (card) {
				grid.appendChild(card.cloneNode(true));
			});
			slide.appendChild(grid);
			slidesContainer.appendChild(slide);
		});

		// Rebuild bullets
		if (bulletsContainer) {
			bulletsContainer.innerHTML = '';
			if (totalSlides > 1) {
				bulletsContainer.style.display = '';
				for (var b = 0; b < totalSlides; b++) {
					var btn = document.createElement('button');
					btn.className = 'pt-home-bullet' + (b === 0 ? ' active' : '');
					btn.setAttribute('data-slide', b);
					btn.setAttribute('aria-label', 'Slide ' + (b + 1));
					btn.addEventListener('click', (function (idx) {
						return function () {
							goTo(idx);
							startAuto();
						};
					})(b));
					bulletsContainer.appendChild(btn);
				}
			} else {
				bulletsContainer.style.display = 'none';
			}
		}

		startAuto();
	}

	function goTo(index) {
		if (totalSlides <= 1) return;
		if (index < 0) index = totalSlides - 1;
		if (index >= totalSlides) index = 0;

		var bullets = bulletsContainer ? bulletsContainer.querySelectorAll('.pt-home-bullet') : [];
		if (bullets.length > current) bullets[current].classList.remove('active');
		current = index;
		if (bullets.length > current) bullets[current].classList.add('active');

		var slideWidth = slidesContainer.parentElement.offsetWidth;
		slidesContainer.style.transform = 'translateX(-' + (current * slideWidth) + 'px)';
	}

	function startAuto() {
		stopAuto();
		if (!autoplay || totalSlides <= 1) return;
		autoTimer = setInterval(function () {
			goTo(current + 1);
		}, speed * 1000);
	}

	function stopAuto() {
		if (autoTimer) {
			clearInterval(autoTimer);
			autoTimer = null;
		}
	}

	// Touch / swipe support
	var touchStartX = 0;
	slidesContainer.addEventListener('touchstart', function (e) {
		touchStartX = e.changedTouches[0].screenX;
	}, { passive: true });

	slidesContainer.addEventListener('touchend', function (e) {
		var diff = touchStartX - e.changedTouches[0].screenX;
		if (Math.abs(diff) > 50) {
			goTo(diff > 0 ? current + 1 : current - 1);
			startAuto();
		}
	}, { passive: true });

	// Build on load and rebuild on resize (debounced)
	buildSlides();
	var resizeTimer;
	window.addEventListener('resize', function () {
		clearTimeout(resizeTimer);
		resizeTimer = setTimeout(buildSlides, 200);
	});
})();
