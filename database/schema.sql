-- VozStation - Radio Online
-- Esquema de base de datos para cPanel / MySQL
-- Importar este archivo desde phpMyAdmin en tu cPanel

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(100) PRIMARY KEY,
  `setting_value` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `schedule` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `day_of_week` TINYINT NOT NULL COMMENT '1=Lunes ... 7=Domingo',
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `program_name` VARCHAR(150) NOT NULL,
  `host_name` VARCHAR(150) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `slides` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `image` VARCHAR(255) NOT NULL,
  `title` VARCHAR(150) DEFAULT NULL,
  `subtitle` VARCHAR(255) DEFAULT NULL,
  `link_url` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tickets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `buyer_name` VARCHAR(150) NOT NULL,
  `buyer_email` VARCHAR(150) DEFAULT NULL,
  `buyer_phone` VARCHAR(50) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `payment_proof` VARCHAR(255) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pendiente',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuario administrador por defecto
-- Usuario: admin | Contraseña: changeme123
-- IMPORTANTE: cambia esta contraseña apenas ingreses al panel
INSERT INTO `admins` (`username`, `password`) VALUES
('admin', '$2y$10$THFVBXTGMTadIjKC8qORpu4lrlLRKC0ZFKUu8PDCCZRUuosubQQ7a');

-- Configuración inicial del sitio
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
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
('secondary_color', '#1e6fe8'),
('tickets_enabled', ''),
('tickets_event_name', ''),
('tickets_event_date', ''),
('tickets_price', '0'),
('tickets_description', ''),
('tickets_event_image', '');

-- Programación de ejemplo
INSERT INTO `schedule` (`day_of_week`, `start_time`, `end_time`, `program_name`, `host_name`, `sort_order`) VALUES
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
