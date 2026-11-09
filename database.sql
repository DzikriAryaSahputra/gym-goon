CREATE TABLE `members` (
  `id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `pin` varchar(255) NOT NULL,
  `points` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `price_normal` decimal(10,2) NOT NULL,
  `price_member` decimal(10,2) NOT NULL,
  `points_reward` int(11) DEFAULT 0,
  `points_cost` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `packages` (`name`, `duration_days`, `price_normal`, `price_member`, `points_reward`, `points_cost`) VALUES
('Paket 1 Bulan', 30, 250000.00, 200000.00, 10, 100),
('Paket 3 Bulan', 90, 700000.00, 550000.00, 35, 280),
('Paket 6 Bulan', 180, 1300000.00, 1000000.00, 75, 550),
('Paket 1 Tahun', 365, 2400000.00, 1800000.00, 160, 1000);

-- Table for admins (since it's only 1 admin, we could just hardcode it or use a simple table)
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin password is 'admin123' (hashed)
INSERT INTO `admins` (`username`, `password`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
