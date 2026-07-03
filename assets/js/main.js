document.addEventListener('DOMContentLoaded', function () {
    var audio = document.getElementById('radio-audio');
    var playBtn = document.getElementById('play-btn');
    var playIcon = document.getElementById('play-icon');
    var volumeSlider = document.getElementById('volume-slider');
    var muteBtn = document.getElementById('mute-btn');
    var muteIcon = document.getElementById('mute-icon');
    var statusLabel = document.getElementById('player-status');
    var canvas = document.getElementById('visualizer');
    var playerCard = document.querySelector('.player-card');

    var miniPlayer = document.getElementById('mini-player');
    var miniPlayBtn = document.getElementById('mini-play-btn');
    var miniPlayIcon = document.getElementById('mini-play-icon');
    var miniStatusLabel = document.getElementById('mini-player-status');

    var config = window.VOZSTATION_CONFIG || {};

    if (!audio || !playBtn) {
        return;
    }

    var STORAGE_VOLUME_KEY = 'vozstation_volume';
    var STORAGE_MUTED_KEY = 'vozstation_muted';

    function storageGet(key) {
        try {
            return localStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }

    function storageSet(key, value) {
        try {
            localStorage.setItem(key, value);
        } catch (e) {
            // almacenamiento no disponible (modo privado, etc.); no es crítico
        }
    }

    var isPlaying = false;
    var userWantsPlaying = false;
    var reconnectTimer = null;
    var reconnectDelay = 3000;
    var viz = canvas ? setupVisualizer(canvas) : null;

    function setStatusText(text) {
        if (statusLabel) {
            statusLabel.textContent = text;
        }
        if (miniStatusLabel) {
            miniStatusLabel.textContent = text;
        }
    }

    function updateMediaSessionState(playing) {
        if ('mediaSession' in navigator) {
            navigator.mediaSession.playbackState = playing ? 'playing' : 'paused';
        }
    }

    function setPlayingUI(playing) {
        isPlaying = playing;
        var iconClass = playing ? 'bi bi-pause-fill' : 'bi bi-play-fill';
        playIcon.className = iconClass;
        playBtn.setAttribute('aria-label', playing ? 'Pausar' : 'Reproducir');
        if (miniPlayIcon) {
            miniPlayIcon.className = iconClass;
        }
        if (miniPlayBtn) {
            miniPlayBtn.setAttribute('aria-label', playing ? 'Pausar' : 'Reproducir');
        }
        setStatusText(playing ? 'En vivo' : 'En pausa');
        updateMediaSessionState(playing);
    }

    function clearReconnectTimer() {
        if (reconnectTimer) {
            clearTimeout(reconnectTimer);
            reconnectTimer = null;
        }
    }

    function startStream() {
        setStatusText('Conectando...');
        audio.src = audio.dataset.streamUrl + '?t=' + Date.now();
        return audio.play();
    }

    function scheduleReconnect() {
        if (!userWantsPlaying) {
            return;
        }
        clearReconnectTimer();
        setStatusText('Reconectando...');
        reconnectTimer = setTimeout(function () {
            if (!userWantsPlaying) {
                return;
            }
            startStream().catch(function () {
                reconnectDelay = Math.min(reconnectDelay + 2000, 15000);
                scheduleReconnect();
            });
        }, reconnectDelay);
    }

    function setButtonsDisabled(disabled) {
        playBtn.disabled = disabled;
        if (miniPlayBtn) {
            miniPlayBtn.disabled = disabled;
        }
    }

    function play() {
        userWantsPlaying = true;
        clearReconnectTimer();
        reconnectDelay = 3000;
        setButtonsDisabled(true);

        startStream()
            .then(function () {
                setPlayingUI(true);
            })
            .catch(function () {
                setStatusText('No se pudo conectar al stream');
            })
            .finally(function () {
                setButtonsDisabled(false);
            });
    }

    function pause() {
        userWantsPlaying = false;
        clearReconnectTimer();
        reconnectDelay = 3000;
        audio.pause();
        setPlayingUI(false);
    }

    function togglePlay() {
        if (isPlaying) {
            pause();
        } else {
            play();
        }
    }

    playBtn.addEventListener('click', togglePlay);
    if (miniPlayBtn) {
        miniPlayBtn.addEventListener('click', togglePlay);
    }

    audio.addEventListener('waiting', function () {
        if (userWantsPlaying) {
            setStatusText('Cargando...');
        }
    });

    audio.addEventListener('playing', function () {
        setPlayingUI(true);
        if (viz) {
            viz.start();
        }
    });

    audio.addEventListener('pause', function () {
        if (viz) {
            viz.stop();
        }
        if (!userWantsPlaying) {
            setPlayingUI(false);
        }
    });

    audio.addEventListener('error', function () {
        if (viz) {
            viz.stop();
        }
        if (userWantsPlaying) {
            scheduleReconnect();
        } else {
            setPlayingUI(false);
        }
    });

    // --- Volumen y silencio (con preferencia recordada) ---
    var savedVolume = parseFloat(storageGet(STORAGE_VOLUME_KEY));
    if (isNaN(savedVolume)) {
        savedVolume = 0.8;
    }
    var savedMuted = storageGet(STORAGE_MUTED_KEY) === '1';

    audio.volume = savedVolume;
    audio.muted = savedMuted;
    if (volumeSlider) {
        volumeSlider.value = savedVolume;
    }

    function updateMuteIcon() {
        if (!muteIcon) {
            return;
        }
        if (audio.muted || audio.volume === 0) {
            muteIcon.className = 'bi bi-volume-mute-fill text-secondary';
        } else if (audio.volume < 0.5) {
            muteIcon.className = 'bi bi-volume-down-fill text-secondary';
        } else {
            muteIcon.className = 'bi bi-volume-up-fill text-secondary';
        }
    }
    updateMuteIcon();

    if (volumeSlider) {
        volumeSlider.addEventListener('input', function () {
            audio.volume = parseFloat(volumeSlider.value);
            audio.muted = false;
            storageSet(STORAGE_VOLUME_KEY, String(audio.volume));
            storageSet(STORAGE_MUTED_KEY, '0');
            updateMuteIcon();
        });
    }

    if (muteBtn) {
        muteBtn.addEventListener('click', function () {
            audio.muted = !audio.muted;
            storageSet(STORAGE_MUTED_KEY, audio.muted ? '1' : '0');
            updateMuteIcon();
        });
    }

    // --- Mini-reproductor flotante al salir la portada del viewport ---
    if (miniPlayer && playerCard && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            var show = !entries[0].isIntersecting;
            miniPlayer.classList.toggle('is-visible', show);
            document.body.classList.toggle('mini-player-active', show);
        }, { threshold: 0 });
        observer.observe(playerCard);
    }

    // --- Media Session: controles en pantalla de bloqueo / notificaciones ---
    if ('mediaSession' in navigator && typeof MediaMetadata !== 'undefined') {
        var artwork = [];
        if (config.logo) {
            artwork = [{ src: config.logo, sizes: '512x512', type: 'image/png' }];
        }
        navigator.mediaSession.metadata = new MediaMetadata({
            title: config.siteName || 'Radio en vivo',
            artist: config.tagline || '',
            artwork: artwork,
        });
        navigator.mediaSession.setActionHandler('play', play);
        navigator.mediaSession.setActionHandler('pause', pause);
    }

    // --- Accesibilidad: barra espaciadora reproduce/pausa ---
    document.addEventListener('keydown', function (e) {
        if (e.code !== 'Space') {
            return;
        }
        var tag = (e.target && e.target.tagName) || '';
        if (['INPUT', 'TEXTAREA', 'BUTTON', 'SELECT', 'A'].indexOf(tag) !== -1) {
            return;
        }
        e.preventDefault();
        togglePlay();
    });

    // Resalta el link de navegación activo al hacer scroll
    var sections = document.querySelectorAll('section[id]');
    var navLinks = document.querySelectorAll('.navbar-vozstation .nav-link');

    window.addEventListener('scroll', function () {
        var scrollPos = window.scrollY + 120;
        sections.forEach(function (section) {
            if (scrollPos >= section.offsetTop && scrollPos < section.offsetTop + section.offsetHeight) {
                navLinks.forEach(function (link) {
                    link.classList.toggle('active', link.getAttribute('href') === '#' + section.id);
                });
            }
        });
    });
});

