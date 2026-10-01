const Reconocimiento = window.SpeechRecognition || window.webkitSpeechRecognition;

const sinAcentos = t => t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
const promedio = a => a.reduce((x, y) => x + y, 0) / (a.length || 1);
const percentil = (a, p) => {
  const o = [...a].sort((x, y) => x - y);
  return o.length ? o[Math.min(o.length - 1, Math.floor(o.length * p))] : 0;
};

/* Ánimo a partir del texto */
const lexico = {
  exactas: {
    mal: -1, malo: -1, mala: -1, malos: -1, malas: -1, peor: -2, fatal: -2, horrible: -2, nadie: -1, hambre: -1,
    vacio: -1, vacia: -1, perdido: -1, perdida: -1, odio: -2, rabia: -2, coraje: -2,
    bien: 1, mejor: 1, feliz: 2, genial: 2, excelente: 2, padre: 1, chido: 1, tranquilo: 1, tranquila: 1,
    paz: 1, calma: 1, listo: 1, lista: 1, positivo: 1, positiva: 1, fuerte: 1, gracias: 1, contento: 1
  },
  raices: {
    trist: -1, deprimid: -2, cansad: -1, agotad: -1, preocup: -1, miedo: -1, ansi: -1, estres: -1, enoj: -2,
    frustr: -1, hart: -1, desesper: -2, llor: -1, fracas: -1, inutil: -1, verguenza: -1, culpa: -1, deuda: -1,
    dificil: -1, pesad: -1, aburrid: -1, angusti: -1, nervios: -1, insomnio: -1, rechaz: -1, despid: -1,
    desanim: -1, decepcion: -1, inquiet: -1, abrum: -2, ahog: -1, presion: -1, insegur: -1, desemplead: -1,
    molest: -1, furi: -2, rencor: -2,
    content: 1, animad: 1, ganas: 1, logr: 1, avanc: 1, avanz: 1, alegr: 1, orgull: 1, agradec: 1,
    emocion: 2, esperanz: 1, motivad: 1, aprend: 1, entrevista: 1, contrat: 1, energi: 1, descans: 1,
    optimis: 1, ilusion: 1, sonri: 1, entusias: 2, satisfech: 1, relajad: 1
  },
  frases: {
    'me siento solo': -2, 'me siento sola': -2, 'no puedo mas': -2, 'estoy solo': -1, 'estoy sola': -1, 'sin dinero': -1,
    'sin trabajo': -1, 'no duermo': -1, 'no he dormido': -1, 'no puedo dormir': -1, 'dormi bien': 1, 'ya dormi': 1,
    'echale ganas': 1, 'salir adelante': 1
  },
  /* Ganas de lastimar a alguien: se reporta como enojo */
  enojo: ['pegarle', 'pegarles', 'golpear', 'golpearlo', 'golpearla', 'madrazo', 'madrazos', 'putaz', 'romperle', 'partirle', 'matarlo', 'matarla', 'ahorcar', 'vengar', 'venganza', 'desquitar']
};
const intensificadores = ['muy', 'monton', 'mucho', 'mucha', 'muchos', 'muchas', 'bastante', 'super', 'demasiado', 'tanto', 'tanta', 'tantas'];

function valorPalabra(p) {
  if (p in lexico.exactas) return lexico.exactas[p];
  for (const r in lexico.raices) if (p.startsWith(r)) return lexico.raices[r];
  return 0;
}

