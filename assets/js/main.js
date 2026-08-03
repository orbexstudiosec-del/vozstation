document.addEventListener('DOMContentLoaded', function () {
    // Ajusta el espacio superior del body al alto real del navbar fijo, para
    // que nunca quede un hueco (o un solapamiento) entre el menú y el hero.
    // Un ResizeObserver detecta también los cambios de alto al hacer scroll
    // (el navbar se compacta con la clase is-scrolled), no solo el resize de ventana.
    var navbarEl = document.querySelector('.navbar-vozstation');

    function syncNavbarOffset() {
        if (navbarEl) {
            document.body.style.paddingTop = navbarEl.offsetHeight + 'px';
        }
    }
    syncNavbarOffset();

    if (navbarEl && 'ResizeObserver' in window) {
        new ResizeObserver(syncNavbarOffset).observe(navbarEl);
    } else {
        window.addEventListener('resize', syncNavbarOffset);
    }

    setupCubeSlider();
});

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
        playBtn.classList.toggle('is-playing', playing);
        if (miniPlayIcon) {
            miniPlayIcon.className = iconClass;
        }
        if (miniPlayBtn) {
            miniPlayBtn.setAttribute('aria-label', playing ? 'Pausar' : 'Reproducir');
        }
        setStatusText(playing ? 'En vivo' : 'En pausa');
        updateMediaSessionState(playing);
    }

    function setConnectingUI(connecting) {
        var iconClass = connecting ? 'bi bi-arrow-repeat icon-spin' : (isPlaying ? 'bi bi-pause-fill' : 'bi bi-play-fill');
        playIcon.className = iconClass;
        if (miniPlayIcon) {
            miniPlayIcon.className = iconClass;
        }
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
        setConnectingUI(true);
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
        setConnectingUI(true);

        startStream()
            .then(function () {
                setPlayingUI(true);
            })
            .catch(function () {
                setConnectingUI(false);
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
            setConnectingUI(true);
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

    // --- Autoplay al entrar al sitio ---
    // La mayoría de navegadores bloquean el autoplay con sonido si el usuario
    // no interactuó antes con el sitio (política estándar anti-molestias). Por
    // eso esto es un intento silencioso: si el navegador lo permite, arranca
    // solo; si lo bloquea, no pasa nada raro, el usuario simplemente ve el
    // botón de play normal y lo activa con un clic.
    audio.src = audio.dataset.streamUrl + '?t=' + Date.now();
    audio.play()
        .then(function () {
            userWantsPlaying = true;
            setPlayingUI(true);
        })
        .catch(function () {
            // autoplay bloqueado por el navegador; se deja en pausa normal
        });

    // --- Botones de compartir ---
    var nativeShareBtn = document.getElementById('native-share-btn');
    if (nativeShareBtn) {
        if (navigator.share) {
            nativeShareBtn.classList.remove('d-none');
            nativeShareBtn.addEventListener('click', function () {
                navigator.share({
                    title: nativeShareBtn.dataset.shareText,
                    text: nativeShareBtn.dataset.shareText,
                    url: nativeShareBtn.dataset.shareUrl,
                }).catch(function () {
                    // el usuario canceló el diálogo de compartir; no hacer nada
                });
            });
        }
    }

    var copyLinkBtn = document.getElementById('copy-link-btn');
    if (copyLinkBtn) {
        copyLinkBtn.addEventListener('click', function () {
            var url = copyLinkBtn.dataset.shareUrl;

            function showCopied() {
                var icon = copyLinkBtn.querySelector('i');
                var originalClass = icon.className;
                icon.className = 'bi bi-check-lg';
                copyLinkBtn.classList.add('is-copied');
                setTimeout(function () {
                    icon.className = originalClass;
                    copyLinkBtn.classList.remove('is-copied');
                }, 1800);
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(showCopied).catch(function () {});
            } else {
                var textarea = document.createElement('textarea');
                textarea.value = url;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    showCopied();
                } catch (e) {
                    // copia no soportada; el usuario puede copiar el link manualmente
                }
                document.body.removeChild(textarea);
            }
        });
    }

    // --- Link "TV en vivo" del menú: además de ir al hero, activa la pestaña de TV ---
    var navTvLink = document.getElementById('nav-tv-link');
    var tvTabBtn = document.getElementById('tv-tab-btn');
    if (navTvLink && tvTabBtn && window.bootstrap) {
        navTvLink.addEventListener('click', function () {
            bootstrap.Tab.getOrCreateInstance(tvTabBtn).show();
        });
    }

    // --- Mini-reproductor flotante al salir la portada del viewport ---
    // Solo debe aparecer cuando ya bajaste MÁS ALLÁ del reproductor (quedó
    // arriba de la pantalla), no simplemente porque todavía no llegaste a él
    // (por ejemplo, mientras se ve el slider que está antes en la página).
    if (miniPlayer && playerCard && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            var entry = entries[0];
            var scrolledPast = !entry.isIntersecting && entry.boundingClientRect.top < 0;
            miniPlayer.classList.toggle('is-visible', scrolledPast);
            document.body.classList.toggle('mini-player-active', scrolledPast);
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

    // Resalta el link de navegación activo, oscurece el navbar y muestra/oculta
    // el botón "volver arriba" según el scroll.
    var sections = document.querySelectorAll('section[id]');
    var navLinks = document.querySelectorAll('.navbar-vozstation .nav-link');
    var navbar = document.querySelector('.navbar-vozstation');
    var backToTop = document.getElementById('back-to-top');

    window.addEventListener('scroll', function () {
        var scrollPos = window.scrollY + 120;
        sections.forEach(function (section) {
            if (scrollPos >= section.offsetTop && scrollPos < section.offsetTop + section.offsetHeight) {
                navLinks.forEach(function (link) {
                    link.classList.toggle('active', link.getAttribute('href') === '#' + section.id);
                });
            }
        });

        if (navbar) {
            navbar.classList.toggle('is-scrolled', window.scrollY > 40);
        }
        if (backToTop) {
            backToTop.classList.toggle('is-visible', window.scrollY > window.innerHeight * 0.6);
        }
    });

    if (backToTop) {
        backToTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // --- Animaciones de entrada al hacer scroll ---
    var revealItems = document.querySelectorAll('.reveal, .reveal-trigger');
    var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (revealItems.length) {
        if (prefersReducedMotion || !('IntersectionObserver' in window)) {
            revealItems.forEach(function (el) {
                el.classList.add('is-visible');
            });
        } else {
            var revealObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        revealObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

            revealItems.forEach(function (el) {
                revealObserver.observe(el);
            });
        }
    }

    // --- Reproductor de TV en vivo (HLS) ---
    var tvVideo = document.getElementById('tv-video');
    var tvPlayBtn = document.getElementById('tv-play-btn');
    var tvStatus = document.getElementById('tv-status');
    if (tvVideo && tvPlayBtn) {
        setupVideoPlayer(tvVideo, tvPlayBtn, tvStatus);
    }
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

/**
 * Reproductor de TV en vivo vía HLS (.m3u8). Usa hls.js cuando está
 * disponible (Chrome/Firefox/Edge); en navegadores con soporte nativo de HLS
 * (Safari/iOS) usa el <video> directo. Si el stream se corta, reintenta solo
 * con backoff, igual que el reproductor de audio.
 */
function setupVideoPlayer(video, playBtn, statusEl) {
    var streamUrl = video.dataset.streamUrl;
    var playIcon = playBtn.querySelector('i');
    var hls = null;
    var userWantsPlaying = false;
    var reconnectTimer = null;
    var reconnectDelay = 3000;

    function setStatus(text) {
        if (statusEl) {
            statusEl.textContent = text;
        }
    }

    function setConnecting(connecting) {
        if (playIcon) {
            playIcon.className = connecting ? 'bi bi-arrow-repeat icon-spin' : 'bi bi-play-fill';
        }
    }

    function clearReconnectTimer() {
        if (reconnectTimer) {
            clearTimeout(reconnectTimer);
            reconnectTimer = null;
        }
    }

    function attachSource() {
        var freshUrl = streamUrl + (streamUrl.indexOf('?') === -1 ? '?' : '&') + 't=' + Date.now();

        if (window.Hls && Hls.isSupported()) {
            if (hls) {
                hls.destroy();
            }
            hls = new Hls();
            hls.loadSource(freshUrl);
            hls.attachMedia(video);
            hls.on(Hls.Events.ERROR, function (event, data) {
                if (data && data.fatal && userWantsPlaying) {
                    scheduleReconnect();
                }
            });
        } else {
            video.src = freshUrl;
        }
    }

    function scheduleReconnect() {
        if (!userWantsPlaying) {
            return;
        }
        clearReconnectTimer();
        setStatus('Reconectando...');
        setConnecting(true);
        reconnectTimer = setTimeout(function () {
            if (!userWantsPlaying) {
                return;
            }
            attachSource();
            video.play().catch(function () {});
            reconnectDelay = Math.min(reconnectDelay + 2000, 15000);
        }, reconnectDelay);
    }

    function play() {
        userWantsPlaying = true;
        clearReconnectTimer();
        reconnectDelay = 3000;
        setStatus('Conectando...');
        setConnecting(true);
        attachSource();
        video.play()
            .then(function () {
                playBtn.classList.add('is-hidden');
                setStatus('En vivo');
            })
            .catch(function () {
                setConnecting(false);
                setStatus('No se pudo conectar a la señal de TV');
            });
    }

    playBtn.addEventListener('click', play);

    video.addEventListener('playing', function () {
        userWantsPlaying = true;
        playBtn.classList.add('is-hidden');
        setStatus('En vivo');
    });

    video.addEventListener('pause', function () {
        userWantsPlaying = false;
        clearReconnectTimer();
    });

    video.addEventListener('waiting', function () {
        if (userWantsPlaying) {
            setStatus('Cargando...');
            setConnecting(true);
        }
    });

    video.addEventListener('error', function () {
        if (userWantsPlaying) {
            scheduleReconnect();
        }
    });
}

/**
 * Slider principal con transición de cubo 3D. Mantiene 3 caras fijas
 * (frente, derecha, izquierda) y solo les cambia el contenido; el cubo
 * "gira" rotando el escenario -90°/90° y al terminar la animación se
 * resetea a 0° de forma instantánea con el contenido ya actualizado, así
 * el truco es imperceptible y funciona con cualquier cantidad de slides.
 */
function setupCubeSlider() {
    var slides = window.VOZSTATION_SLIDES || [];
    var container = document.getElementById('cubeSlider');
    var stage = document.getElementById('cubeStage');
    var faceFront = document.getElementById('cubeFaceFront');
    var faceRight = document.getElementById('cubeFaceRight');
    var faceLeft = document.getElementById('cubeFaceLeft');
    var prevBtn = document.getElementById('cubePrevBtn');
    var nextBtn = document.getElementById('cubeNextBtn');
    var indicatorsWrap = document.getElementById('cubeIndicators');

    if (!container || !stage || !faceFront || !slides.length) {
        return;
    }

    var AUTOPLAY_DELAY = 6000;
    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var currentIndex = 0;
    var isAnimating = false;
    var autoplayTimer = null;
    var currentDepth = 300;

    function mod(n, m) {
        return ((n % m) + m) % m;
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function renderFace(faceEl, slide) {
        if (!faceEl || !slide) {
            return;
        }
        faceEl.style.backgroundImage = "url('" + slide.image + "')";
        if (slide.title || slide.subtitle) {
            var html = '<div class="main-slider-caption">';
            if (slide.title) {
                html += '<h2>' + escapeHtml(slide.title) + '</h2>';
            }
            if (slide.subtitle) {
                html += '<p>' + escapeHtml(slide.subtitle) + '</p>';
            }
            if (slide.link) {
                html += '<a href="' + escapeHtml(slide.link) + '" class="btn btn-lg btn-accent" target="_blank" rel="noopener">Ver más</a>';
            }
            html += '</div>';
            faceEl.innerHTML = html;
        } else {
            faceEl.innerHTML = '';
        }
    }

    function updateIndicators() {
        if (!indicatorsWrap) {
            return;
        }
        indicatorsWrap.querySelectorAll('button').forEach(function (btn, i) {
            btn.classList.toggle('active', i === currentIndex);
        });
    }

    function renderNeighbors() {
        renderFace(faceFront, slides[currentIndex]);
        renderFace(faceRight, slides[mod(currentIndex + 1, slides.length)]);
        renderFace(faceLeft, slides[mod(currentIndex - 1, slides.length)]);
    }

    function baseTransform() {
        return 'translateZ(-' + currentDepth + 'px)';
    }

    function resetStage() {
        stage.classList.add('no-transition');
        stage.style.transform = baseTransform() + ' rotateY(0deg)';
        void stage.offsetHeight; // fuerza reflow para que la próxima animación sí transicione
        stage.classList.remove('no-transition');
    }

    function step(direction) {
        if (isAnimating || slides.length < 2) {
            return;
        }

        currentIndex = mod(currentIndex + direction, slides.length);

        if (reducedMotion) {
            renderNeighbors();
            updateIndicators();
            return;
        }

        isAnimating = true;
        stage.style.transform = baseTransform() + ' rotateY(' + (direction > 0 ? -90 : 90) + 'deg)';

        var onEnd = function (e) {
            if (e.target !== stage) {
                return;
            }
            stage.removeEventListener('transitionend', onEnd);
            renderNeighbors();
            resetStage();
            updateIndicators();
            isAnimating = false;
        };
        stage.addEventListener('transitionend', onEnd);
    }

    function goTo(targetIndex) {
        if (isAnimating || targetIndex === currentIndex || targetIndex < 0 || targetIndex >= slides.length) {
            return;
        }
        var forward = mod(targetIndex - currentIndex, slides.length);
        var backward = mod(currentIndex - targetIndex, slides.length);
        var direction = forward <= backward ? 1 : -1;
        var stepsLeft = Math.min(forward, backward);

        (function runStep() {
            if (stepsLeft <= 0) {
                return;
            }
            stepsLeft--;
            step(direction);
            var waitForIt = setInterval(function () {
                if (!isAnimating) {
                    clearInterval(waitForIt);
                    runStep();
                }
            }, 80);
        })();
    }

    function resetAutoplay() {
        if (autoplayTimer) {
            clearInterval(autoplayTimer);
        }
        if (slides.length > 1) {
            autoplayTimer = setInterval(function () {
                step(1);
            }, AUTOPLAY_DELAY);
        }
    }

    function syncDepth() {
        currentDepth = container.getBoundingClientRect().width / 2;
        container.style.setProperty('--cube-depth', currentDepth + 'px');
        if (!isAnimating) {
            resetStage();
        }
    }

    renderNeighbors();
    updateIndicators();
    syncDepth();

    if ('ResizeObserver' in window) {
        new ResizeObserver(syncDepth).observe(container);
    } else {
        window.addEventListener('resize', syncDepth);
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            step(1);
            resetAutoplay();
        });
    }
    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            step(-1);
            resetAutoplay();
        });
    }
    if (indicatorsWrap) {
        indicatorsWrap.querySelectorAll('button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                goTo(parseInt(btn.dataset.index, 10));
                resetAutoplay();
            });
        });
    }

    resetAutoplay();
}
