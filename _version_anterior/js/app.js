const almacen = {
  leer(clave, defecto) {
    try { const v = localStorage.getItem('retoma_' + clave); return v ? JSON.parse(v) : defecto; }
    catch (e) { return defecto; }
  },
  guardar(clave, valor) {
    try { localStorage.setItem('retoma_' + clave, JSON.stringify(valor)); } catch (e) {}
  }
};

const perfil = almacen.leer('perfil', { alias: 'Andrés', meses: '3-6', dependientes: 'no', sector: 'Comercio y ventas' });

document.querySelectorAll('[data-alias]').forEach(el => el.textContent = perfil.alias);

const quitarAcentos = t => t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

const frasesCrisis = [
  'no quiero vivir', 'quitarme la vida', 'suicid', 'matarme', 'mejor muerto', 'mejor muerta',
  'no vale la pena vivir', 'desaparecer para siempre', 'hacerme dano', 'ya no quiero estar aqui',
  'acabar con todo', 'no aguanto mas'
];
const palabrasNegativas = [
  'triste', 'solo', 'sola', 'fracaso', 'inutil', 'verguenza', 'no sirvo', 'culpa', 'cansado', 'cansada',
  'no duermo', 'angustia', 'ansiedad', 'miedo', 'deudas', 'nadie', 'vacio', 'enojo', 'desesperado', 'desesperada'
];

function analizarTexto(texto) {
  const t = quitarAcentos(texto);
  return {
    crisis: frasesCrisis.some(f => t.includes(f)),
    negativas: palabrasNegativas.filter(p => t.includes(p))
  };
}

/* Registro */
const formRegistro = document.getElementById('form-registro');
if (formRegistro) {
  const sector = document.getElementById('sector');
  const sectorOtro = document.getElementById('sector-otro');
  sector.addEventListener('change', () => {
    const esOtro = sector.value === 'Otro';
    sectorOtro.hidden = !esOtro;
    sectorOtro.required = esOtro;
    if (esOtro) sectorOtro.focus();
  });

  const cuantos = document.getElementById('cuantos-dependen');
  const opcionesCuantos = cuantos.querySelectorAll('input');
  formRegistro.querySelectorAll('input[name=dependientes]').forEach(r => r.addEventListener('change', () => {
    const si = r.value === 'si' && r.checked;
    cuantos.hidden = !si;
    opcionesCuantos[0].required = si;
    if (!si) opcionesCuantos.forEach(o => o.checked = false);
  }));

  formRegistro.addEventListener('submit', e => {
    e.preventDefault();
    const d = new FormData(formRegistro);
    almacen.guardar('perfil', {
      alias: d.get('alias').trim() || 'Anónimo',
      edad: d.get('edad'), meses: d.get('meses'),
      sector: d.get('sector') === 'Otro' ? d.get('sector_otro').trim() : d.get('sector'),
      dependientes: d.get('dependientes'),
      numDependientes: d.get('dependientes') === 'si' ? d.get('num_dependientes') : '0'
    });
    location.href = 'checkin.html';
  });
}