function animoDelTexto(texto) {
  const limpio = ' ' + sinAcentos(texto).replace(/[^a-zñ\s]/g, ' ').replace(/\s+/g, ' ') + ' ';
  let positivo = 0, negativo = 0, senales = 0;
  const sumar = v => { if (v > 0) positivo += v; else negativo -= v; senales++; };
  for (const f in lexico.frases) {
    const veces = limpio.split(' ' + f + ' ').length - 1;
    for (let k = 0; k < veces; k++) sumar(lexico.frases[f]);
  }
  const p = limpio.trim().split(' ');
  const enojo = p.some(w => lexico.enojo.some(r => w.startsWith(r)));
  if (enojo) sumar(-3);
  for (let i = 0; i < p.length; i++) {
    let v = valorPalabra(p[i]);
    if (!v) continue;
    if ((p[i] === 'bien' || p[i] === 'muy') && valorPalabra(p[i + 1] || '') < 0) continue;
    if (p[i - 1] === 'bien' && v < 0) v *= 2;
    if (intensificadores.includes(p[i - 1]) || intensificadores.includes(p[i - 2])) v *= 2;
    if ((p[i - 1] === 'no' || p[i - 2] === 'no') && v > 0) v = -1;
    sumar(v);
  }
  const puntos = positivo - negativo;
  let nivel = puntos <= -5 ? 1 : puntos <= -2 ? 2 : puntos < 2 ? 3 : puntos < 4 ? 4 : 5;
  if (enojo) nivel = Math.min(nivel, 2);
  return { nivel, senales, enojo, mezclado: positivo >= 2 && negativo >= 2 };
}

/* Tono de voz por autocorrelación */
function tono(buf, tasa) {
  const min = Math.floor(tasa / 400), max = Math.floor(tasa / 70), n = buf.length - max;
  let energia = 0;
  for (let i = 0; i < n; i++) energia += buf[i] * buf[i];
  if (energia < 1e-5) return 0;
  const r = new Float32Array(max + 1);
  let mejor = 0;
  for (let lag = min; lag <= max; lag++) {
    let s = 0, e2 = 0;
    for (let i = 0; i < n; i++) { s += buf[i] * buf[i + lag]; e2 += buf[i + lag] * buf[i + lag]; }
    r[lag] = s / Math.sqrt(energia * e2 || 1);
    if (r[lag] > mejor) mejor = r[lag];
  }
  if (mejor < 0.75) return 0;
  for (let lag = min + 1; lag < max; lag++) {
    if (r[lag] >= mejor * 0.9 && r[lag] >= r[lag - 1] && r[lag] >= r[lag + 1]) return tasa / lag;
  }
  return 0;
}

/* Ánimo por la voz: -2 muy apagada … +2 muy animada (volumen, rapidez y variación del tono) */
function animoDeVoz(cuadros, palabras) {
  const piso = Math.max(-70, percentil(cuadros.map(c => c.db), 0.05));
  const conVoz = cuadros.map((c, i) => c.db > piso + 10 ? i : -1).filter(i => i >= 0);
  if (conVoz.length < 15) return null;
  const voz = conVoz.map(i => cuadros[i]);
  const volumen = promedio(voz.map(c => c.db)) - piso;
  const tonos = voz.map(c => c.f).filter(Boolean);
  const base = percentil(tonos, 0.5) || 1;
  const semitonos = tonos.map(f => 12 * Math.log2(f / base));
  const rango = tonos.length >= 15 ? percentil(semitonos, 0.9) - percentil(semitonos, 0.1) : null;
  const segundosHablando = (conVoz[conVoz.length - 1] - conVoz[0] + 1) / 10;
  const ppm = palabras / (segundosHablando / 60);
  let puntos = 0;
  puntos += volumen < 14 ? -1 : volumen > 24 ? 1 : 0;
  puntos += ppm < 90 ? -1 : ppm > 150 ? 1 : 0;
  if (rango !== null) puntos += rango < 3 ? -1 : rango > 7 ? 1 : 0;
  console.info('Voz', { volumen, ppm, rango, puntos });
  return Math.max(-2, Math.min(2, puntos));
}

