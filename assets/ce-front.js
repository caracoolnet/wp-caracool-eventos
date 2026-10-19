/* Caracool Eventos · filtro del listado por categoría. Sin dependencias. */
(function () {
	function iniciar(raiz) {
		if (raiz.dataset.ceListo) { return; }
		raiz.dataset.ceListo = '1';
		var botones = raiz.querySelectorAll('.ce-filtro-btn');
		var eventos = raiz.querySelectorAll('.ce-evento');
		var vacio = raiz.querySelector('.ce-vacio');
		if (!botones.length) { return; }
		botones.forEach(function (b) {
			b.addEventListener('click', function () {
				var cat = b.getAttribute('data-ce-cat');
				var visibles = 0;
				botones.forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
				eventos.forEach(function (e) {
					var cats = ' ' + (e.getAttribute('data-ce-cats') || '') + ' ';
					var ver = !cat || cats.indexOf(' ' + cat + ' ') !== -1;
					e.hidden = !ver;
					if (ver) { visibles++; }
				});
				if (vacio) { vacio.hidden = visibles > 0; }
			});
		});
	}
	function todos() { document.querySelectorAll('[data-ce-listado]').forEach(iniciar); }
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', todos); } else { todos(); }
	// En el editor de Elementor el widget se pinta después de cargar la página.
	window.addEventListener('elementor/frontend/init', function () {
		if (window.elementorFrontend && window.elementorFrontend.hooks) {
			window.elementorFrontend.hooks.addAction('frontend/element_ready/caracool_eventos.default', function ($el) {
				var el = $el && $el[0] ? $el[0].querySelector('[data-ce-listado]') : null;
				if (el) { iniciar(el); }
			});
		}
	});
})();