/* Check-in */
const formCheckin = document.getElementById('form-checkin');
if (formCheckin) {
  const botonVoz = document.getElementById('boton-voz');
  const texto = document.getElementById('texto');
  const etiquetaVoz = document.getElementById('etiqueta-voz');
  const medidor = document.getElementById('medidor');
  const cajaVoz = document.getElementById('confirmar-voz');
  const Reconocimiento = window.SpeechRecognition || window.webkitSpeechRecognition;
  const nombresAnimo = { apagado: 'con poca energía', tenso: 'con tensión o prisa', tranquilo: 'en calma' };
  const nombresVoz = { apagado: 'algo apagada', tenso: 'tensa o acelerada', tranquilo: 'tranquila' };
  const prom = a => a.reduce((x, y) => x + y, 0) / (a.length || 1);
  const desv = a => { const m = prom(a); return Math.sqrt(prom(a.map(x => (x - m) ** 2))); };
  const percentil = (a, p) => { const o = [...a].sort((x, y) => x - y); return o.length ? o[Math.min(o.length - 1, Math.floor(o.length * p))] : 0; };
  let sesion = null, ultimaMedicion = null;

  /* Tono por autocorrelación normalizada; evita errores de octava tomando el primer pico fuerte */
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

  async function iniciarVoz() {
    if (!Reconocimiento) { etiquetaVoz.textContent = 'Tu navegador no permite dictado (usa Chrome)'; return; }
    let flujo;
    try {
      flujo = await navigator.mediaDevices.getUserMedia({
        audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: false }
      });
    } catch (e) { etiquetaVoz.textContent = 'Sin permiso de micrófono'; return; }

    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const pasaAltos = ctx.createBiquadFilter();
    pasaAltos.type = 'highpass'; pasaAltos.frequency.value = 80;
    const pasaBajos = ctx.createBiquadFilter();
    pasaBajos.type = 'lowpass'; pasaBajos.frequency.value = 3500;
    const analizador = ctx.createAnalyser();
    analizador.fftSize = 2048;
    ctx.createMediaStreamSource(flujo).connect(pasaAltos);
    pasaAltos.connect(pasaBajos);
    pasaBajos.connect(analizador);
    const buf = new Float32Array(analizador.fftSize);

    sesion = { flujo, ctx, cuadros: [], palabras: 0, inicio: Date.now() };
    sesion.timer = setInterval(() => {
      analizador.getFloatTimeDomainData(buf);
      let s = 0;
      for (const x of buf) s += x * x;
      const db = Math.max(-90, 20 * Math.log10(Math.sqrt(s / buf.length) || 1e-8));
      sesion.cuadros.push({ db, f: db > -55 ? tono(buf, ctx.sampleRate) : 0 });
      medidor.style.width = Math.max(0, Math.min(100, (db + 60) * 2)) + '%';
    }, 100);

    const provisional = document.getElementById('voz-provisional');
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
        texto.value += (texto.value ? ' ' : '') + t;
      }
      provisional.textContent = enCurso.trim();
    };
    rec.onerror = ev => {
      if (['not-allowed', 'service-not-allowed', 'audio-capture'].includes(ev.error) && sesion) {
        sesion.parar = true;
        etiquetaVoz.textContent = 'No se pudo usar el micrófono';
      }
    };
    rec.onend = () => {
      provisional.textContent = '';
      const limite = sesion && Date.now() - sesion.inicio > 180000;
      if (sesion && !sesion.parar && !limite) {
        try { rec.start(); return; } catch (e) {}
      }
      terminarVoz();
    };
    rec.start();
    sesion.rec = rec;
    cajaVoz.hidden = true;
    botonVoz.classList.add('grabando');
    etiquetaVoz.textContent = 'Escuchando… toca para terminar';
  }

  function medir(s) {
    const segundos = (Date.now() - s.inicio) / 1000;
    const piso = Math.max(-70, percentil(s.cuadros.map(c => c.db), 0.05));
    const esVoz = s.cuadros.map(c => c.db > piso + 10);
    const voz = s.cuadros.filter((c, i) => esVoz[i]);

    let pausasLargas = 0, racha = 0;
    esVoz.forEach(v => {
      if (!v) racha++;
      else { if (racha >= 8) pausasLargas++; racha = 0; }
    });

    const tonos = voz.map(c => c.f).filter(f => f > 0);
    const base = percentil(tonos, 0.5) || 1;
    const semitonos = tonos.map(f => 12 * Math.log2(f / base));
    return {
      segundos,
      segundosVoz: voz.length / 10,
      volumen: voz.length ? prom(voz.map(c => c.db)) - piso : 0,
      dinamica: desv(voz.map(c => c.db)),
      rango: tonos.length >= 15 ? percentil(semitonos, 0.9) - percentil(semitonos, 0.1) : null,
      ppm: s.palabras / (segundos / 60),
      pausasMin: pausasLargas / (segundos / 60),
      silencio: 1 - voz.length / s.cuadros.length
    };
  }

  function clasificar(m) {
    const ref = almacen.leer('linea_voz', null);
    const personal = ref && ref.n >= 2;
    let apagado = 0, tenso = 0;

    if (personal) {
      if (m.volumen < ref.volumen - 4) apagado++;
      if (m.volumen > ref.volumen + 5) tenso++;
      if (m.ppm < ref.ppm * 0.8) apagado++;
      if (m.ppm > ref.ppm * 1.2) tenso++;
      if (m.pausasMin > ref.pausasMin * 1.5 + 1) apagado++;
      if (m.rango !== null && ref.rango) {
        if (m.rango < ref.rango * 0.7) apagado++;
        if (m.rango > ref.rango * 1.4) tenso++;
      }
      if (m.dinamica > ref.dinamica * 1.4) tenso++;
    } else {
      if (m.volumen < 16) apagado++;
      if (m.volumen > 32) tenso++;
      if (m.ppm < 95) apagado++;
      if (m.ppm > 170) tenso++;
      if (m.pausasMin > 8) apagado++;
      if (m.rango !== null && m.rango < 3.5) apagado++;
      if (m.rango !== null && m.rango > 10) tenso++;
      if (m.dinamica > 9) tenso++;
    }

    const negativas = analizarTexto(texto.value).negativas.length;
    if (negativas >= 2) apagado++;

    const confiable = m.segundosVoz >= 3 && m.segundos >= 5;
    let estado = 'tranquilo', fuerza = 0;
    if (apagado >= 2 && apagado >= tenso) { estado = 'apagado'; fuerza = apagado; }
    else if (tenso >= 2) { estado = 'tenso'; fuerza = tenso; }
    return { estado, confiable, fuerza, personal };
  }

  function actualizarLineaBase(m, confirmado) {
    const ref = almacen.leer('linea_voz', null);
    const campos = ['volumen', 'ppm', 'pausasMin', 'rango', 'dinamica'];
    if (!ref) {
      const nueva = { n: 1 };
      campos.forEach(k => nueva[k] = m[k]);
      almacen.guardar('linea_voz', nueva);
      return;
    }
    const peso = confirmado === 'tranquilo' ? 0.4 : 0.1;
    campos.forEach(k => {
      if (m[k] === null) return;
      ref[k] = ref[k] === null ? m[k] : ref[k] * (1 - peso) + m[k] * peso;
    });
    ref.n++;
    almacen.guardar('linea_voz', ref);
  }

  function terminarVoz() {
    if (!sesion) return;
    const s = sesion;
    sesion = null;
    clearInterval(s.timer);
    s.flujo.getTracks().forEach(t => t.stop());
    s.ctx.close();
    botonVoz.classList.remove('grabando');
    etiquetaVoz.textContent = 'Dictar con voz';
    medidor.style.width = '0';
    if (s.cuadros.length < 20) return;

    const m = medir(s);
    const r = clasificar(m);
    ultimaMedicion = { m, r };
    console.info('Análisis de voz', m, r);
    mostrarConfirmacion(r);
  }

  function mostrarConfirmacion(r) {
    const pregunta = document.getElementById('pregunta-voz');
    const detectado = document.getElementById('voz_detectado');
    document.getElementById('voz_confirmado').value = '';
    document.getElementById('gracias-voz').hidden = true;
    cajaVoz.querySelectorAll('input[name=voz_corregido]').forEach(x => x.checked = false);

    if (!r.confiable) {
      detectado.value = '';
      pregunta.textContent = 'No alcanzamos a escucharte bien. ¿Cómo te sientes hoy?';
      document.getElementById('acciones-voz').hidden = true;
      document.getElementById('corregir-voz').hidden = false;
    } else {
      detectado.value = r.estado;
      const sobreVoz = r.fuerza >= 3 || r.personal;
      const inicio = r.fuerza >= 3 ? 'Tu voz de hoy suena ' : r.personal ? 'Comparada con otros días, tu voz suena ' : 'Por cómo sonó tu voz, parece que hoy estás ';
      pregunta.innerHTML = inicio + '<b>' + (sobreVoz ? nombresVoz : nombresAnimo)[r.estado] + '</b>. ¿Es así?';
      document.getElementById('acciones-voz').hidden = false;
      document.getElementById('corregir-voz').hidden = true;
    }
    cajaVoz.hidden = false;
    cajaVoz.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function registrarRespuesta(valor) {
    document.getElementById('voz_confirmado').value = valor;
    document.getElementById('gracias-voz').hidden = false;
    if (!ultimaMedicion) return;
    actualizarLineaBase(ultimaMedicion.m, valor);
    const etiquetas = almacen.leer('etiquetas_voz', []);
    etiquetas.push({ fecha: new Date().toISOString(), medidas: ultimaMedicion.m, detectado: ultimaMedicion.r.estado, confirmado: valor });
    almacen.guardar('etiquetas_voz', etiquetas.slice(-60));
    ultimaMedicion = null;
  }

  if (cajaVoz) {
    cajaVoz.querySelectorAll('[data-resp]').forEach(b => b.addEventListener('click', () => {
      document.getElementById('acciones-voz').hidden = true;
      if (b.dataset.resp === 'si') registrarRespuesta(document.getElementById('voz_detectado').value);
      else document.getElementById('corregir-voz').hidden = false;
    }));
    cajaVoz.querySelectorAll('input[name=voz_corregido]').forEach(x => x.addEventListener('change', () => registrarRespuesta(x.value)));
  }

  if (botonVoz) {
    botonVoz.addEventListener('click', () => {
      if (sesion) { sesion.parar = true; sesion.rec.stop(); }
      else iniciarVoz();
    });
  }

  formCheckin.addEventListener('submit', e => {
    e.preventDefault();
    const d = new FormData(formCheckin);
    const animo = parseInt(d.get('animo'), 10);
    const analisis = analizarTexto(d.get('texto') || '');
    const vozDetectado = d.get('voz_detectado'), vozConfirmado = d.get('voz_confirmado');
    const textoAnimo = { 1: 'muy mal', 2: 'mal', 3: 'más o menos', 4: 'bien', 5: 'muy bien' };

    const razones = ['Nos dijiste que hoy te sientes ' + textoAnimo[animo] + '.'];
    if (vozConfirmado === 'apagado') razones.push('Confirmaste que hoy te sientes con poca energía.');
    else if (vozConfirmado === 'tenso') razones.push('Confirmaste que hoy te sientes con tensión.');
    if (vozDetectado && vozConfirmado && vozConfirmado !== vozDetectado) razones.push('Tu voz sugería otra cosa, pero tomamos en cuenta lo que tú nos dijiste.');
    if (analisis.negativas.length >= 3) razones.push('Lo que escribiste tiene un tono bastante pesado hoy.');

    let nivel = 'verde';
    if (analisis.crisis) {
      nivel = 'rojo';
      razones.unshift('Lo que escribiste nos hace pensar que necesitas apoyo ahora.');
    } else if (animo <= 2 || (animo === 3 && (vozConfirmado === 'apagado' || analisis.negativas.length >= 3))) {
      nivel = 'amarillo';
    }

    let ritmo = animo <= 2 ? 0 : animo === 3 ? 1 : 2;
    if (vozConfirmado === 'apagado' || vozConfirmado === 'tenso') ritmo = Math.max(0, ritmo - 1);

    const historial = almacen.leer('historial', []);
    historial.push({ fecha: new Date().toISOString(), animo: animo * 2, nivel });
    almacen.guardar('historial', historial.slice(-14));
    almacen.guardar('ultimo', { nivel, razones, animo, ritmo });

    location.href = 'resultado.html?nivel=' + nivel;
  });
}