/* Dictado */
function prepararDictado(boton) {
  const destino = document.getElementById(boton.dataset.dictar);
  const etiqueta = boton.querySelector('span');
  const textoOriginal = etiqueta.textContent;
  const bloque = boton.closest('.bloque, form');
  const provisional = bloque.querySelector('.provisional');
  const barra = bloque.querySelector('.volumen i');
  const conAnimo = boton.hasAttribute('data-animo');
  let sesion = null;

  async function iniciar() {
    if (!window.isSecureContext) { etiqueta.textContent = 'El micrófono solo funciona con el enlace seguro (https)'; return; }
    if (!Reconocimiento) { etiqueta.textContent = 'Usa Chrome para dictar'; return; }
    sesion = { cuadros: [], palabras: 0, parar: false, inicio: Date.now() };

    if (conAnimo) {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      ctx.resume();
      try {
        const flujo = await navigator.mediaDevices.getUserMedia({
          audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: false }
        });
        if (ctx.state !== 'running') await ctx.resume();
        const filtro = ctx.createBiquadFilter();
        filtro.type = 'highpass';
        filtro.frequency.value = 80;
        const analizador = ctx.createAnalyser();
        analizador.fftSize = 2048;
        ctx.createMediaStreamSource(flujo).connect(filtro);
        filtro.connect(analizador);
        const buf = new Float32Array(analizador.fftSize);
        Object.assign(sesion, { ctx, flujo });
        sesion.timer = setInterval(() => {
          analizador.getFloatTimeDomainData(buf);
          let s = 0;
          for (const x of buf) s += x * x;
          const db = Math.max(-90, 20 * Math.log10(Math.sqrt(s / buf.length) || 1e-8));
          sesion.cuadros.push({ db, f: db > -55 ? tono(buf, ctx.sampleRate) : 0 });
          if (barra) barra.style.width = Math.max(0, Math.min(100, (db + 60) * 2)) + '%';
        }, 100);
      } catch (e) {
        ctx.close();
      }
    }

    const rec = new Reconocimiento();
    rec.lang = 'es-MX';
    rec.continuous = true;
    rec.interimResults = true;
    rec.onresult = ev => {
      if (!sesion) return;
      let enCurso = '';
      for (let i = ev.resultIndex; i < ev.results.length; i++) {
        const t = ev.results[i][0].transcript.trim();
        if (!ev.results[i].isFinal) { enCurso += ' ' + t; continue; }
        sesion.palabras += t.split(/\s+/).filter(Boolean).length;
        destino.value += (destino.value ? ' ' : '') + t;
      }
      if (provisional) provisional.textContent = enCurso.trim();
    };
    rec.onerror = ev => {
      const mensajes = {
        'not-allowed': 'Permite el micrófono en el candado de la barra de direcciones',
        'service-not-allowed': 'Permite el micrófono en el candado de la barra de direcciones',
        'audio-capture': 'No encontramos un micrófono conectado',
        network: 'El dictado necesita conexión a internet'
      };
      if (mensajes[ev.error] && sesion) {
        sesion.parar = true;
        etiqueta.textContent = mensajes[ev.error];
      }
    };
    rec.onend = () => {
      if (provisional) provisional.textContent = '';
      if (sesion && !sesion.parar && Date.now() - sesion.inicio < 180000) {
        try { rec.start(); return; } catch (e) {}
      }
      terminar();
    };
    rec.start();
    sesion.rec = rec;
    boton.classList.add('grabando');
    etiqueta.textContent = 'Escuchando… toca para terminar';
  }

  function terminar() {
    if (!sesion) return;
    const s = sesion;
    sesion = null;
    clearInterval(s.timer);
    if (s.flujo) s.flujo.getTracks().forEach(t => t.stop());
    if (s.ctx) s.ctx.close();
    boton.classList.remove('grabando');
    if (etiqueta.textContent.startsWith('Escuchando')) etiqueta.textContent = textoOriginal;
    if (barra) barra.style.width = '0';
    if (!conAnimo) return;

    const texto = animoDelTexto(destino.value);
    const voz = s.cuadros.length ? animoDeVoz(s.cuadros, s.palabras) : null;
    let nivel = null;
    if (texto.senales) nivel = texto.nivel + (voz !== null && Math.abs(voz) === 2 ? Math.sign(voz) : 0);
    else if (voz !== null) nivel = 3 + voz;
    if (nivel) nivel = Math.max(1, Math.min(texto.enojo ? 2 : 5, nivel));
    mostrarAnimo(bloque, nivel, texto, voz !== null);
  }

  boton.addEventListener('click', () => {
    if (sesion) { sesion.parar = true; sesion.rec.stop(); }
    else iniciar();
  });
}