/**
 * Visualizador de barras puramente decorativo (no toca el audio real).
 *
 * Nota: enganchar el <audio> a Web Audio API (createMediaElementSource) para
 * leer frecuencias reales redirige TODA la salida de sonido del elemento
 * hacia ese grafo; si el AudioContext queda "suspended" (pasa seguido si no
 * se resume exactamente dentro del gesto de clic, o si el navegador suspende
 * el contexto en segundo plano) el audio se silencia por completo aunque
 * parezca estar reproduciéndose. Para una radio en vivo esa es la peor falla
 * posible, así que este visualizador nunca toca el elemento de audio: solo
 * simula el movimiento mientras isPlaying es true.
 */
function setupVisualizer(canvas) {
    var ctx = canvas.getContext('2d');
    var BAR_COUNT = 48;
    var rafId = null;
    var bars = new Array(BAR_COUNT).fill(0.08);

    function resize() {
        var dpr = window.devicePixelRatio || 1;
        var rect = canvas.getBoundingClientRect();
        canvas.width = Math.max(1, rect.width * dpr);
        canvas.height = Math.max(1, rect.height * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }
    window.addEventListener('resize', resize);
    resize();

    function colors() {
        var styles = getComputedStyle(document.documentElement);
        return {
            accent: styles.getPropertyValue('--radio-accent').trim() || '#e50914',
            accentBlue: styles.getPropertyValue('--radio-accent-blue').trim() || '#1e6fe8',
        };
    }

    function draw() {
        var rect = canvas.getBoundingClientRect();
        var w = rect.width;
        var h = rect.height;
        if (w === 0 || h === 0) {
            return;
        }
        ctx.clearRect(0, 0, w, h);

        var c = colors();
        var gradient = ctx.createLinearGradient(0, 0, w, 0);
        gradient.addColorStop(0, c.accent);
        gradient.addColorStop(1, c.accentBlue);
        ctx.fillStyle = gradient;

        var gap = 3;
        var barWidth = (w - gap * (BAR_COUNT - 1)) / BAR_COUNT;

        for (var i = 0; i < BAR_COUNT; i++) {
            var barHeight = Math.max(2, bars[i] * h);
            var x = i * (barWidth + gap);
            var y = h - barHeight;
            ctx.fillRect(x, y, barWidth, barHeight);
        }
    }

    function tick() {
        rafId = requestAnimationFrame(tick);
        for (var i = 0; i < BAR_COUNT; i++) {
            var target = 0.12 + Math.random() * 0.85;
            bars[i] += (target - bars[i]) * 0.2;
        }
        draw();
    }

    return {
        start: function () {
            if (!rafId) {
                tick();
            }
        },
        stop: function () {
            if (rafId) {
                cancelAnimationFrame(rafId);
                rafId = null;
            }
            var rect = canvas.getBoundingClientRect();
            ctx.clearRect(0, 0, rect.width, rect.height);
        },
    };
}
