-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 13/08/2025 às 05:11
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `ocorrencia`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `registros_ocorrencias`
--

CREATE TABLE `registros_ocorrencias` (
  `id` int(11) NOT NULL,
  `data_ocorrencia` date NOT NULL,
  `horario_ocorrencia` time NOT NULL,
  `workid` int(8) DEFAULT NULL,
  `motorista_atual` int(5) DEFAULT NULL,
  `linha` varchar(10) DEFAULT NULL,
  `carro_atual` int(4) DEFAULT NULL,
  `ocorrencia` varchar(100) DEFAULT NULL,
  `incidente` varchar(100) DEFAULT NULL,
  `socorro` tinyint(1) NOT NULL DEFAULT 0,
  `horario_linha` time DEFAULT NULL,
  `terminal` varchar(50) DEFAULT NULL,
  `carro_pos` int(4) DEFAULT NULL,
  `monitor` varchar(50) DEFAULT NULL,
  `fiscal` varchar(50) DEFAULT NULL,
  `observacao` varchar(250) DEFAULT NULL,
  `timestamp_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `registros_ocorrencias`
--
ALTER TABLE `registros_ocorrencias`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `registros_ocorrencias`
--
ALTER TABLE `registros_ocorrencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