/* Medidor de ánimo y confirmación */
const nombresAnimo = { 1: 'muy mal', 2: 'mal', 3: 'más o menos', 4: 'bien', 5: 'muy bien' };

function mostrarAnimo(bloque, nivel, texto, porVoz) {
  const porTexto = texto.senales > 0;
  const caja = bloque.querySelector('.confirmar-animo');
  const medidor = caja.querySelector('.medidor-animo');
  const pregunta = caja.querySelector('.pregunta-animo');
  const acciones = caja.querySelector('.acciones-animo');
  caja.querySelector('[name=animo_detectado]').value = nivel || '';
  caja.querySelector('[name=animo_confirmado]').value = '';
  marcar(medidor, nivel);

  if (nivel) {
    const fuente = porTexto && porVoz ? 'Por lo que dijiste y cómo sonó tu voz' : porTexto ? 'Por lo que dijiste' : 'Por cómo sonó tu voz';
    const extra = texto.enojo ? ' También notamos <b>enojo</b>: si sientes ganas de lastimar a alguien, aléjate un momento y respira.'
      : texto.mezclado ? ' Notamos cosas buenas y otras que te pesan.' : '';
    pregunta.innerHTML = fuente + ', parece que hoy te sientes <b>' + nombresAnimo[nivel] + '</b>.' + extra + ' ¿Es así?';
    acciones.hidden = false;
    medidor.classList.remove('elegible');
  } else {
    pregunta.textContent = 'No alcanzamos a notar cómo te sientes. Toca en el medidor cómo estás hoy.';
    acciones.hidden = true;
    medidor.classList.add('elegible');
  }
  caja.hidden = false;
}

function marcar(medidor, nivel) {
  medidor.querySelectorAll('button').forEach(b => b.classList.toggle('activo', +b.dataset.n === nivel));
}

function confirmar(caja, nivel) {
  caja.querySelector('[name=animo_confirmado]').value = nivel;
  marcar(caja.querySelector('.medidor-animo'), nivel);
  caja.querySelector('.medidor-animo').classList.remove('elegible');
  caja.querySelector('.acciones-animo').hidden = true;
  caja.querySelector('.pregunta-animo').innerHTML = 'Gracias. Anotamos que hoy te sientes <b>' + nombresAnimo[nivel] + '</b>.';
}

document.querySelectorAll('.confirmar-animo').forEach(caja => {
  caja.querySelectorAll('[data-resp]').forEach(b => b.addEventListener('click', () => {
    if (b.dataset.resp === 'si') return confirmar(caja, +caja.querySelector('[name=animo_detectado]').value);
    caja.querySelector('.acciones-animo').hidden = true;
    caja.querySelector('.pregunta-animo').textContent = 'Toca en el medidor cómo te sientes en realidad.';
    caja.querySelector('.medidor-animo').classList.add('elegible');
  }));
  caja.querySelectorAll('.medidor-animo button').forEach(b => b.addEventListener('click', () => {
    if (caja.querySelector('.medidor-animo').classList.contains('elegible')) confirmar(caja, +b.dataset.n);
  }));
});

document.querySelectorAll('textarea').forEach((campo, i) => {
  if (!campo.id) campo.id = 'campo-dictado-' + i;
  if (document.querySelector('[data-dictar="' + campo.id + '"]')) return;
  const boton = document.createElement('button');
  boton.type = 'button';
  boton.className = 'boton-voz boton-voz-chico';
  boton.dataset.dictar = campo.id;
  boton.innerHTML = '<i></i><span>Dictar</span>';
  campo.insertAdjacentElement('afterend', boton);
});
document.querySelectorAll('[data-dictar]').forEach(prepararDictado);

