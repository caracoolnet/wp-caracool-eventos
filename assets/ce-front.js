/* Caracool Eventos · listado: el mes al ancho del día y el filtro por categoría. Sin dependencias. */
(function () {
	// El mes ocupa el mismo ancho que el número del día, nunca menos que un
	// número de dos cifras, para que «9 ENE» no quede diminuto. Si el número no
	// cabe en su columna (un «10–12»), se reduce hasta caber. Sin JavaScript, el
	// mes se ve igual con su tamaño del CSS.
	function ancho(el) { return el.getBoundingClientRect().width; }

	function ajustarMes(fecha) {
		var dia = fecha.querySelector('.ce-dia');
		var mes = fecha.querySelector('.ce-mes');
		if (!dia || !mes) { return; }
		dia.style.fontSize = '';
		mes.style.fontSize = '';
		var hueco = fecha.parentNode ? ancho(fecha) : 0;
		if (!ancho(dia)) { return; }
		if (hueco && ancho(dia) > hueco) {
			var td = parseFloat(window.getComputedStyle(dia).fontSize);
			dia.style.fontSize = (td * hueco / ancho(dia)).toFixed(2) + 'px';
		}
		// Ancho de «00» con la letra del día, como mínimo.
		var muestra = dia.cloneNode(false);
		muestra.textContent = '00';
		muestra.style.position = 'absolute';
		muestra.style.visibility = 'hidden';
		dia.parentNode.appendChild(muestra);
		var objetivo = Math.max(ancho(dia), ancho(muestra));
		muestra.remove();
		if (hueco) { objetivo = Math.min(objetivo, hueco); }
		var tm = parseFloat(window.getComputedStyle(mes).fontSize);
		mes.style.fontSize = (tm * objetivo / ancho(mes)).toFixed(2) + 'px';
	}

	function ajustarTodo(raiz) {
		(raiz || document).querySelectorAll('.ce-fecha').forEach(ajustarMes);
	}

	function iniciar(raiz) {
		if (raiz.dataset.ceListo) { return; }
		raiz.dataset.ceListo = '1';
		ajustarTodo(raiz);
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
				// Lo que estaba escondido no se pudo medir: se mide ahora.
				ajustarTodo(raiz);
			});
		});
	}

	function todos() { document.querySelectorAll('[data-ce-listado]').forEach(iniciar); }
	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', todos); } else { todos(); }

	// Con la tipografía definitiva cargada, y al cambiar el tamaño de la ventana o girar el móvil.
	if (document.fonts && document.fonts.ready) { document.fonts.ready.then(function () { ajustarTodo(); }); }
	window.addEventListener('load', function () { ajustarTodo(); });
	var espera;
	window.addEventListener('resize', function () {
		clearTimeout(espera);
		espera = setTimeout(function () { ajustarTodo(); }, 120);
	});

	// En el editor de Elementor el widget se pinta después de cargar la página.
	window.addEventListener('elementor/frontend/init', function () {
		if (window.elementorFrontend && window.elementorFrontend.hooks) {
			window.elementorFrontend.hooks.addAction('frontend/element_ready/caracool_eventos.default', function ($el) {
				var el = $el && $el[0] ? $el[0].querySelector('[data-ce-listado]') : null;
				if (el) { delete el.dataset.ceListo; iniciar(el); }
			});
		}
	});
})();