/* Resultado */
const semaforo = document.getElementById('semaforo');
if (semaforo) {
  const ultimo = almacen.leer('ultimo', { nivel: 'amarillo', razones: ['Resultado de ejemplo para la demostración.'] });
  const nivel = new URLSearchParams(location.search).get('nivel') || ultimo.nivel;
  semaforo.className = 'semaforo ' + nivel;
  document.getElementById('nivel-' + nivel).classList.add('visible');
  const lista = document.getElementById('razones');
  ultimo.razones.forEach(r => { const li = document.createElement('li'); li.textContent = r; lista.appendChild(li); });
}

/* Cursos de oficio */
const cursos = [
  { nombre: 'Reparación de celulares', ritmo: 0, modalidad: 'En línea', duracion: '4 semanas', afines: ['Tecnología', 'Comercio y ventas'], texto: 'Cambio de pantallas, baterías y puertos de carga. Puedes empezar a ofrecer el servicio desde casa.' },
  { nombre: 'Costura y confección', ritmo: 0, modalidad: 'En línea', duracion: '6 semanas', afines: ['Manufactura y maquila'], texto: 'Arreglos, ajustes y prendas sencillas. Se aprende con videos cortos y a tu ritmo.' },
  { nombre: 'Panadería y repostería', ritmo: 1, modalidad: 'Presencial', duracion: '6 semanas', afines: ['Restaurantes y servicios', 'Comercio y ventas'], texto: 'Pan de dulce, pasteles y costeo básico para vender por pedido.' },
  { nombre: 'Barbería', ritmo: 1, modalidad: 'Presencial', duracion: '8 semanas', afines: ['Restaurantes y servicios', 'Comercio y ventas'], texto: 'Cortes clásicos y modernos, manejo de máquina y atención al cliente.' },
  { nombre: 'Carpintería básica', ritmo: 1, modalidad: 'Presencial', duracion: '8 semanas', afines: ['Manufactura y maquila'], texto: 'Medición, corte y ensamble de muebles sencillos. Trabajo con las manos y a paso tranquilo.' },
  { nombre: 'Electricidad residencial', ritmo: 2, modalidad: 'Presencial', duracion: '3 meses', afines: ['Manufactura y maquila', 'Tecnología'], texto: 'Instalaciones, contactos y tableros en casa. Oficio con demanda constante.' },
  { nombre: 'Refrigeración y aire acondicionado', ritmo: 2, modalidad: 'Presencial', duracion: '4 meses', afines: ['Manufactura y maquila', 'Oficina y administración'], texto: 'Instalación y mantenimiento de minisplits y refrigeradores.' },
  { nombre: 'Soldadura', ritmo: 2, modalidad: 'Presencial', duracion: '3 meses', afines: ['Manufactura y maquila'], texto: 'Soldadura de arco y MIG, con práctica en taller y constancia al terminar.' },
  { nombre: 'Instalación de paneles solares', ritmo: 2, modalidad: 'Mixto', duracion: '10 semanas', afines: ['Tecnología', 'Call center y atención a clientes'], texto: 'Oficio en crecimiento: montaje, conexión y mantenimiento de sistemas solares.' }
];
const listaCursos = document.getElementById('lista-cursos');
if (listaCursos) {
  const ultimo = almacen.leer('ultimo', { nivel: 'verde', ritmo: 1 });
  const nombresRitmo = ['A tu ritmo', 'Ritmo medio', 'Intensivo'];
  const intros = [
    'Hoy no es día de exigirte de más. Estos cursos son cortos, flexibles y los puedes llevar a tu ritmo.',
    'Estos cursos tienen un ritmo moderado: avanzas sin cargarte demasiado.',
    'Se nota que hoy tienes energía. Es buen momento para un curso más completo, con constancia al terminar.'
  ];
  if (ultimo.nivel !== 'rojo') {
    const ritmo = ultimo.ritmo ?? 1;
    const elegidos = cursos
      .map(c => ({ c, puntos: Math.abs(c.ritmo - ritmo) * 2 - (c.afines.includes(perfil.sector) ? 1 : 0) }))
      .sort((a, b) => a.puntos - b.puntos)
      .slice(0, 3)
      .map(x => x.c);
    const interes = almacen.leer('cursos_interes', []);
    document.getElementById('cursos-intro').textContent = intros[ritmo];
    listaCursos.innerHTML = elegidos.map(c => `
      <div class="tarjeta curso">
        <h3>${c.nombre}</h3>
        <p>${c.texto}</p>
        <p class="curso-datos">${c.modalidad} · ${c.duracion} · ${nombresRitmo[c.ritmo]}</p>
        <button type="button" class="boton linea" data-curso="${c.nombre}">${interes.includes(c.nombre) ? 'Guardado' : 'Me interesa'}</button>
      </div>`).join('');
    listaCursos.querySelectorAll('[data-curso]').forEach(b => b.addEventListener('click', () => {
      const guardados = almacen.leer('cursos_interes', []);
      const nombre = b.dataset.curso;
      const i = guardados.indexOf(nombre);
      if (i >= 0) guardados.splice(i, 1); else guardados.push(nombre);
      almacen.guardar('cursos_interes', guardados);
      b.textContent = i >= 0 ? 'Me interesa' : 'Guardado';
    }));
    document.getElementById('cursos').hidden = false;
  }
}

