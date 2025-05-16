-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2025 at 06:24 PM
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
-- Database: `milk_dist_database2`
--

-- --------------------------------------------------------

--
-- Table structure for table `seller_month_revenue`
--

CREATE TABLE `seller_month_revenue` (
  `Smr_id` int(11) NOT NULL,
  `Seller_id` int(11) DEFAULT NULL,
  `Month` varchar(20) DEFAULT NULL,
  `Total_liter` decimal(10,2) DEFAULT NULL,
  `Total_price` decimal(10,2) DEFAULT NULL,
  `Payment_status` varchar(50) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `Method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_payment`
--

CREATE TABLE `seller_payment` (
  `S_payment_id` int(11) NOT NULL,
  `User_report_id` int(11) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `Amount_collected` decimal(10,2) DEFAULT NULL,
  `Payment_status` varchar(50) DEFAULT NULL,
  `Payment_date` date DEFAULT NULL,
  `Method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_address`
--

CREATE TABLE `tbl_address` (
  `Address_id` int(11) NOT NULL,
  `Address` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_address`
--

INSERT INTO `tbl_address` (`Address_id`, `Address`) VALUES
(1, 'kadi');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_admin`
--

CREATE TABLE `tbl_admin` (
  `Admin_id` int(11) NOT NULL,
  `Contact` int(10) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_admin`
--

INSERT INTO `tbl_admin` (`Admin_id`, `Contact`, `Password`) VALUES
(1, 1234567890, '$2y$10$b7Q/IRhL8z3RJUIpnyfVC.l9puapShguza0bf0SBnTXlbzILKNo6W');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customer`
--

CREATE TABLE `tbl_customer` (
  `Customer_id` int(11) NOT NULL,
  `Name` varchar(40) DEFAULT NULL,
  `Contact` bigint(11) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `Address_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_customer`
--

INSERT INTO `tbl_customer` (`Customer_id`, `Name`, `Contact`, `Password`, `Price`, `Date`, `Address_id`) VALUES
(8, 'Sanjay', 6356753673, '$2y$10$JJaPLET1rtPIwtwwWqkUr.JX3UfkZP8A1MoEGesqaOWTGjCXz4IlO', 66.00, '2025-05-04', 1),
(9, 'Sanjay', 1234567890, '$2y$10$HzUFL35KyySd3t8Q0Zp0LuueR/wscsB5vbNy8EnOL19Z4324E/1cO', 10.00, '2025-05-09', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_milk_assignment`
--

CREATE TABLE `tbl_milk_assignment` (
  `Assignment_id` int(11) NOT NULL,
  `Seller_id` int(11) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `Assigned_quantity` decimal(10,2) DEFAULT NULL,
  `Remaining_quantity` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_milk_delivery`
--

CREATE TABLE `tbl_milk_delivery` (
  `Delivery_id` int(11) NOT NULL,
  `Seller_id` int(11) DEFAULT NULL,
  `Customer_id` int(11) DEFAULT NULL,
  `DateTime` datetime DEFAULT NULL,
  `Quantity` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_seller`
--

CREATE TABLE `tbl_seller` (
  `Seller_id` int(11) NOT NULL,
  `Name` varchar(30) DEFAULT NULL,
  `Contact` bigint(10) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `Vehicle_no` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_month_report`
--

CREATE TABLE `user_month_report` (
  `User_report_id` int(11) NOT NULL,
  `Customer_id` int(11) DEFAULT NULL,
  `Month` varchar(20) DEFAULT NULL,
  `Total_liter` decimal(10,2) DEFAULT NULL,
  `Total_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `seller_month_revenue`
--
ALTER TABLE `seller_month_revenue`
  ADD PRIMARY KEY (`Smr_id`),
  ADD KEY `Seller_id` (`Seller_id`);

--
-- Indexes for table `seller_payment`
--
ALTER TABLE `seller_payment`
  ADD PRIMARY KEY (`S_payment_id`),
  ADD KEY `User_report_id` (`User_report_id`);

--
-- Indexes for table `tbl_address`
--
ALTER TABLE `tbl_address`
  ADD PRIMARY KEY (`Address_id`);

--
-- Indexes for table `tbl_admin`
--
ALTER TABLE `tbl_admin`
  ADD PRIMARY KEY (`Admin_id`);

--
-- Indexes for table `tbl_customer`
--
ALTER TABLE `tbl_customer`
  ADD PRIMARY KEY (`Customer_id`),
  ADD KEY `Address_id` (`Address_id`);

--
-- Indexes for table `tbl_milk_assignment`
--
ALTER TABLE `tbl_milk_assignment`
  ADD PRIMARY KEY (`Assignment_id`),
  ADD KEY `Seller_id` (`Seller_id`);

--
-- Indexes for table `tbl_milk_delivery`
--
ALTER TABLE `tbl_milk_delivery`
  ADD PRIMARY KEY (`Delivery_id`),
  ADD KEY `Seller_id` (`Seller_id`),
  ADD KEY `Customer_id` (`Customer_id`);

--
-- Indexes for table `tbl_seller`
--
ALTER TABLE `tbl_seller`
  ADD PRIMARY KEY (`Seller_id`);

--
-- Indexes for table `user_month_report`
--
ALTER TABLE `user_month_report`
  ADD PRIMARY KEY (`User_report_id`),
  ADD KEY `Customer_id` (`Customer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `seller_month_revenue`
--
ALTER TABLE `seller_month_revenue`
  MODIFY `Smr_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_payment`
--
ALTER TABLE `seller_payment`
  MODIFY `S_payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_address`
--
ALTER TABLE `tbl_address`
  MODIFY `Address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_admin`
--
ALTER TABLE `tbl_admin`
  MODIFY `Admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_customer`
--
ALTER TABLE `tbl_customer`
  MODIFY `Customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tbl_milk_assignment`
--
ALTER TABLE `tbl_milk_assignment`
  MODIFY `Assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_milk_delivery`
--
ALTER TABLE `tbl_milk_delivery`
  MODIFY `Delivery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tbl_seller`
--
ALTER TABLE `tbl_seller`
  MODIFY `Seller_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_month_report`
--
ALTER TABLE `user_month_report`
  MODIFY `User_report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `seller_month_revenue`
--
ALTER TABLE `seller_month_revenue`
  ADD CONSTRAINT `seller_month_revenue_ibfk_1` FOREIGN KEY (`Seller_id`) REFERENCES `tbl_seller` (`Seller_id`);

--
-- Constraints for table `seller_payment`
--
ALTER TABLE `seller_payment`
  ADD CONSTRAINT `seller_payment_ibfk_1` FOREIGN KEY (`User_report_id`) REFERENCES `user_month_report` (`User_report_id`);

--
-- Constraints for table `tbl_customer`
--
ALTER TABLE `tbl_customer`
  ADD CONSTRAINT `tbl_customer_ibfk_1` FOREIGN KEY (`Address_id`) REFERENCES `tbl_address` (`Address_id`) ON DELETE SET NULL;

--
-- Constraints for table `tbl_milk_assignment`
--
ALTER TABLE `tbl_milk_assignment`
  ADD CONSTRAINT `tbl_milk_assignment_ibfk_1` FOREIGN KEY (`Seller_id`) REFERENCES `tbl_seller` (`Seller_id`);

--
-- Constraints for table `tbl_milk_delivery`
--
ALTER TABLE `tbl_milk_delivery`
  ADD CONSTRAINT `tbl_milk_delivery_ibfk_1` FOREIGN KEY (`Seller_id`) REFERENCES `tbl_seller` (`Seller_id`),
  ADD CONSTRAINT `tbl_milk_delivery_ibfk_2` FOREIGN KEY (`Customer_id`) REFERENCES `tbl_customer` (`Customer_id`);

--
-- Constraints for table `user_month_report`
--
ALTER TABLE `user_month_report`
  ADD CONSTRAINT `user_month_report_ibfk_1` FOREIGN KEY (`Customer_id`) REFERENCES `tbl_customer` (`Customer_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
