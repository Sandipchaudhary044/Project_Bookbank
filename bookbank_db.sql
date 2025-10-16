-- Table for users
CREATE TABLE `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `is_admin` BOOLEAN NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for book donations
CREATE TABLE `donations` (
  `donation_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `book_title` VARCHAR(255) NOT NULL,
  `author` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `image_path` VARCHAR(255) DEFAULT 'assets/images/default_book.png',
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `is_borrowed` BOOLEAN NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for borrow requests
CREATE TABLE `borrow_requests` (
  `request_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `donation_id` INT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'returned') NOT NULL DEFAULT 'pending',
  `request_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `return_date` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
  FOREIGN KEY (`donation_id`) REFERENCES `donations`(`donation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create a default admin user (change password)
-- The password is 'admin123'
INSERT INTO `users` (`username`, `email`, `password_hash`, `is_admin`) VALUES
('admin', 'admin@bookbank.com', '$2y$10$wS2AbHkEaJg2mQ5P0xG/7.H.o3b4Yg5p3qYI.bC4wZ.p0F.j9u8nO', 1);


-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 20, 2025 at 12:29 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bookbank_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `borrow_requests`
--

CREATE TABLE `borrow_requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `donation_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected','returned','return_requested') NOT NULL DEFAULT 'pending',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `return_date` timestamp NULL DEFAULT NULL,
  `return_requested` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `borrow_requests`
--

INSERT INTO `borrow_requests` (`request_id`, `user_id`, `donation_id`, `status`, `request_date`, `return_date`, `return_requested`) VALUES
(1, 4, 2, '', '2025-07-19 18:33:14', NULL, 0),
(2, 4, 3, '', '2025-07-20 07:51:33', NULL, 0),
(4, 4, 1, 'rejected', '2025-07-20 09:21:04', NULL, 0),
(5, 6, 3, '', '2025-07-20 09:23:42', NULL, 0),
(6, 5, 1, 'returned', '2025-07-20 09:29:19', '2025-07-20 09:30:31', 0),
(7, 6, 1, 'returned', '2025-07-20 09:50:22', '2025-07-20 10:18:44', 0);

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `created_at`) VALUES
(1, 'Bicky Patel', 'bicky.795424@trmc.tu.edu.np', 'hello', '2025-07-19 16:18:42'),
(2, 'Bicky Patel', '1230bicky@gmail.com', 'kaha bata line kitab', '2025-07-20 06:13:36');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `donation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT 'assets/images/default_book.png',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `is_borrowed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cover_image` varchar(255) DEFAULT 'assets/images/default_book.png',
  `category` varchar(100) NOT NULL,
  `book_condition` varchar(50) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `contact` varchar(20) NOT NULL,
  `address` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`donation_id`, `user_id`, `book_title`, `author`, `description`, `image_path`, `status`, `is_borrowed`, `created_at`, `cover_image`, `category`, `book_condition`, `isbn`, `contact`, `address`) VALUES
(1, 4, 'Old is Gold', 'Asmita\'s', 'SEE', 'assets/images/default_book.png', 'approved', 0, '2025-07-19 12:51:40', '687b94dc880b8_oldisgold.jpg', '', '', NULL, '', ''),
(2, 5, 'You can win', 'Shiv Khera', 'A practical, common-sense guide that will help you:\r\n\r\nBuild confidence by mastering the seven steps to positive thinking', 'assets/images/default_book.png', 'approved', 1, '2025-07-19 18:11:47', '687bdfe315775_You can win.jpeg', '', '', NULL, '', ''),
(3, 4, 'Excellent science', 'Dr. Rajendra Pd. Koirala', 'Asmita\'s', 'assets/images/default_book.png', 'approved', 1, '2025-07-20 06:51:06', '687c91da4b539_science.jpeg', 'SEE', 'Good', '', '9807298522', 'maisthan Birgunj-8');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `contact` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `profession` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `is_admin`, `created_at`, `contact`, `address`, `profession`) VALUES
(3, 'admin', 'admin@bookbank.com', '$2y$10$WmI8Xld71JEf.W.0xFWUnOuN3RNFo4.Ez18PoMu9ax/AU2QDnnKsy', 1, '2025-07-19 12:34:57', '', '', ''),
(4, 'bicky patel', '1230bicky@gmail.com', '$2y$10$xwoXi1s48qQ5xsDLyFv..uhfyG8Rq3iiiaWIXGg.fi36bTXH8Cecm', 0, '2025-07-19 12:39:13', '', '', ''),
(5, 'ram', 'xaviswilcox35@gmail.com', '$2y$10$VoeE2Hv4uUkpOZD5PYBNhOgLK9yWkAzVMIF5Rnjl2.f9fDFF2YZR2', 0, '2025-07-19 18:06:58', '', '', ''),
(6, 'sandip chudhary', 'sandip@123.gmail.com', '$2y$10$TIcke6UhGXETANAGMR6ezeJMK80ji22uzWKQi9PAhTnGihRjjf4IO', 0, '2025-07-20 08:54:49', '9820202020', '6 no. gate birgunj parsa tharu chatrabas', 'student');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `borrow_requests`
--
ALTER TABLE `borrow_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `donation_id` (`donation_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`donation_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `borrow_requests`
--
ALTER TABLE `borrow_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `donation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrow_requests`
--
ALTER TABLE `borrow_requests`
  ADD CONSTRAINT `borrow_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `borrow_requests_ibfk_2` FOREIGN KEY (`donation_id`) REFERENCES `donations` (`donation_id`);

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
ALTER TABLE users 
ADD COLUMN name VARCHAR(100),
ADD COLUMN avatar VARCHAR(255);
ALTER TABLE users
MODIFY avatar VARCHAR(255) DEFAULT NULL;

ALTER TABLE donations
ADD COLUMN quantity INT DEFAULT 1,
ADD COLUMN available_quantity INT DEFAULT 1;
2082-04-08
CREATE TABLE remember_me_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
 ALTER TABLE remember_me_tokens CHANGE expires expires_at DATETIME NOT NULL;