/* Color de la nota nueva */
const nuevaNota = document.getElementById('nueva-nota');
if (nuevaNota) {
  nuevaNota.querySelectorAll('input[name=color]').forEach(r => r.addEventListener('change', () => {
    nuevaNota.className = 'nueva-nota nota nota-' + r.value;
  }));
}

/* Diálogos: ayuda y enlaces externos */
const guardado = {
  leer(clave) { try { return JSON.parse(localStorage.getItem('retoma_' + clave)); } catch (e) { return null; } },
  escribir(clave, valor) { try { localStorage.setItem('retoma_' + clave, JSON.stringify(valor)); } catch (e) {} },
  borrar(clave) { try { localStorage.removeItem('retoma_' + clave); } catch (e) {} }
};

document.querySelectorAll('dialog.dialogo').forEach(d => {
  d.querySelectorAll('[data-cerrar]').forEach(b => b.addEventListener('click', () => d.close()));
  d.addEventListener('click', ev => { if (ev.target === d) d.close(); });
});

const dialogoAyuda = document.getElementById('confirmar-ayuda');
if (dialogoAyuda && dialogoAyuda.showModal) {
  document.querySelectorAll('.ayuda-ya').forEach(enlace => enlace.addEventListener('click', ev => {
    ev.preventDefault();
    dialogoAyuda.showModal();
  }));
}

const avisoExterno = document.getElementById('aviso-externo');
if (avisoExterno && avisoExterno.showModal) {
  const irExterno = document.getElementById('ir-externo');
  document.addEventListener('click', ev => {
    const enlace = ev.target.closest('a[href^="http"]');
    if (!enlace || enlace === irExterno || enlace.host === location.host || enlace.closest('dialog')) return;
    ev.preventDefault();
    irExterno.href = enlace.href;
    avisoExterno.showModal();
  });
  irExterno.addEventListener('click', () => avisoExterno.close());
}

/* Publicar vacante: formato de sueldo, aviso de prestaciones y confirmación */
const formVacante = document.getElementById('form-vacante');
if (formVacante) {
  const sueldo = document.getElementById('sueldo');
  const prestaciones = document.getElementById('prestaciones');
  const avisoPrestaciones = document.getElementById('sin-prestaciones');
  const dialogo = document.getElementById('confirmar-vacante');
  const minimoDiario = 315.04;
  const diasPorPeriodo = { 'al día': 1, 'a la semana': 7, 'a la quincena': 15, 'al mes': 30 };
  const formato = n => n.toLocaleString('es-MX');

  sueldo.addEventListener('input', () => {
    const n = parseInt(sueldo.value.replace(/\D/g, '').slice(0, 6), 10);
    sueldo.value = n ? formato(n) : '';
  });
  const cupos = document.getElementById('cupos');
  cupos.addEventListener('input', () => {
    const n = parseInt(cupos.value.replace(/\D/g, '').slice(0, 2), 10);
    cupos.value = n ? String(Math.min(n, 50)) : '';
  });
  prestaciones.addEventListener('change', () => { avisoPrestaciones.hidden = prestaciones.checked; });

  formVacante.addEventListener('submit', ev => {
    if (formVacante.dataset.confirmado || !dialogo.showModal) return;
    ev.preventDefault();
    const d = new FormData(formVacante);
    const monto = parseInt(String(d.get('sueldo')).replace(/\D/g, ''), 10) || 0;
    const minimo = Math.ceil(minimoDiario * (diasPorPeriodo[d.get('periodo')] || 1));
    let resumen = '<b>' + d.get('titulo').replace(/</g, '&lt;') + '</b> · $' + formato(monto) + ' ' + d.get('periodo') + ' · ' + d.get('cupos') + ' vacante(s)';
    if (monto < minimo) resumen += '<br><span class="aviso-campo">El sueldo es menor al salario mínimo ($' + formato(minimo) + ' ' + d.get('periodo') + '). Revísalo si es de tiempo completo.</span>';
    document.getElementById('resumen-vacante').innerHTML = resumen;
    document.getElementById('dialogo-sin-prestaciones').hidden = prestaciones.checked;
    dialogo.showModal();
  });
  document.getElementById('publicar-vacante').addEventListener('click', () => {
    formVacante.dataset.confirmado = '1';
    dialogo.close();
    formVacante.requestSubmit();
  });
}