/* Respiración */
const bola = document.getElementById('bola');
if (bola) {
  const indicacion = document.getElementById('indicacion');
  const boton = document.getElementById('iniciar-resp');
  let activo = false, temporizador, ciclos = 0;
  const ciclo = () => {
    if (!activo) return;
    bola.className = 'bola inhala'; indicacion.textContent = 'Inhala… 4';
    temporizador = setTimeout(() => {
      bola.className = 'bola exhala'; indicacion.textContent = 'Suelta despacio… 6';
      temporizador = setTimeout(() => {
        ciclos++;
        if (ciclos >= 6) { detener(); indicacion.textContent = 'Listo. ¿Cómo se siente el cuerpo ahora?'; return; }
        ciclo();
      }, 6000);
    }, 4000);
  };
  const detener = () => { activo = false; clearTimeout(temporizador); bola.className = 'bola'; boton.textContent = 'Empezar (1 minuto)'; };
  boton.addEventListener('click', () => {
    if (activo) { detener(); indicacion.textContent = ''; return; }
    activo = true; ciclos = 0; boton.textContent = 'Detener'; ciclo();
  });
}

/* Checklist persistente */
document.querySelectorAll('.checklist[data-lista]').forEach(ul => {
  const clave = 'lista_' + ul.dataset.lista;
  const marcados = almacen.leer(clave, []);
  const casillas = ul.querySelectorAll('input[type=checkbox]');
  const contador = document.querySelector('[data-contador="' + ul.dataset.lista + '"]');
  const actualizar = () => {
    const hechos = [...casillas].filter(c => c.checked).length;
    if (contador) contador.textContent = hechos + ' de ' + casillas.length;
  };
  casillas.forEach((c, i) => {
    c.checked = marcados.includes(i);
    c.addEventListener('change', () => {
      almacen.guardar(clave, [...casillas].map((x, j) => x.checked ? j : -1).filter(j => j >= 0));
      actualizar();
    });
  });
  actualizar();
});

