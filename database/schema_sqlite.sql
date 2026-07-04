-- Esquema SQLite usado SOLO para desarrollo local (ver includes/config.local.php).
-- El despliegue real en cPanel usa database/schema.sql (MySQL).

CREATE TABLE admins (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT
);

CREATE TABLE schedule (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  day_of_week INTEGER NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  program_name VARCHAR(150) NOT NULL,
  host_name VARCHAR(150),
  image VARCHAR(255),
  sort_order INTEGER DEFAULT 0
);

CREATE TABLE slides (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  image VARCHAR(255) NOT NULL,
  title VARCHAR(150),
  subtitle VARCHAR(255),
  link_url VARCHAR(255),
  sort_order INTEGER DEFAULT 0
);

-- Usuario: admin | Contraseña: changeme123
INSERT INTO admins (username, password) VALUES
('admin', '$2y$10$THFVBXTGMTadIjKC8qORpu4lrlLRKC0ZFKUu8PDCCZRUuosubQQ7a');

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'VozStation'),
('tagline', 'La radio que te acompaña todo el día'),
('description', 'Música, noticias y entretenimiento las 24 horas. Sintoniza VozStation en vivo desde cualquier lugar.'),
('about_text', 'VozStation es una radio online dedicada a llevar la mejor música y contenido a nuestra audiencia. Contamos con programación variada, locutores en vivo y la mejor selección musical para acompañarte en tu día a día.'),
('logo', 'assets/img/logo.png'),
('stream_url', 'https://stream.zeno.fm/f3wvbbqmdg8uv'),
('stream_format', 'mp3'),
('video_stream_url', ''),
('ad_banner_text', 'Impulsa tu marca en nuestra señal en vivo y llega a miles de oyentes cada día.'),
('phone', '+593 98 261 1896'),
('whatsapp', '593982611896'),
('email', 'contacto@vozstation.com'),
('address', 'Calle Cutuchi y Vía Patamarca Uncovía, Cuenca, Ecuador'),
('facebook', 'https://www.facebook.com/share/1JcATyGafz/'),
('instagram', 'https://www.instagram.com/vozstation?igsh=MWx2aGR6YXlieHk2NQ=='),
('twitter', ''),
('youtube', ''),
('tiktok', 'https://www.tiktok.com/@voz.station.radio?_r=1&_t=ZS-97iNoXSvZJ9'),
('primary_color', '#e50914'),
('secondary_color', '#1e6fe8');

INSERT INTO schedule (day_of_week, start_time, end_time, program_name, host_name, sort_order) VALUES
(1, '06:00:00', '09:00:00', 'Despertar VozStation', 'DJ Ana Torres', 1),
(1, '09:00:00', '12:00:00', 'Ritmo Matutino', 'Carlos Pérez', 2),
(1, '17:00:00', '20:00:00', 'Hora Pico', 'Mateo Rivas', 3),
(2, '06:00:00', '09:00:00', 'Despertar VozStation', 'DJ Ana Torres', 1),
(2, '09:00:00', '12:00:00', 'Ritmo Matutino', 'Carlos Pérez', 2),
(2, '17:00:00', '20:00:00', 'Hora Pico', 'Mateo Rivas', 3),
(3, '06:00:00', '09:00:00', 'Despertar VozStation', 'DJ Ana Torres', 1),
(3, '09:00:00', '12:00:00', 'Ritmo Matutino', 'Carlos Pérez', 2),
(3, '17:00:00', '20:00:00', 'Hora Pico', 'Mateo Rivas', 3),
(4, '06:00:00', '09:00:00', 'Despertar VozStation', 'DJ Ana Torres', 1),
(4, '09:00:00', '12:00:00', 'Ritmo Matutino', 'Carlos Pérez', 2),
(4, '17:00:00', '20:00:00', 'Hora Pico', 'Mateo Rivas', 3),
(5, '06:00:00', '09:00:00', 'Despertar VozStation', 'DJ Ana Torres', 1),
(5, '09:00:00', '12:00:00', 'Ritmo Matutino', 'Carlos Pérez', 2),
(5, '20:00:00', '23:00:00', 'Noche de Éxitos', 'Valeria Soto', 3),
(6, '10:00:00', '13:00:00', 'Sábado Activo', 'DJ Ana Torres', 1),
(6, '20:00:00', '23:59:00', 'Mix Nocturno', 'Mateo Rivas', 2),
(7, '10:00:00', '13:00:00', 'Domingo Familiar', 'Carlos Pérez', 1);