/* Al cerrar sesión no dejamos rastro en el navegador */
if (new URLSearchParams(location.search).has('salida')) {
  try { Object.keys(localStorage).filter(k => k.startsWith('retoma_')).forEach(k => localStorage.removeItem(k)); } catch (e) {}
}

/* Cuestionario: una pregunta por pantalla, se avanza con «Siguiente» */
const cuestionario = document.querySelector('form.cuestionario');
if (cuestionario) {
  const pasos = [...cuestionario.querySelectorAll('.paso')];
  const texto = document.getElementById('progreso-texto');
  const relleno = document.getElementById('progreso-relleno');
  const navegacion = cuestionario.querySelector('.navegacion-pasos');
  const atras = navegacion.querySelector('[data-atras]');
  const siguiente = navegacion.querySelector('[data-siguiente]');
  const enviar = cuestionario.querySelector('button[type=submit]');
  const guardarLuego = cuestionario.querySelector('.guardar-luego');
  const retomar = document.getElementById('retomar');
  let actual = 0;

  const respondido = paso => !paso.querySelector('input[type=radio][required]') || !!paso.querySelector('input[type=radio]:checked');

  function respuestas() {
    const datos = {};
    cuestionario.querySelectorAll('input[type=radio]:checked').forEach(r => { datos[r.name] = r.value; });
    const relato = document.getElementById('relato');
    if (relato && relato.value) datos.relato = relato.value;
    return datos;
  }

  function guardarProgreso() {
    guardado.escribir('cuestionario', { paso: actual, datos: respuestas(), fecha: Date.now() });
  }

  function mostrar(i, desplazar = true) {
    actual = i;
    pasos.forEach((p, j) => { p.hidden = j !== i; });
    const ultimo = i === pasos.length - 1;
    texto.textContent = 'Paso ' + (i + 1) + ' de ' + pasos.length;
    relleno.style.width = ((i + 1) / pasos.length * 100) + '%';
    relleno.classList.toggle('completo', ultimo && respondido(pasos[i]));
    atras.style.visibility = i === 0 ? 'hidden' : 'visible';
    siguiente.hidden = ultimo;
    siguiente.disabled = !respondido(pasos[i]);
    siguiente.textContent = pasos[i].querySelector('input[type=radio][required]') ? 'Siguiente' : 'Continuar';
    enviar.hidden = !ultimo;
    enviar.disabled = !respondido(pasos[i]);
    if (desplazar) cuestionario.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  cuestionario.classList.add('por-pasos');
  navegacion.hidden = false;
  guardarLuego.hidden = false;

  cuestionario.addEventListener('change', ev => {
    if (ev.target.type !== 'radio') return;
    siguiente.disabled = !respondido(pasos[actual]);
    enviar.disabled = !respondido(pasos[actual]);
    relleno.classList.toggle('completo', actual === pasos.length - 1 && respondido(pasos[actual]));
    guardarProgreso();
  });
  atras.addEventListener('click', () => { mostrar(actual - 1); guardarProgreso(); });
  siguiente.addEventListener('click', () => {
    if (!respondido(pasos[actual])) return;
    mostrar(actual + 1);
    guardarProgreso();
  });
  guardarLuego.querySelector('[data-guardar]').addEventListener('click', () => {
    guardarProgreso();
    location.href = 'index.php';
  });
  cuestionario.addEventListener('submit', () => guardado.borrar('cuestionario'));

  const previo = guardado.leer('cuestionario');
  if (previo && previo.datos && Object.keys(previo.datos).length && retomar) {
    retomar.hidden = false;
    retomar.querySelector('[data-continuar]').addEventListener('click', () => {
      Object.entries(previo.datos).forEach(([nombre, valor]) => {
        if (nombre === 'relato') { document.getElementById('relato').value = valor; return; }
        const opcion = cuestionario.querySelector('input[name="' + nombre + '"][value="' + valor + '"]');
        if (opcion) opcion.checked = true;
      });
      retomar.hidden = true;
      mostrar(Math.min(previo.paso || 0, pasos.length - 1));
    });
    retomar.querySelector('[data-reiniciar]').addEventListener('click', () => {
      guardado.borrar('cuestionario');
      retomar.hidden = true;
    });
  }
  mostrar(0, false);
}

const retomarRuta = document.getElementById('retomar-ruta');
if (retomarRuta) {
  const previo = guardado.leer('cuestionario');
  if (previo && previo.datos && Object.keys(previo.datos).length) retomarRuta.hidden = false;
}

/* Apoyos: ¿cuáles me corresponden? */
const mini = document.getElementById('mini-cuestionario');
if (mini) {
  const resumen = document.getElementById('resumen-apoyos');
  const evaluar = () => {
    const d = Object.fromEntries(new FormData(mini));
    const edad = parseInt(d.edad, 10);
    if (!d.edad && !d.estudia && !d.imss && !d.dias) return;
    const reglas = {
      todos: () => true,
      adulto: () => !d.edad || edad >= 18,
      imss: () => d.imss === 'si',
      jcf: () => edad >= 18 && edad <= 29 && d.estudia !== 'si',
      afore: () => d.imss === 'si' && d.dias === 'mucho'
    };
    let cuantos = 0;
    document.querySelectorAll('[data-regla]').forEach(t => {
      const aplica = reglas[t.dataset.regla]();
      t.classList.toggle('corresponde', aplica);
      t.classList.toggle('no-corresponde', !aplica);
      t.querySelector('.marca-corresponde').hidden = !aplica;
      if (aplica) cuantos++;
    });
    resumen.textContent = 'Según tus respuestas, te pueden corresponder ' + cuantos + ' apoyos. Confirma los requisitos en cada sitio oficial.';
  };
  mini.addEventListener('input', evaluar);
  mini.addEventListener('change', evaluar);
}

/* Mapa de valía: sugerencias */
document.querySelectorAll('[data-sugerencia]').forEach(b => b.addEventListener('click', () => {
  const campo = document.getElementById('texto-valia');
  campo.value = b.dataset.sugerencia;
  const cualidad = document.querySelector('input[name=tipo][value=cualidad]');
  if (cualidad) cualidad.checked = true;
  campo.focus();
}));

/* Respiración guiada */
const bolaRespirar = document.getElementById('bola-respirar');
if (bolaRespirar) {
  const indicacion = document.getElementById('indicacion-respirar');
  const boton = document.getElementById('iniciar-respiracion');
  let activo = false, temporizador, ciclos = 0;
  const detener = (mensaje) => {
    activo = false;
    clearTimeout(temporizador);
    bolaRespirar.className = 'bola-respirar';
    boton.textContent = 'Empezar';
    indicacion.textContent = mensaje;
  };
  const ciclo = () => {
    if (!activo) return;
    bolaRespirar.className = 'bola-respirar inhala';
    indicacion.textContent = 'Toma aire… 1, 2, 3, 4';
    temporizador = setTimeout(() => {
      bolaRespirar.className = 'bola-respirar exhala';
      indicacion.textContent = 'Suéltalo despacio… 1, 2, 3, 4, 5, 6';
      temporizador = setTimeout(() => {
        if (++ciclos >= 6) return detener('Lo hiciste muy bien. ¿Cómo se siente tu cuerpo ahora?');
        ciclo();
      }, 6000);
    }, 4000);
  };
  boton.addEventListener('click', () => {
    if (activo) return detener('Toma aire en 4 tiempos y suéltalo en 6.');
    activo = true;
    ciclos = 0;
    boton.textContent = 'Detener';
    ciclo();
  });
}