/* Panel: gráfica de ánimo */
const grafica = document.getElementById('grafica-animo');
if (grafica) {
  const ejemplo = [4, 5, 3, 5, 6, 5, 7];
  const colorNivel = { verde: '#2E7A56', amarillo: '#C4952B', rojo: '#A63A2A' };
  let datos = almacen.leer('historial', []).slice(-7).map(h => ({ v: h.animo, c: colorNivel[h.nivel] }));
  if (datos.length < 3) datos = ejemplo.map(v => ({ v, c: v >= 6 ? colorNivel.verde : v >= 4 ? colorNivel.amarillo : colorNivel.rojo }));
  const dias = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
  const ancho = 560, alto = 200, base = 170, paso = ancho / 7;
  let svg = `<svg viewBox="0 0 ${ancho} ${alto}" role="img" aria-label="Ánimo de los últimos días">`;
  [0, 5, 10].forEach(g => {
    const y = base - g * 15;
    svg += `<line x1="0" x2="${ancho}" y1="${y}" y2="${y}" stroke="#D5CCBC" stroke-dasharray="${g ? '3 4' : ''}"/>`;
  });
  datos.forEach((d, i) => {
    const h = d.v * 15, x = i * paso + paso * .28;
    svg += `<rect x="${x}" y="${base - h}" width="${paso * .44}" height="${h}" fill="${d.c}" rx="2"/>`;
    svg += `<text x="${x + paso * .22}" y="${base - h - 7}" text-anchor="middle" font-size="13" fill="#4B534D" font-family="Fraunces, Georgia">${d.v}</text>`;
    svg += `<text x="${x + paso * .22}" y="${alto - 8}" text-anchor="middle" font-size="12" fill="#7C7A70">${dias[i]}</text>`;
  });
  grafica.innerHTML = svg + '</svg>';

  const ultimo = almacen.leer('ultimo', null);
  const chip = document.getElementById('ultimo-nivel');
  if (chip && ultimo) {
    const nombres = { verde: 'Estable', amarillo: 'Con carga', rojo: 'Necesita apoyo' };
    const clase = { verde: 'v', amarillo: 'a', rojo: 'r' };
    chip.innerHTML = `<i class="punto ${clase[ultimo.nivel]}"></i> Último check-in: ${nombres[ultimo.nivel]}`;
  }
  const mesesTexto = { '0-1': 'menos de 1', '1-3': '1 a 3', '3-6': '3 a 6', '6-12': '6 a 12', '12+': 'más de 12' };
  const meses = document.getElementById('meses-sin-empleo');
  if (meses) meses.textContent = mesesTexto[perfil.meses] || '3 a 6';
}

/* Círculos */
document.querySelectorAll('[data-apartar]').forEach(b => {
  const clave = 'circulo_' + b.dataset.apartar;
  const lugares = b.closest('.circulo-item').querySelector('.lugares b');
  const marcar = on => {
    b.classList.toggle('apartado', on);
    b.textContent = on ? 'Lugar apartado' : 'Apartar lugar';
    lugares.textContent = parseInt(lugares.dataset.base, 10) - (on ? 1 : 0);
  };
  lugares.dataset.base = lugares.textContent;
  marcar(almacen.leer(clave, false));
  b.addEventListener('click', () => { const on = !b.classList.contains('apartado'); almacen.guardar(clave, on); marcar(on); });
});
