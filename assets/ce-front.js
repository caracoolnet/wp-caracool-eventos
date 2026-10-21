/* Caracool Eventos · listado: el mes al ancho del día y el filtro por categoría. Sin dependencias. */
(function () {
	// El mes ocupa el mismo ancho que el número del día, nunca menos que un
	// número de dos cifras, para que «9 ENE» no quede diminuto. Si el número no
	// cabe en su columna (un «10–12»), se reduce hasta caber. Sin JavaScript, el
	// mes se ve igual con su tamaño del CSS.
	function ancho(el) { return el.getBoundingClientRect().width; }

	// En los diseños con caja (calendario, bloque, contorno) el mes no se toca:
	// solo se encoge el número si no cabe, como pasa con un «10–12».
	function ajustarCaja(fecha) {
		var caja = fecha.querySelector('.ce-fecha-caja');
		var dia = fecha.querySelector('.ce-dia');
		if (!caja || !dia) { return; }
		dia.style.fontSize = '';
		var cs = window.getComputedStyle(caja);
		var hueco = caja.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
		var w = dia.scrollWidth;
		if (hueco > 0 && w > hueco) {
			var td = parseFloat(window.getComputedStyle(dia).fontSize);
			dia.style.fontSize = (td * hueco / w * 0.96).toFixed(2) + 'px';
		}
	}

	function ajustarMes(fecha) {
		var raiz = fecha.closest('.ce-listado, .ce-ficha-hoja');
		if (raiz && !raiz.classList.contains('ce-f-clasico')) {
			if (!raiz.classList.contains('ce-f-linea')) { ajustarCaja(fecha); }
			return;
		}
		var dia = fecha.querySelector('.ce-dia');
		var mes = fecha.querySelector('.ce-mes');
		if (!dia || !mes) { return; }
		dia.style.fontSize = '';
		mes.style.fontSize = '';
		// Con el tamaño del mes puesto a mano en el widget, no se calcula nada.
		if (fecha.closest('.ce-mes-manual-yes')) { return; }
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

	// ── Ficha del evento ─────────────────────────────────────────────
	// Cuenta atrás con la fecha del navegador (la del servidor puede venir de
	// una caché), compartir con el menú del móvil, copiar el enlace y el mapa
	// de Google, que solo se carga cuando se pide.
	function cuenta(el) {
		var p = (el.getAttribute('data-ce-dia') || '').split('-');
		if (p.length !== 3) { return; }
		var dia = new Date(+p[0], +p[1] - 1, +p[2]);
		var hoy = new Date(); hoy.setHours(0, 0, 0, 0);
		var n = Math.round((dia - hoy) / 864e5);
		if (n < 0) { el.hidden = true; return; }
		el.textContent = n === 0 ? 'Es hoy' : (n === 1 ? 'Es mañana' : 'Faltan ' + n + ' días');
	}

	function iniciarFicha(f) {
		if (f.dataset.ceListo) { return; }
		f.dataset.ceListo = '1';
		f.querySelectorAll('.ce-cuenta').forEach(cuenta);
		f.querySelectorAll('.ce-fecha').forEach(ajustarMes);
		f.querySelectorAll('[data-ce-compartir]').forEach(function (b) {
			if (!navigator.share) { return; }
			b.hidden = false;
			b.addEventListener('click', function () {
				navigator.share({ title: b.getAttribute('data-ce-titulo') || document.title, url: b.getAttribute('data-ce-compartir') }).catch(function () {});
			});
		});
		f.querySelectorAll('[data-ce-copiar]').forEach(function (b) {
			b.addEventListener('click', function () {
				var url = b.getAttribute('data-ce-copiar');
				var hecho = function () { b.classList.add('ce-copiado'); setTimeout(function () { b.classList.remove('ce-copiado'); }, 2000); };
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(url).then(hecho, function () { window.prompt('Copia el enlace:', url); });
				} else {
					window.prompt('Copia el enlace:', url);
				}
			});
		});
		f.querySelectorAll('[data-ce-mapa]').forEach(function (m) {
			var b = m.querySelector('.ce-mapa-btn');
			if (!b) { return; }
			b.addEventListener('click', function () {
				var i = document.createElement('iframe');
				i.src = m.getAttribute('data-ce-mapa');
				i.loading = 'lazy';
				i.title = 'Mapa del lugar';
				i.referrerPolicy = 'no-referrer-when-downgrade';
				m.textContent = '';
				m.appendChild(i);
				m.classList.add('ce-mapa-abierto');
			});
		});
	}

	function todos() {
		document.querySelectorAll('[data-ce-listado]').forEach(iniciar);
		document.querySelectorAll('[data-ce-ficha]').forEach(iniciarFicha);
	}
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
			window.elementorFrontend.hooks.addAction('frontend/element_ready/caracool_evento_ficha.default', function ($el) {
				var el = $el && $el[0] ? $el[0].querySelector('[data-ce-ficha]') : null;
				if (el) { delete el.dataset.ceListo; iniciarFicha(el); }
			});
		}
	});
})();
