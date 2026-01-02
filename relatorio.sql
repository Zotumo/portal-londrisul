-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 02/01/2026 às 12:32
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
-- Banco de dados: `relatorio`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `cadastros_locais`
--

CREATE TABLE `cadastros_locais` (
  `id` int(11) NOT NULL,
  `company_code` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `imagem_path` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'ativo',
  `coordenadas` varchar(100) DEFAULT NULL,
  `mostrar_ponto` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cadastros_locais`
--

INSERT INTO `cadastros_locais` (`id`, `company_code`, `name`, `data_importacao`, `imagem_path`, `descricao`, `status`, `coordenadas`, `mostrar_ponto`) VALUES
(1, 'CATED-SP', 'Avenida São Paulo, 344', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.311794, -51.160282', 1),
(2, 'DETRAN', 'Rua Suindara, 356-400', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.291791, -51.143082', 1),
(3, '113-OUT', 'Avenida Custódio Venâncio Ribeiro, 250', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.306378, -51.112010', 1),
(4, '201.S-IN', 'Avenida Salgado Filho, 260', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.329607, -51.142996', 1),
(5, '418.C-PC', 'Estrada Chácaras Primavera', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.243352, -51.120653', 1),
(6, '310.B-PC', 'Avenida Brasília, 1075', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.292551, -51.185379', 1),
(7, '311.C-IN', 'Rua Cajarana, 40-82', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.293073, -51.199961', 1),
(8, '311.R-PC', 'Rua Oséias Furtoso, 656', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.278237, -51.194000', 1),
(9, '419.A-IN', 'Rua da Águia Imperial, 111', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.280849, -51.155687', 1),
(10, '095.ASS-IN', 'Rua Assunção, 189', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.334030, -51.168310', 1),
(11, 'N455', 'BR-369', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.292659, -51.095811', 1),
(12, 'LD9', 'Sub-Prefeitura de Lerroville', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.691216, -51.091580', 1),
(13, '311.M-IN', 'Rua das Castanheiras, 608-724', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.294319, -51.194983', 1),
(14, '209.EC-PC', 'Condomínio Estância Cabral', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.368491, -51.259407', 1),
(15, 'ESPER-TVX', 'Espera T. Vivi', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.261156, -51.172240', 1),
(16, 'LD3', 'Estação Provisória Acapulco', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.359915, -51.157191', 1),
(17, 'T.GUERRA.B', 'Avenida Salgado Filho - Tiro de Guerra', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.335010, -51.134618', 1),
(18, 'EST-BENJAM', 'Estacionamento Rua Benjamim', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308741, -51.159621', 1),
(19, 'TVX-EST', 'Est. Frota Reserva T. Vivi Xavier', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.260981, -51.172398', 1),
(20, '201.S-OUT', 'Rua Capitão João Busse, 193-233', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.329840, -51.144839', 1),
(21, '201.T-PC', 'Rua Lázaro Zamenhof, 1112-1174', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.334035, -51.133205', 1),
(22, '202.IV-IN', 'Travessa Lisboa, 1-85', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.351702, -51.146449', 1),
(23, '203.G-IN', 'Rua do Trevo-Branco, 139', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.362657, -51.144594', 1),
(24, '208-IN', 'Rua Paranaguá, 2109 Oposto', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.324000, -51.169236', 1),
(25, '220.G-IN', 'Avenida Gil de Abreu e Souza, 5000', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.339630, -51.215500', 1),
(26, '314.A-IN', 'Avenida Arthur Thomas, 1937-2077', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.312898, -51.206755', 1),
(27, 'TCPI-EST-T', 'Estacionamento Piso Inferior - Terminal Central', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.307761, -51.160468', 1),
(28, 'TRO', 'Terminal Região Oeste', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.297196, -51.187180', 1),
(29, 'G.MARAV', 'Garagem Maravilha', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.463432, -50.999330', 1),
(30, 'G.GUARAV', 'Garagem Guaravera', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.605756, -51.186436', 1),
(31, 'G.TAMARAN', 'Garagem Tamarana', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.726615, -51.097013', 1),
(32, 'N2155', 'Rua Maria Serrato Batista, 234-284', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.327010, -51.228174', 1),
(33, 'LD9.b', 'Sub-prefeitura de Lerroville', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.691168, -51.091545', 1),
(34, 'TVX', 'Terminal Vivi Xavier', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.260691, -51.172699', 1),
(35, 'N2251', 'Rua Helmut Baer', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.247480, -51.164497', 1),
(36, 'MUFFATO-IN', 'Avenida Duque de Caxias, 1176-1298', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.330428, -51.152391', 1),
(37, '209.SP-PC', 'Estância Santa Paula', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.359947, -51.243646', 1),
(38, '214-PC', 'Rua Doutor Luís Aranda, 105-143', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.393275, -51.139755', 1),
(39, 'TA-C', 'Terminal Acapulco - pista C', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.360804, -51.155327', 1),
(40, 'N2329', 'Rua Gessi Eugênio da Silva, 620', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.252835, -51.143610', 1),
(41, 'TC-F-EST', 'Estacionamento TC pista F', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308133, -51.160712', 1),
(42, 'N2347', 'Rua Gessi Eugênio da Silva, 603-655', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.252941, -51.143691', 1),
(43, 'TC-J-EST', 'Estacionamento TC pista J', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308547, -51.160772', 1),
(44, 'N2360', 'Rua Francisco de Assis F Ruiz, 630-724', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.252084, -51.140840', 1),
(45, 'N2385', 'Avenida Saul Elkind, 564-604', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.257941, -51.142897', 1),
(46, '411-PC', 'Rua da Águia-Imperial, 66', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.273571, -51.156657', 1),
(47, 'TOVER', 'Estação Provisória Ouro Verde', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.283412, -51.174889', 1),
(48, 'N2629', 'Rua Emílio Scholze, 230', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.270415, -51.163492', 1),
(49, 'N2710', 'Rua Antônio C Ramea, 2-70', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.246721, -51.170870', 1),
(50, '203.R-IN', 'Rua Vitória-Régia, 76', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.358327, -51.148964', 1),
(51, '204-OUT', 'Rua Ivone Freitas Lopes, 313-363', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.370928, -51.144031', 1),
(52, '210.BV-OUT', 'Rua dos Cozinheiros, 268', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.381744, -51.129255', 1),
(53, '213.VR-IN', 'Avenida Juscelino Kubitscheck, 2426-2550', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.320033, -51.165283', 1),
(54, 'TA-ESTAC', 'Est. Frota Reserva T. Acapulco', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.360044, -51.155254', 1),
(55, 'TCPI-EST-L', 'Estacionamento Piso inferior - Terminal Central', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.307751, -51.160561', 1),
(56, 'TCPS-EST-T', 'Estacionamento Piso Superior Terminal Central', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308489, -51.161332', 1),
(57, '110.C-OUT', 'Rua Rosângela Cunha Redondo, 136', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.280065, -51.124910', 1),
(58, '112-OUT', 'Avenida dos Pioneiros, 1628', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.311249, -51.127412', 1),
(59, '112.S-IN', 'Avenida Jamil Scaff, 1597-1703', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.316714, -51.114923', 1),
(60, '113.L-PC', 'Avenida Theodoro Victorelli, 623', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.311848, -51.143259', 1),
(61, '121-IN', 'Rua José Gonçalves Viana Filho, 2-16', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.285642, -51.108235', 1),
(62, '200-IN', 'Rua Uruguai, 1152', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.320378, -51.152615', 1),
(63, '214.B-IN', 'Rua Doutor Gilnei Carneiro Leal, 395-435', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.389785, -51.137418', 1),
(64, '260.C-PC', 'Estrada Piriquitos - Vale Fértil', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.363709, -51.058922', 1),
(65, '260.M-IN', 'Rua Aldo Siena', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.460663, -51.001288', 1),
(66, '280.T-PC', 'PR-532, Distrito de Tauaruna', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.535051, -51.191290', 1),
(67, '290-PC', 'Rua do Café, Distrito de Guaravera', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.611125, -51.186174', 1),
(68, '305.A-PC', 'Colégio Aplicação - UEL', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.325310, -51.207118', 1),
(69, '309.B-IN', 'Avenida Esperanto, 205 - oposto', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.293121, -51.219936', 1),
(70, '202.VZ-IN', 'Avenida Europa, 144', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.349412, -51.144421', 1),
(71, '308.G-OUT', 'Avenida Serra da Esperança, 455', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.301113, -51.197553', 1),
(72, '308.G-IN', 'Rua Serra da Graciosa, 200', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.304624, -51.201025', 1),
(73, '308.G-PC', 'Rua Serra dos Pirineus, 753', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.301485, -51.205628', 1),
(74, '308.P-PC', 'Rua Serra dos Pirineus, 618-668', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.301527, -51.205889', 1),
(75, '211.RE-PC', 'PR-538, 500 - Patrimônio Regina, 500', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.411184, -51.223482', 1),
(76, '108-IN', 'Avenida Santos Dumont, 1160-1308', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.323478, -51.142712', 1),
(77, '110.C-IN', 'Rua João Munhoz Moreno, 258-306', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.278671, -51.123179', 1),
(78, '110.N-IN', 'Avenida Nova Londrina, 54', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.287341, -51.125760', 1),
(79, '112.L-PC', 'Rua Gabriel Matokanovic, 165-267', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.316703, -51.112491', 1),
(80, '406-OUTA', 'Rec. 406 via Luiz de Sá', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.261664, -51.140933', 1),
(81, '407.S-PC', 'Avenida Saul Elkind, 585', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.258247, -51.143047', 1),
(82, '414.A-PC', 'Rua Segisfredo Gonçalves Mendes, 69', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.272772, -51.192821', 1),
(83, '414.B-PC', 'Avenida Clárice de Lima Castro, 441-495', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.274493, -51.190267', 1),
(84, 'TOVER-C', 'Terminal Ouro Verde - Pista C', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.281609, -51.170666', 1),
(85, 'UBS.SAB-CE', 'Avenida Arthur Thomas, 2211-2319', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.315253, -51.209272', 1),
(86, 'TC-B', 'Terminal Central pista B', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308034, -51.161287', 1),
(87, 'T.GUERRA.C', 'Avenida Salgado Filho - Tiro de Guerra', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.334778, -51.134490', 1),
(88, 'UNOPAR-T', 'Rua Tietê, 1239', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.295708, -51.166135', 1),
(89, '201.S-PC', 'Rua Matilde Yoshiko Honda, 2-52', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.343514, -51.125496', 1),
(90, '103.C-PC', 'Rua Santa Eliza, 170', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.309607, -51.147616', 1),
(91, '104-M-OUT', 'Rua Carmela Dutra, 340', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.315996, -51.138951', 1),
(92, '106-IN', 'Avenida São João, 1782', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.318996, -51.130989', 1),
(93, 'ILECE_E2', 'ILECE - Avenida Presidente Euríco Gáspar Dutra, 80', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.362877, -51.152100', 1),
(94, '222-IN', 'Avenida Paris, 220', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.349451, -51.142146', 1),
(95, '260.U-PC', 'PR-218, ponto Usina', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.382279, -51.080421', 1),
(96, '275.R-PC', 'Vila Rural de Paiquerê', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.514539, -51.070432', 1),
(97, '295.B-IN', 'Água do Beijo, divisa com Tamarana', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.714387, -51.095426', 1),
(98, '295-IN', 'Rua Eloy Nogueira', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.692143, -51.093616', 1),
(99, '305.R-IN', 'Rua Constantino Pialarissi, 226-406', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.327377, -51.194187', 1),
(100, '425.V-OUT', 'Rua André Buck, 147', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.261760, -51.198289', 1),
(101, '430-OUT', 'Rua Celeste Contó Moro, 247-283', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.268572, -51.199535', 1),
(102, '706.B-IN', 'Rua Luís Brugin, 24-132', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.257624, -51.150057', 1),
(103, '106.A-PC', 'Rua Leontina da Conceição Gaion, 205-255', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.329885, -51.106816', 1),
(104, 'TC-A', 'Terminal Central pista A', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.307956, -51.161123', 1),
(105, 'EXPO-LD', 'Avenida Tiradentes, 6027-6133', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.288364, -51.224771', 1),
(106, '308.P-IN', 'Rua Serra dos Parecis, 162-220', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.301867, -51.197680', 1),
(107, '308.P-OUT', 'Avenida Serra da Esperança, 422-520', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.305611, -51.202391', 1),
(108, '309.P-IN', 'Avenida Jockey Club, 403-547', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.298788, -51.211560', 1),
(109, '311.C-OUT', 'Rua Ruy Virmond Carnascialli, 583-645', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.292752, -51.198448', 1),
(110, '314-IN', 'Rua Aparecida Bernardes Caetano, 120-240', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.321728, -51.226609', 1),
(111, '406.A-IN', 'Rua Francisco García de Campos, 2-60', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.259918, -51.139566', 1),
(112, '307-OUT', 'Rua do Basquetebol, 85', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.320258, -51.224228', 1),
(113, '309.A-IN', 'Rua Joana Rodrigues Jondral, 2-418', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.298551, -51.217900', 1),
(114, '210-PC', 'Rua Dezenove de Abril, 1-153', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.386356, -51.129682', 1),
(115, '211.RO-PC', 'Residencial Euro Royal', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.367051, -51.187136', 1),
(116, '211.CT-PC', 'Chácara Toca do Peixe', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.406664, -51.162131', 1),
(117, '213.AI-IN', 'Av. Ayrton Senna da Silva, 998 - Guanabara', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.335928, -51.177527', 1),
(118, '406.A-PC', 'Rua Petronilha Ribeiro Manzuti, 65', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.245698, -51.138986', 1),
(119, '407.M-PC', 'Rua Meire Cristiane Bonancea Santos', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.281012, -51.135558', 1),
(120, '407.N-IN', 'R. Dr. Newton Leopoldo Câmara, 535', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.274941, -51.142971', 1),
(121, '412.H-PC', 'Rua José Freitas dos Santos, 2-58', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.273673, -51.183956', 1),
(122, '309.T-PC', 'Avenida Tiradentes, 9260-9322', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.288105, -51.224468', 1),
(123, '202.B-IN', 'Avenida Bandeirantes, 963', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.326887, -51.157009', 1),
(124, 'TC-C-T', 'Terminal Central pista C', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308148, -51.161010', 1),
(125, 'TC-D-222', 'Terminal Central pista D', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.307957, -51.161022', 1),
(126, 'TC-E-210', 'Terminal Central pista E', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308045, -51.161023', 1),
(127, 'TC-E-203', 'Terminal Central pista E', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308045, -51.160725', 1),
(128, 'TC-E-601', 'Terminal Central pista E', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308043, -51.160457', 1),
(129, 'TC-D-202', 'Terminal Central pista D', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.307957, -51.160797', 1),
(130, 'TC-F-200L', 'Terminal Central Pista F', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308121, -51.160459', 1),
(131, 'TC-I', 'Terminal Central pista I', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308455, -51.161072', 1),
(132, '203.R-OUT', 'Avenida Guilherme de Almeida, 549', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.362022, -51.146658', 1),
(133, '204-IN', 'Rua José Luís Andrade, 170-222', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.371665, -51.141443', 1),
(134, 'TOVER-EST', 'Estacionamento Terminal Ouro Verde', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.281716, -51.171851', 1),
(135, 'G.LERROV', 'Garagem Lerroville', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.692258, -51.094372', 1),
(136, 'TC-L-111', 'Terminal Central pista L', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308623, -51.160447', 1),
(137, '205.D-OUT', 'Rua Badre Dagher, 43', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.365185, -51.155313', 1),
(138, '229-PC', 'Rodovia Celso Garcia Cid, 3570', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.330799, -51.186378', 1),
(139, '103.M-PC', 'Rua Raimunda Madalena Reberg, 1077-1087', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.300775, -51.126165', 1),
(140, '103.M-OUT', 'Rua Mangaba, 838-898', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.305254, -51.135596', 1),
(141, '103.A-OUT', 'Rua Noel Rosa, 123-419', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.304369, -51.134762', 1),
(142, '103_OUT_BA', '103.A-OUT', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.307009, -51.134514', 1),
(143, '104.T-IN', 'Rua Flor-de-Jesus, 364-426', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308986, -51.138308', 1),
(144, '106-OUT', 'Avenida São João, 1789', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.318895, -51.131074', 1),
(145, '310-PC', 'Rua das Castanheiras, 226', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.294500, -51.191849', 1),
(146, '108-PC', 'Avenida Alziro Zarur, 292-296', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.326040, -51.130307', 1),
(147, '110-PC', 'Avenida Nova Londrina, 190', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.282165, -51.129145', 1),
(148, '310.R-PC', 'Avenida Rio Branco, 599', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.296557, -51.173497', 1),
(149, '214.B-OUT', 'Rua Silvio Carlos Silva, 142', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.388094, -51.137880', 1),
(150, '214.C-OUT', 'Rua Alvizio Jarreta, 179', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.374497, -51.154058', 1),
(151, '111-IN', 'Avenida das Maritacas, 1850-1922', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.285676, -51.120930', 1),
(152, '222.U-PC', 'Avenida Paris, 736', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.351819, -51.137299', 1),
(153, '228-PC', 'Hospital Evangélico', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.326034, -51.160443', 1),
(154, '417.C-IN', 'Rua Zirbo Quintino Pontes, 182', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.259485, -51.135853', 1),
(155, '417.H-PC', 'Rua Cardeal, 326', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.265749, -51.153652', 1),
(156, '448-PC', 'Rua Eugênia Safra do Rosário', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.264041, -51.115270', 1),
(157, '420.I-PC', 'Avenida da Liberdade, 865', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.275892, -51.162898', 1),
(158, '425.A-PC', 'Avenida Saul Elkind, 2-114', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.259915, -51.138689', 1),
(159, '311.C-PC', 'Rua Oséias Furtoso, 693', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.278242, -51.194123', 1),
(160, '111-OUT', 'Avenida das Maritacas, 1901', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.285553, -51.121144', 1),
(161, '111-PC', 'Rua Pedro Antônio de Souza, 487-537', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.279611, -51.110523', 1),
(162, '311.R-IN', 'Rua Ruy Virmond Carnascialli, 584-646', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.292544, -51.198242', 1),
(163, 'TCPS-EST-L', 'Estacionamento Piso Superior Terminal Central', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308496, -51.161333', 1),
(164, '311.R-OUT', 'Rua Cajarana, 101', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.293212, -51.200012', 1),
(165, 'N1284', 'Rua dos Zeladores, 32-100', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.381973, -51.131183', 1),
(166, '312-PC', 'Rua Ildo Garcia, 310', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.275619, -51.197897', 1),
(167, '314.U-IN', 'Rua Antônio Salema, 245', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.314521, -51.208975', 1),
(168, 'TC-G', 'Terminal Central pista G', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308276, -51.160478', 1),
(169, '315-PC', 'Rua Antônio Martins Lardin, 586-648', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.335294, -51.218052', 1),
(170, '706.C-PC', 'Avenida Bandeirantes, 556', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.326090, -51.160712', 1),
(171, '280.R-PC', 'Vila Rural de Taquaruna', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.529371, -51.172675', 1),
(172, '290.R-PC', 'Vila Rural de Guaravera', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.586722, -51.193607', 1),
(173, '706.B-OUT', 'Avenida Saul Elkind, 1198-1202', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.258208, -51.149040', 1),
(174, '800.CA-IN', 'Rua Pio XII, 340', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.312065, -51.164707', 1),
(175, '305-PC', 'CEF / CECA - UEL', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.323927, -51.205755', 1),
(176, '800.CT-IN', 'Rua Pio XII, 180', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.312000, -51.163000', 1),
(177, '501.V-IN', 'Rua Lindalva Silva Basseto, 120', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.273813, -51.172468', 1),
(178, '800.H-OUT', 'Avenida Higienópolis, 2669', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.333254, -51.167817', 1),
(179, 'TRO-EST', 'Estacionamento Terminal Oeste', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.297352, -51.186639', 1),
(180, '601.M-IN', 'Avenida Duque de Caxias, 1472', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.328616, -51.153399', 1),
(181, 'TSHOP-EST', 'Estacionamento TSHOP', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.344017, -51.186362', 1),
(182, '800.Q-OUT', 'Rua Quintino Bocaiúva, 65', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.310095, -51.164415', 1),
(183, '913.U-PC', 'Estacionamento - UEL', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.324084, -51.203202', 1),
(184, '404.I-OUT', 'Início e Rec. Linha 404', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.241631, -51.156591', 1),
(185, '94-OUT', 'Avenida Europa, 50', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.348376, -51.145005', 1),
(186, '213.A2-IN', 'Av. Madre com Reservatório Sanepar', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.335786, -51.178917', 1),
(187, 'BOULEV-B', 'Shopping Boulevard sentido Rua Santa Terezinha', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.311542, -51.146040', 1),
(188, '405-OUT', 'Rua Félix Chenso, 990-1122', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.268255, -51.146853', 1),
(189, '406.A-OUT', 'Rua Francisco de Assis F Ruíz, 101-255', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.256202, -51.141059', 1),
(190, 'N2958', 'Terminal Região Oeste', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.297196, -51.187180', 1),
(191, 'N303', 'Avenida Jamil Scaff, 300-400', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.326395, -51.106577', 1),
(192, 'N3035', 'Avenida Robert Koch, 171', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.325424, -51.128141', 1),
(193, 'N376', 'Rua Aparecida Bernardes Caetano, 183', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.321770, -51.226461', 1),
(194, 'N431', 'Rua Lupicínio Rodrigues, 70-248', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.294870, -51.221137', 1),
(195, '101-PC', 'Rua Agenor Pereira da Silva, 99-237', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.281034, -51.142430', 1),
(196, 'N607', 'Rua das Goiabeiras, 18-102', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308811, -51.131825', 1),
(197, 'N811', 'Rua Gabriel Matokanovic, 166-268', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.316816, -51.112457', 1),
(198, 'NÓ.ÓRFÃO1', 'EXCLUIR', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.315382, -51.115650', 1),
(199, 'MOCIAN', 'Portal de Versalhes', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.318984, -51.204082', 1),
(200, 'N2453', 'Rua Padre Manoel da Nóbrega, 155-247', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.293594, -51.170696', 1),
(201, 'N1793', 'Rua Eloy Nogueira', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.691189, -51.091194', 1),
(202, 'TMGAV', 'Terminal Milton Gavetti', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.281499, -51.152587', 1),
(203, 'PQ.ECOL', 'Parque Ecológico', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.384274, -51.077915', 1),
(204, 'G-GUAIRACA', 'Garagem Guairacá', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.605286, -50.975735', 1),
(205, 'G.PAIQUERE', 'Garagem Paiquerê', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.528129, -51.083367', 1),
(206, 'T-IRERE', 'Terminal Irere', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.504820, -51.125724', 1),
(207, 'G-IRERE', 'Garagem Terminal Irerê', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.505261, -51.125421', 1),
(208, 'APAE', 'Rua Dom joão VI, 594 - 672', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.323630, -51.129340', 1),
(209, 'TCGLL', 'Garagem', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.293623, -51.156306', 1),
(210, '109-PC', 'Terminal Rodoviário', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308726, -51.150452', 1),
(211, 'ILECE-JK', 'ILECE - Avenida Juscelino Kubitscheck, 1834', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.318197, -51.170653', 1),
(212, 'SERCOMTEL', 'SERCOMTEL', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.313518, -51.161420', 1),
(213, '221-PC2', 'Rua Francisco Gonsales Donoso, 580', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.343847, -51.122454', 1),
(214, '231-PC1', 'Rua Francisco Gonsales Donoso, 580', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.344080, -51.122306', 1),
(215, '091-PC', 'Rua André Buck, 365', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.263684, -51.198293', 1),
(216, '095-PC', 'Rua da Cidadania, 374-436', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.385034, -51.128789', 1),
(217, 'G-BELGICA', 'Garagem Bélgica', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.355117, -51.151044', 1),
(218, 'TC-F-408T', 'Terminal Central Pista F', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308122, -51.160458', 1),
(219, 'BOULEV-C', 'Shopping Boulevard sentido Rodoviária', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.311416, -51.145659', 1),
(220, 'CASTALD-B', 'Avenida Arthur Thomas, 1162', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.306118, -51.199855', 1),
(221, 'CASTALD-C', 'Avenida Arthur Thomas, 1055-1163', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.306441, -51.199819', 1),
(222, 'ESPER-TA2', 'Espera Terminal Acapulco', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.359266, -51.155707', 1),
(223, 'ESPER-TA', 'Espera Terminal Acapulco', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.359517, -51.156342', 1),
(224, 'G.NATA2', 'Garagem Nata', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.363807, -51.059687', 1),
(225, 'G.NATA', 'Garagem Nata', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.363897, -51.059590', 1),
(226, 'GUARDA.M', 'GUARDA MIRIM', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.324837, -51.136585', 1),
(227, 'ILECE.E', 'ILECE-EURICO', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.361367, -51.152015', 1),
(228, 'N2310', 'Rua Pedro Rossato', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.241508, -51.156619', 1),
(229, 'TMGAV-EST', 'Estacionamento via pública Terminal Milton Gavetti', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.281216, -51.152481', 1),
(230, '313-IN', 'Rua Antônio Brutomesso, 180', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.283202, -51.201160', 1),
(231, '222-OUT', 'Avenida Europa, 1085', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.354109, -51.136261', 1),
(232, '202-IN.PC', 'Rua Milão, 828', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.354302, -51.138602', 1),
(233, '202-OUT', 'Rua Deputado Agnaldo Pereira Lima, 1-39', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.353578, -51.142390', 1),
(234, '202-PC', 'Rua Madre Enriqueta Dominici, 911', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.358158, -51.143580', 1),
(235, '205.1-IN', 'Avenida Presidente Euríco Gáspar Dutra, 416', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.364597, -51.152137', 1),
(236, '205.1-OUT', 'Avenida Chepli Tanus Daher, 965', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.366035, -51.159174', 1),
(237, '205.2-IN', 'Avenida Chepli Tanus Daher, 1240-1276', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.368594, -51.159282', 1),
(238, '205.2-OUT', 'Rua Maria Vidal da Silva, 351-415', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.362776, -51.155002', 1),
(239, '208-OUT', 'Rua João Huss, 529-621', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.328497, -51.180110', 1),
(240, '210.UM-IN', 'Rua Miguel Campos de Souza, 341', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.383362, -51.128439', 1),
(241, '210.V-IN', 'Rua dos Sapateiros, 157-199', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.382063, -51.129708', 1),
(242, '215-IN', 'Rua Tadão Ohira, 256', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.372247, -51.132096', 1),
(243, '216-PC', 'Rua Geraldo Júlio', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.384413, -51.170252', 1),
(244, '218-OUT', 'Avenida Guilherme de Almeida, 1609', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.370428, -51.139433', 1),
(245, '219-IN', 'Rua Rosane Wainberg, 570', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.389042, -51.120592', 1),
(246, '219-OUT', 'Rua Emílio Mahler, 77-155', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.388334, -51.119409', 1),
(247, '221-IN', 'Estrada Piriquitos', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.350234, -51.049667', 1),
(248, '221-OUT', 'PR-526, sentido centro', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.347528, -51.049137', 1),
(249, '226-PC', 'Rua Emílio Striquer', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.377819, -51.127058', 1),
(250, '227-PC', 'Jardim Botânico', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.361926, -51.174877', 1),
(251, '229-IN', 'Rua Caracas 232-376', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.332298, -51.180270', 1),
(252, '250-PC', 'PR-538, 695', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.512982, -51.235102', 1),
(253, '270-IN', 'Rua João Costa melquíades, 1769', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.419217, -51.126118', 1),
(254, '270-PC', 'Estrada do Galo', '2026-01-01 21:38:55', 'LOCAL_270PC.png', 'Ponto Final', 'ativo', '-23.415398, -51.117438', 1),
(255, '271-PC', 'Coroados', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.445989, -51.190015', 1),
(256, '285.PC', 'Distrito de Guairacá', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.603790, -50.976792', 1),
(257, '295-PC', 'Avenida Gustavo Avelino, Distrito de Lerroville', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.690717, -51.090576', 1),
(258, '296-PC', 'Comunidade Eli Vive', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.608091, -51.014726', 1),
(259, '301-IN', 'Rua Raja Gabaglia, 820', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.318560, -51.173514', 1),
(260, '301-OUT', 'Rua Jonatas Serrano, 470-576', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.313872, -51.174563', 1),
(261, '301-PC', 'Rua Nilo Peçanha, 199', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.316405, -51.185136', 1),
(262, '302-IN', 'Rua Deputado Fernando Ferrari, 424', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.303561, -51.183286', 1),
(263, '302-OUT', 'Rua Astorga, 97', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.310811, -51.178280', 1),
(264, '302-PC', 'Rua Marechal Hermes da Fonseca, 182-244', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.315073, -51.185565', 1),
(265, '303-IN', 'Rua Albert Einstein, 290-382', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.305383, -51.194771', 1),
(266, '303-OUT', 'Travessa João Nicolau, 116', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.313473, -51.191681', 1),
(267, '303-PC', 'Rua Benjamin Franklin, 228-360', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.314066, -51.201655', 1),
(268, '307-IN', 'Avenida Hugo Seben, 89', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.316186, -51.227835', 1),
(269, '307-PC', 'Rua do Judô, 236', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.322262, -51.225737', 1),
(270, '309-OUT', 'Rua Deputado Ardinal Ribas, 131-207', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.291007, -51.222942', 1),
(271, '309-PC', 'Rua Prata, 221', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.291845, -51.221643', 1),
(272, '310-IN', 'Rua Jaracatiá, 40', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.296470, -51.194261', 1),
(273, '312-IN', 'Rua São Sebastião, 100-170', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.276474, -51.197038', 1),
(274, '313-PC', 'Rua Antônio de Carvalho Lage Filho, 2', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.277918, -51.220689', 1),
(275, '315-IN', 'Avenida Juvenal Pietraroia, 108-200', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.318896, -51.213347', 1),
(276, '315-OUT', 'Rua Procópio Ferreira, 45', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.320670, -51.209839', 1),
(277, '317-PC', 'Rua Valdomiro Turini, 1-45', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.329555, -51.225092', 1),
(278, '400-OUT', 'Avenida Valdir de Azevedo, 177-237', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.259334, -51.178438', 1),
(279, '401-OUT', 'Rua Reinaldo Ribeiro da Silva, 28-80', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.270641, -51.193604', 1),
(280, '401-PC', 'Rua Reinaldo Ribeiro da Silva, 230', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.270004, -51.194375', 1),
(281, '402-IN', 'Escola Lucia Barros Lisboa', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.259344, -51.165538', 1),
(282, '402-OUT', 'Rua Elis Regina, 181', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.256332, -51.165680', 1),
(283, '404-OUT', 'Rua Alberto Jans, 137-247', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.253405, -51.158601', 1),
(284, '405-IN', 'Rua Aliomar Baleeiro, 304', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.260658, -51.154394', 1),
(285, '406.L-IN', 'Rua Francisco de Assis F Ruíz, 196', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.256723, -51.141012', 1),
(286, '406.L-OUT', 'Rua Francisco García de Campos, 621-623', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.265237, -51.139389', 1),
(287, '407.J-IN', 'Rua Raul Coutinho, 98-134', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.272503, -51.142943', 1),
(288, '407.S-IN', 'Rua Francisco Bueno', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.272136, -51.144423', 1),
(289, '407.S-OUT', 'Rua Raul Coutinho, 97-133', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.272505, -51.142765', 1),
(290, '408-IN', 'Rua Tibagi, 678', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.296344, -51.158327', 1),
(291, '428-PC', 'Rua Helly Reys Piazzalunga, 2', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.268495, -51.206203', 1),
(292, '419-IN', 'Rua Pompéu Soares Cardoso, 397-449', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.262839, -51.167325', 1),
(293, '425-PC', 'Rua Angelina Ricci Vezozzo, 2836', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.264498, -51.137962', 1),
(294, '225-PC', 'Rua João Rogério Ribeiro Bonesi', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.346600, -51.175872', 1),
(295, '408-OUT', 'Rua Tietê, 901-965', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.295713, -51.163187', 1),
(296, '411-IN', 'Rua Inaldo Guimarães, 2-96', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.277511, -51.154017', 1),
(297, '413-IN', 'Avenida Alexandre Santoro, 1128', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.262498, -51.177237', 1),
(298, '413-OUT', 'Rua Francisco de Melo Palheta, 277-329', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.264005, -51.180853', 1),
(299, '413-PC', 'Rua Luísa Donoso Gonzales, 2-70', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.267857, -51.185611', 1),
(300, '414-IN', 'Rua Nivardo Espósito, 2', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.276106, -51.191375', 1),
(301, '414-OUT', 'Avenida Clárice de Lima Castro, 191-223', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.276773, -51.189794', 1),
(302, '415-IN', 'Rua Remo Ferrarese, 472-512', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.253843, -51.151665', 1),
(303, '415-OUT', 'Rua Adriano Rocha, 237-279', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.252738, -51.150071', 1),
(304, '417-OUT', 'Rua Guilhermina Lahman, 357', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.257420, -51.138008', 1),
(305, '417-PC', 'Rua Alzira Postali Gewehr, 235', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.255866, -51.135711', 1),
(306, '419-OUT', 'Rua Otávio Clivati, 252', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.262505, -51.163625', 1),
(307, '423-IN', 'Rua Antônio M de Oliveira, 554-590', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.255572, -51.191120', 1),
(308, '423-OUT', 'Rua Teresinha P Trindade, 92', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.255000, -51.190000', 1),
(309, '424-IN', 'Rua Rosa Golin Milhorini, 52-94', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.259000, -51.203000', 1),
(310, '424-OUT', 'Rua Noel Porfírio de Andrade, 273', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.257635, -51.207306', 1),
(311, '424-PC', 'Rua João Grigoleto, 198-216', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.250206, -51.207375', 1),
(312, '426-IN', 'Rua Jouberte de Carvalho, 1280', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.253190, -51.171266', 1),
(313, '427-PC', 'Travessa Ibiporã, 69', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.196437, -51.201873', 1),
(314, '428-IN', 'Rua André Buck, 169', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.261993, -51.198361', 1),
(315, '444-IN', 'Avenida Londrina, 26', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.198769, -51.200584', 1),
(316, '444-PC', 'Rua Bonislau Scherlowski, 100', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.192184, -51.202225', 1),
(317, '502-OUT', 'Rua Arcindo Sardo, 267-331', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.285888, -51.175918', 1),
(318, '504-IN', 'Rua Theodoro Skiba, 105', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.273386, -51.151405', 1),
(319, '703-IN', 'Rua Anuar Caram, 56-108', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.268660, -51.145113', 1),
(320, '703-PC', 'Rua Guilhermina Lahman, 673', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.254371, -51.137782', 1),
(321, '706-PC', 'Rua Gessi Eugênio da Silva, 1025-1075', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.249223, -51.142961', 1),
(322, '802-OUT', 'Avenida Duque de Caxias, 355-613', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.336239, -51.149377', 1),
(323, '802-PC', 'Avenida Duque de Caxias, 526', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.336212, -51.149120', 1),
(324, '806-OUT', 'Avenida Saul Elkind, 85', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.259967, -51.139190', 1),
(325, '900-IN', 'Rua Dom João VI, 780-828', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.323440, -51.128726', 1),
(326, '900-PC', 'Avenida Robert Koch, 171', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.325424, -51.128141', 1),
(327, '121-IN2', 'Avenida das Maritacas, 2642-2684', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.283000, -51.114732', 1),
(328, '906-IN', 'Rua Sidney Miller, 1254-1382', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.330031, -51.213630', 1),
(329, '906-OUT', 'Rua Renato Fabretti, 685', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.328502, -51.217038', 1),
(330, '906-PC', 'Avenida Hugo Seben, 356', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.313395, -51.227126', 1),
(331, '907-PC', 'Rua Prefeito Faria Lima, 1320', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.321315, -51.188588', 1),
(332, '913-PC', 'Rua Antônio Gomes Santiago, 173-277', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.329630, -51.107731', 1),
(333, '95-IN', 'Avenida Inglaterra, 902', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.348767, -51.147438', 1),
(334, 'COL', 'Ponto Virtual - [Rua das Açucenas, 190]', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.321730, -51.187760', 1),
(335, 'EPESMEL', 'Rua Angelina Ricci Vezozzo, 24', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.291881, -51.138996', 1),
(336, 'UTFPR', 'Avenida dos Pioneiros, 2735 - UTFPR', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.307913, -51.113920', 1),
(337, '93-PC', 'Rua Palmira Bandeira Parizoto, 264-316', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.247073, -51.140412', 1),
(338, '430-PC', 'Avenida Rosalvo Marques Bonfim, 1501', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.263056, -51.204990', 1),
(339, '430-IN', 'Rua São José, 380', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.270477, -51.201059', 1),
(340, '425.F-PC', 'Av. Rosalvo Marques Bonfim, 1450', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.262459, -51.204730', 1),
(341, '231-IN', 'Avenida das Américas, 200', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.340075, -51.138448', 1),
(342, '231-OUT', 'Rua Pitágoras, 372', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.337813, -51.141854', 1),
(343, '425-OUT', 'Avenida Rosalvo Marques Bonfim, 899', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.263046, -51.199630', 1),
(344, 'CARREFOUR', 'Rodovia Mabio Gonçalves Palhano, 200', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.339978, -51.186963', 1),
(345, 'N601', 'Rua da Macieira, 167-195', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.305260, -51.128829', 1),
(346, '222-PC', 'Rua Salvadora Sanches Canales, 171', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.351069, -51.129341', 1),
(347, 'UEL-CCH', 'CCH - Universidade Estadual de Londrina', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.324281, -51.203738', 1),
(348, 'MASTIG', 'MASTIG', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.419943, -51.145697', 1),
(349, 'TSHOP', 'Terminal Shopping Catuaí', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.343881, -51.186024', 1),
(350, 'TA-IN', 'Terminal Acapulco', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.359748, -51.155366', 1),
(351, 'TOVER-IN', 'Terminal Ouro Verde', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.281849, -51.172075', 1),
(352, '101.A-PC', 'Rua Antônio da Silva, 175-217', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.281057, -51.143210', 1),
(353, '101.A-IN', 'Av. Angelina Ricci Vezozzo', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.283195, -51.138606', 1),
(354, '101.A-OUT', 'Rua Gino Tamiozo, 565-693', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.287945, -51.142176', 1),
(355, '214-IN', 'Rua Fumiko Miyamura', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.380357, -51.139976', 1),
(356, 'ESTÁDIO', 'Av. Henrique Mansano, 684', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.284260, -51.167138', 1),
(357, '426-OUT', 'Rua Humberto B Testa, 33-113', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.257689, -51.172504', 1),
(358, '400-IN', 'Rua Luiz Vieira Sagrilo - Predinhos', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.258232, -51.174818', 1),
(359, '445-PC', 'Rua Luís Martins, 1', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.285092, -51.145472', 1),
(360, '400-PC', 'Rua Manoel Cordeiro, 53', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.253269, -51.179960', 1),
(361, '219-PC', 'Rua Henrique Vicente, 158-208', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.392309, -51.113166', 1),
(362, '203.R-PC', 'Rua Joaquim Gregório Marquês, 130-196', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.368114, -51.126138', 1),
(363, '203.G-PC', 'Rua Joaquim Gregório Marquês, 129-195', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.367887, -51.126064', 1),
(364, '121-PC', 'Avenida das Maritacas, 7150-8838', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.268462, -51.098656', 1),
(365, '200-PC', 'Rua Capitão Pedro Rufino, 370-388', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.334528, -51.147494', 1),
(366, '102-PC', 'Rua Brilhante, 1-97', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.295072, -51.136851', 1),
(367, '204-PC', 'Rua Tomás Fabrício, 95', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.373366, -51.145504', 1),
(368, '205-PC', 'Rua Osvaldo A Filho, 125-171', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.377441, -51.153338', 1),
(369, '215-PC', 'Rua Silvio Mariano da Silva, 238-308', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.371821, -51.126224', 1),
(370, '224-PC', 'Rua Rozalina Menegheti Lerco, 2-164', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.360001, -51.180842', 1),
(371, '220-PC', 'Chácaras Emaús', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.354531, -51.201483', 1),
(372, '221-PC', 'PR-526, ponto final', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.342673, -51.029110', 1),
(373, 'GARCIA', 'Garagem Viação garcia e Londrisul', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.304648, -51.123341', 1),
(374, 'TC-L-110', 'Terminal Central Plataforma L', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308625, -51.160610', 1),
(375, 'TC-L-101', 'Terminal Central Plataforma L', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308621, -51.160750', 1),
(376, '210.V-OUT', 'Rua dos Cozinheiros, 571', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.381360, -51.127506', 1),
(377, 'TC-PI-L', 'Terminal Central piso inferior', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.307895, -51.161356', 1),
(378, 'TC-PS-L', 'Terminal Central piso superior', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308694, -51.161333', 1),
(379, 'AQUAVILLE', 'Avenida dos Pioneiros, 1250', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.307720, -51.102992', 1),
(380, 'MARAVILHA', 'Rua Ivai', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.462115, -50.998841', 1),
(381, 'SERGIPE.A', 'Rua Sergipe, 127-255', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.309789, -51.155776', 1),
(382, 'LACOR', 'R. Adhemar Pereira de Barros, 745 - Bela Suiça', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.336236, -51.162490', 1),
(383, 'EVERY', 'Avenida Ademar Pereira de Barros, 730', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.335791, -51.162671', 1),
(384, 'TIBET', 'Rua Tibet, 178-222', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.338088, -51.171645', 1),
(385, 'GARIBALDI', 'Avenida Garibalde Deliberador, 323-481', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.337862, -51.170589', 1),
(386, 'AURORA', 'Avenida Ayrton Senna da Silva, 424-500', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.329587, -51.177490', 1),
(387, '113-IN', 'Avenida dos Pioneiros, 3131', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308141, -51.113365', 1),
(388, 'TC-PS-T', 'Terminal Central, piso superior', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308694, -51.161326', 1),
(389, 'IEEL', 'Rua Brasil, 951-999', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.318011, -51.153797', 1),
(390, 'TC-PI-T', 'Terminal Central, piso inferior', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.307889, -51.161359', 1),
(391, '114-PC', 'Parada nova Garcia', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.304522, -51.123672', 1),
(392, '423-PC', 'Rua Odete de Santana', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.244102, -51.188533', 1),
(393, '415-PC', 'Rua Aparecida Sartorado', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.239674, -51.145918', 1),
(394, '405-PC', 'Rua Ana Murge, 103', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.247044, -51.146053', 1),
(395, 'HU', 'Avenida Robert Koch, 226-410', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.325690, -51.127843', 1),
(396, '232-PC', 'Rua Wenceslau Zamuner, 83', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.354336, -51.153469', 1),
(397, '202.VZ-OUT', 'Rua Verona, 31', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.351517, -51.145934', 1),
(398, '203.G-OUT', 'Rua Vitória-Régia, 15', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.357911, -51.149300', 1),
(399, '303-IN2', 'Travessa João Nicolau, 116', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.313443, -51.191688', 1),
(400, '102-OUT', 'Rua Ceará, 181-203', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.301757, -51.143334', 1),
(401, '104.M-IN', 'Rua Jambo, 67-135', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.315723, -51.137172', 1),
(402, '201.J-OUT', 'Avenida Salgado Filho, 271-291', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.329525, -51.142795', 1),
(403, '201.J-PC', 'Rua Matilde Yoshiko Honda, 2-52', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.343582, -51.125475', 1),
(404, '201.J-IN', 'Rua Capitão João Busse, 212', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.330028, -51.144941', 1),
(405, '202.VA-PC', 'Rua Edmundo Gonçalves, 243', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.355757, -51.137919', 1),
(406, '104-PC', 'Rua do Tamarino, 690', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308434, -51.126809', 1),
(407, '106.H-PC', 'Rua João da Silva Godoy, 276', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.328030, -51.108390', 1),
(408, '104.T-OUT', 'Rua Limão', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.315295, -51.139641', 1),
(409, '110.N-OUT', 'Rua Centenário do Sul, 298', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.284487, -51.123458', 1),
(410, '200-OUT', 'Rua Colômbia, 657-663', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.325941, -51.149491', 1),
(411, '103.A-IN', 'Rua Mangaba, 871-897', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.305296, -51.135706', 1),
(412, '295-PC2', 'Rua Eloy Nogueira, Distrito de Lerroville', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.689849, -51.089983', 1),
(413, '295.B-OUT', 'Rua Dezenove de Dezembro, divisa com Tamarana', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.715758, -51.095802', 1),
(414, '302.M-IN', 'Rua Professor Samuel Moura, 272-328', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308048, -51.179360', 1),
(415, '275-IN', 'Rua Miguel Blasi, Distrito de Paiquerê', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.528707, -51.082905', 1),
(416, '275-PC', 'Rua General Mallet, Distrito de Paiquerê', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.523341, -51.082680', 1);
INSERT INTO `cadastros_locais` (`id`, `company_code`, `name`, `data_importacao`, `imagem_path`, `descricao`, `status`, `coordenadas`, `mostrar_ponto`) VALUES
(417, '305.R-PC', 'Rua Bambuzal - PCU-UEL', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.328123, -51.198411', 1),
(418, '306-OUT', 'UEL - Saída PR445', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.323754, -51.196901', 1),
(419, '102-IN', 'Avenida Santa Mônica, 406-570', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.303463, -51.141494', 1),
(420, '103.M-IN', 'Rua Noel Rosa, 124-128', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.304472, -51.134577', 1),
(421, '406.L-PC', 'Rua Alberto Palma sem número', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.253270, -51.138628', 1),
(422, '407.SL-IN', 'Avenida Milton Ribeiro de Menezes, 715', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.278114, -51.139505', 1),
(423, '295.BE-OUT', 'Rua Eloy Nogueira, Distrito de Lerroville', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.691237, -51.091325', 1),
(424, '412.H-IN', 'Rua Osmy Muniz, 278', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.279849, -51.177189', 1),
(425, '412.H-OUT', 'Rua Mario Cansian, 85', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.281431, -51.179514', 1),
(426, '412.I-OUT', 'Rua Osmy Muniz, 281-331', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.279918, -51.177283', 1),
(427, '412.I-PC', 'Rua Enoch Vieira dos Santos, 883', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.273790, -51.183827', 1),
(428, '108-OUT', 'Avenida Paul Harris, 852 Oposto', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.321889, -51.140740', 1),
(429, '112.L-IN', 'Avenida Jamil Scaff, 2249', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.311513, -51.119191', 1),
(430, 'INESUL', 'Ponto normal e inicial - INESUL', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.330517, -51.152294', 1),
(431, 'G.SLUIS', 'Garagem São Luis', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.510417, -51.236628', 1),
(432, 'NSHOP', 'Londrina Norte Shopping', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.285401, -51.150602', 1),
(433, '224.A-PC', 'Alameda Ipê Roxo, 357', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.354849, -51.188582', 1),
(434, 'TA-A', 'Terminal Acapulco - pista A', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.359861, -51.155330', 1),
(435, '706.C-OUT', 'Rua Mato Grosso, 1003', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.317848, -51.156391', 1),
(436, '901.G-PC', 'Rua Francisco de Assis F Ruiz, 679', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.252167, -51.140917', 1),
(437, '904.S-IN', 'Rua Pero Fernandes Sardinha - UBS Sabará', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.314909, -51.210210', 1),
(438, '907.C-OUT', 'Rodovia Celso Garcia Cid, 1050', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.317352, -51.210451', 1),
(439, '93.F-PC', 'Rua Elías Daniel Hatti, 756', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.253003, -51.138785', 1),
(440, 'TA-B', 'Terminal Acapulco - pista B', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.360905, -51.155186', 1),
(441, 'JUMPER', 'Jumper', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.279089, -51.134624', 1),
(442, 'TOVER-A', 'Terminal Ouro Verde - Pista A', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.281751, -51.171952', 1),
(443, 'TOVER-B', 'Terminal Ouro Verde - Pista B', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.281529, -51.170711', 1),
(444, 'N2842', 'Avenida Inglaterra, 1059', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.349326, -51.147242', 1),
(445, 'N991', 'Avenida Inglaterra, 845', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.346747, -51.147216', 1),
(446, 'TC-D-217', 'Terminal Central pista D', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.307952, -51.160615', 1),
(447, 'TC-D-228', 'Terminal Central pista D', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.307967, -51.160432', 1),
(448, 'TC-C-L', 'Terminal Central pista C', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308135, -51.161162', 1),
(449, 'TC-F-201', 'Terminal Central Pista F', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308127, -51.161023', 1),
(450, 'TC-H-112', 'Terminal Central plataforma H', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308364, -51.160730', 1),
(451, 'TC-J', 'Terminal Central pista J', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308520, -51.160730', 1),
(452, '447-PC', 'Avenida Los Imigrantes', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.260232, -51.122454', 1),
(453, '101.G-IN', 'Rua Gino Tamiozo, 566-694', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.287886, -51.142183', 1),
(454, '101.G-OUT', 'Angelina Ricci Vezozzo, oposto ao 500', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.287948, -51.138852', 1),
(455, 'TC-L-102', 'Terminal Central Plataforma L', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308628, -51.161034', 1),
(456, 'TC-H-301', 'Terminal Central Plataforma H', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308364, -51.161042', 1),
(457, 'N4019', 'Avenida Cirillo Curtti, 37', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.302010, -51.111310', 1),
(458, 'TC-H-109', 'Terminal Central Plataforma H', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308363, -51.160451', 1),
(459, '410-PC', 'Rua Araguaia, 287', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.299701, -51.165629', 1),
(460, '106.A-PC2', 'Rua João da Silva Godóy, 55', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.330115, -51.106710', 1),
(461, 'ASSAI', 'Avenida Tiradentes, 7952-8088', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.293257, -51.212872', 1),
(462, 'TC-H', 'Terminal Central / Plataforma H - Piso Superior', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308326, -51.160577', 1),
(463, 'TC-L', 'Terminal Central / Plataforma L - Piso Superior', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308587, -51.160357', 1),
(464, '303.BA1-PC', 'Rua Miguel Karaksoff, 180', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.314147, -51.192842', 1),
(465, 'PV-17', 'Rua Miguel Karaksoff', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.314184, -51.192832', 1),
(466, 'TMG-IN', 'Terminal Milton Gavetti - entrada', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.281649, -51.153004', 1),
(467, '404-IN2', 'Avenida Lucineide R Silveira, 262', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.257114, -51.158676', 1),
(468, '95-IN2', 'Avenida Chepli Tanus Dagher, 215', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.359900, -51.157191', 1),
(469, 'N4219', 'Rua Santa Teresinha, 847', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.311233, -51.142349', 1),
(470, 'N4096', 'jose ferreira da silva 275', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.305498, -51.128794', 1),
(471, '104-OUT', 'Avenida das Laranjeiras, 1435', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.313543, -51.136669', 1),
(472, 'N4014', '', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.295050, -51.218481', 1),
(473, '902-PC1', 'Avenida Angelina Ricci Vezozzo, 3203', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.265141, -51.138221', 1),
(474, '902-PC2', 'Rua Ebio Ferraz de Carvalho, 01', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.282769, -51.114750', 1),
(475, '825-PC', 'Avenida Saul Elkind, 1433', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.258130, -51.151799', 1),
(476, 'PV-B', 'Novo', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.307867, -51.098709', 1),
(477, 'TIRERE-ESTAC', 'Estacionamento Terminal Irerê', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.505023, -51.125680', 1),
(478, '902-PC3', 'Rua Aristóteles Pereira da Silva', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.247685, -51.220586', 1),
(479, 'N-PV-A', 'Rua Nereu Mendes', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.328167, -51.116722', 1),
(480, '272-PC', 'CIMERIAN Indústria Metalúrgica', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.397039, -51.134246', 1),
(481, 'CATEDRAL', 'Travessa Padre Eugênio Herter', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.311772, -51.159817', 1),
(482, 'RSL', 'Troca de motorista reserva LDSUL', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.308438, -51.161348', 1),
(483, '409-IN', 'Rua Tremembés, 652', '2026-01-01 21:38:55', NULL, NULL, 'ativo', '-23.300128, -51.144507', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `cadastros_vias`
--

CREATE TABLE `cadastros_vias` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `instrucoes` varchar(50) DEFAULT NULL,
  `linha` varchar(50) DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `iframe_mapa` text DEFAULT NULL,
  `nome_linha` varchar(255) DEFAULT NULL,
  `status_linha` varchar(20) DEFAULT 'ativa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cadastros_vias`
--

INSERT INTO `cadastros_vias` (`id`, `codigo`, `instrucoes`, `linha`, `descricao`, `data_importacao`, `iframe_mapa`, `nome_linha`, `status_linha`) VALUES
(1, '002_v_IRERE', 'VOLTA', '2', 'Reserva de operação TIRERE', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(2, '085_i_FILE2', 'IDA', '85', 'Especial Ilece sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(3, '200L_v_FBRAS', 'VOLTA', '200L', 'Vila Brasil sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(4, '201_i_TIROG', 'IDA', '201', 'Via Tiro de Guerra sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(5, '201_v_SALGA', 'VOLTA', '201', 'Via Salgado Filho sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(6, '202_i_VZROS', 'IDA', '202', 'Roseira e Vale Azul via Paris sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(7, '203_i_IGUIM', 'IDA', '203', 'Via Guilherme com São Marcos sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(8, '203_v_FGUIM', 'VOLTA', '203', 'Via Guilherme e São Marcos sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(9, '203_v_GUIMA', 'VOLTA', '203', 'Via Guilherme e São Marcos sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(10, '205_v_CAFUM', 'VOLTA', '205', 'Via Cafezal um sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(11, '207_v_UNOPA', 'VOLTA', '207', 'Linha 207 - sentido Unopar (Jd. Piza)', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(12, '210_v_FEIR1', 'VOLTA', '210', 'Via um sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(13, '210_v_FEIR5', 'VOLTA', '210', 'Via cinco sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(14, '211_i_SOTOC', 'IDA', '211', 'Somente Sítio São José sentido Shopping Catuaí', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(15, '213_i_FVRIJ', 'IDA', '213', 'Via Colégio Vicente Rijo', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(16, '213_i_MADRE', 'IDA', '213', 'Via Madre Leônia Milito - sentido Centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(17, '213_v_WYCLI', 'VOLTA', '213', 'Via João Wyclif - sentido Shopping Catuaí', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(18, '218_i_PRNES', 'IDA', '218', 'Jatobá via Perobal e Nova Esperança', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(19, '218_v_JATOB', 'VOLTA', '218', 'Via sem Perobal sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(20, '218_v_PEROB', 'VOLTA', '218', '218 Via Perobal sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(21, '219_i_INESP', 'IDA', '219', 'Nova Esperança sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(22, '219_v_NESPE', 'VOLTA', '219', 'Via Nova Esperança sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(23, '221_i_LIMOE', 'IDA', '221', 'Limoeiro sentido Centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(24, '228_i_ANELC', 'IDA', '228', '228 Avenida Bandeirantes sentido Centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(25, '228_i_ANEPS', 'IDA', '228', 'Avenida Bandeirantes sentido Terminal', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(26, '250_v_GUARA', 'VOLTA', '250', 'São Luis até Guaravera', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(27, '260_i_USINA', 'IDA', '260', 'Via Usina sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(28, '265_i_IFMAR', 'IDA', '265', 'Maravilha sentido Terminal', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(29, '271_i_FMAST', 'IDA', '271', 'Mastig sentido Terminal', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(30, '272_v_CIMER', 'VOLTA', '272', 'Parque Industrial 4', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(31, '408L_v_OGUID', 'VOLTA', '408L', 'via Rua Semiu Oguido sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(32, '408L_v_RECRE', 'VOLTA', '408L', 'Recreio sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(33, '600_i_EXSSO', 'IDA', '600', 'Expresso Irerê sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(34, '601_i_FLORE', 'IDA', '601', 'Via Conjunto das Flores sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(35, '601_i_TACAP', 'IDA', '601', 'Via Dez de Dezembro sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(36, '603_i_FTACA', 'IDA', '603', 'Via Acapulco sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(37, '800L_v_PIAUI', 'VOLTA', '800L', 'via Piauí sentido Vivi', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(38, '802L_v_BANDE', 'VOLTA', '802L', 'Terminal Vivi Avenida Bandeirantes sentido Vivi', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(39, '906_i_COLUM', 'IDA', '906', 'Acapulco sentido Avelino', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(40, '906_i_DOMIN', 'IDA', '906', 'Via Alphaville sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(41, '906_v_DOMIN', 'VOLTA', '906', 'Via Alphaville sentido Shopping', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(42, '907_i_FASHO', 'IDA', '907', '907 - Trecho Rua Faria Lima só até Shopping', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(43, '907_v_GLEBA', 'VOLTA', '907', '907 Sentido Gleba', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(44, '913L_i_INICI', 'IDA', '913L', 'Sentido Gleba', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(45, '002_i_TRTAC', 'IDA', '2', 'Troca de motorista TACAP', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(46, '085_v_ILECE', 'VOLTA', '85', 'Especial sentido Ilece', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(47, '094_i_ROSEI', 'IDA', '94', 'Corujão Roseira sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(48, '095_i_FCORU', 'IDA', '95', 'Corujão Vitória sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(49, '098_i_FGLEB', 'IDA', '98', 'Corujão Gleba Palhano sentido Terminal', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(50, '202_i_IROSE', 'IDA', '202', 'Roseira sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(51, '202_i_IVAZU', 'IDA', '202', 'Roseira e Vale Azul via Rua Verona sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(52, '202_v_FEIRP', 'VOLTA', '202', 'Roseira e Vale Azul via Paris sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(53, '202_v_ROSEI', 'VOLTA', '202', 'Roseira sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(54, '203_v_FEGUI', 'VOLTA', '203', 'Via Guilherme com feira sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(55, '203_v_REGMA', 'VOLTA', '203', 'Via Régia e São Marcos sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(56, '204_i_ITITO', 'IDA', '204', 'Tito Leal sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(57, '205_i_ICAD2', 'IDA', '205', 'Via dois sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(58, '205_i_ICADO', 'IDA', '205', 'via dois sentido terminal', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(59, '208_v_HIGI2', 'VOLTA', '208', 'sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(60, '209_v_CLAUD', 'VOLTA', '209', 'Via Claudia - sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(61, '210_i_IVIAU', 'IDA', '210', 'Via um sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(62, '211_i_SJOSE', 'IDA', '211', '211 - Via Sítio São José (Toca do Peixe) - Sentido Shopping Catuaí', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(63, '211_v_SJOSE', 'VOLTA', '211', 'Via Sítio São José sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(64, '213_i_WYCLI', 'IDA', '213', 'Via João Wyclif - sentido Centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(65, '213_v_MADRE', 'VOLTA', '213', 'Via Madre Leônia - sentido Shopping Catuaí', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(66, '214_i_FRODO', 'IDA', '214', 'Via PR 445 sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(67, '215_v_PEROB', 'VOLTA', '215', 'Linha 215 sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(68, '216_v_MIGUE', 'VOLTA', '216', '216 Chácara São Miguel sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(69, '218_i_IJATO', 'IDA', '218', 'Via Jatobá sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(70, '218_i_PEROB', 'IDA', '218', 'Via Perobal sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(71, '218_v_NESPE', 'VOLTA', '218', 'Via Nova Esperança sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(72, '221_i_INIPL', 'IDA', '221', 'Via Pequena Londres sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(73, '222_i_IVAZU', 'IDA', '222', 'Vale Azul sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(74, '222_i_MAXPS', 'IDA', '222', 'Maxi sentido Terminal', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(75, '231_v_PLOND', 'VOLTA', '231', 'Pequena Londres sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(76, '265_i_IMARA', 'IDA', '265', 'Maravilha sentido Terminal', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(77, '271_i_COROA', 'IDA', '271', 'Via Coroados sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(78, '271_i_VITOR', 'IDA', '271', 'via Vitória sentido terminal', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(79, '275_i_PAIQU', 'IDA', '275', '275 Via sem Vila Rural sentido Irerê', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(80, '280_i_TAQRU', 'IDA', '280', 'via Vila Rural sentido irerê', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(81, '290_v_GUARA', 'VOLTA', '290', '290 Via Guaravera sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(82, '296_v_BEELI', 'VOLTA', '296', 'via Água do Beijo sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(83, '296_v_ELIVI', 'VOLTA', '296', 'Comunidade Eli Vive sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(84, '408L_i_IRECR', 'IDA', '408L', 'Recreio sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(85, '602_v_TACAP', 'VOLTA', '602', 'Parador Terminal Irerê - Centro - via com Acapulco - sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(86, '800L_i_CETAC', 'IDA', '800L', 'Linha 800 do centro para Terminal Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(87, '800L_v_CETAC', 'VOLTA', '800L', 'Linha 800 do Terminas Acapulco até o centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(88, '800L_v_ITOTV', 'VOLTA', '800L', 'Linha 800 Ldsul - inicio Ouro Verde para Vivi', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(89, '804L_i_GLEBA', 'IDA', '804L', 'Terminal Oeste Gleba Palhano - sentido Gleba', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(90, '906_v_DOMAC', 'VOLTA', '906', 'Via Alphaville sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(91, '907_i_GLSHO', 'IDA', '907', 'Gleba Palhano até Shopping', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(92, '907_i_TASHO', 'IDA', '907', 'Sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(93, '907_v_CATIV', 'VOLTA', '907', '907 Via Cativa', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(94, '002_i_TCEPS', 'IDA', '2', 'Frota reserva TCPS', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(95, '002_i_TRTVX', 'IDA', '2', 'Troca de motorista TVIVI', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(96, '002_v_TRTIR', 'VOLTA', '2', 'Troca de motorista TIRERE', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(97, '088_v_ESCOL', 'VOLTA', '88', 'Especial sentido COL', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(98, '094_v_ROSEI', 'VOLTA', '94', 'Corujão Roseira sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(99, '095_v_FTACA', 'VOLTA', '95', 'Corujão até Terminal Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(100, '200L_i_BRASI', 'IDA', '200L', 'Vila Brasil sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(101, '200L_i_IBRAS', 'IDA', '200L', 'sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(102, '200L_v_HOSFE', 'VOLTA', '200L', 'via Hospital do Câncer sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(103, '201_v_JOILE', 'VOLTA', '201', 'Via João Busse e Iles sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(104, '202_i_BANDE', 'IDA', '202', 'Av. Bandeirantes sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(105, '202_v_VAZUL', 'VOLTA', '202', 'Roseira e Vale Azul sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(106, '203_v_FEIRM', 'VOLTA', '203', 'Via Régia e São Marcos para bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(107, '204_v_TITOC', 'VOLTA', '204', 'Linha 204 - sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(108, '205_i_ICAU2', 'IDA', '205', 'Cafezal um sentido Terminal', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(109, '205_v_CAFDO', 'VOLTA', '205', 'Via Cafezal dois sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(110, '210_i_CINCO', 'IDA', '210', 'Via cinco Sentido Centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(111, '210_v_VIAUM', 'VOLTA', '210', 'Via um sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(112, '213_i_JWYOS', 'IDA', '213', 'Via João Wyclif sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(113, '214_i_IRODO', 'IDA', '214', 'Via Pr445 sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(114, '214_v_CHACA', 'VOLTA', '214', 'Via Chácaras sentido Dequech', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(115, '217_i_FVIVE', 'IDA', '217', 'Vivendas sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(116, '218_v_FPRNE', 'VOLTA', '218', 'Via Perobal e Nova Esperança', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(117, '219_i_ITITO', 'IDA', '219', 'Via Tito Leal sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(118, '219_v_VTITO', 'VOLTA', '219', 'Via Tito Leal sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(119, '221_v_FEIPL', 'VOLTA', '221', 'Via Pequena Londres sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(120, '222_i_VAZUL', 'IDA', '222', 'Vale Azul sentido Centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(121, '222_v_VAZUL', 'VOLTA', '222', 'Vale Azul sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(122, '226_v_VITOR', 'VOLTA', '226', 'Via Vitória sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(123, '229_i_FAURO', 'IDA', '229', 'Shopping Aurora Sentido Centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(124, '229_v_GLEBA', 'VOLTA', '229', 'Gleba Palhano sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(125, '231_i_PLOND', 'IDA', '231', 'Pequena Londres sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(126, '260_i_ICAMB', 'IDA', '260', 'via Cambezinho sentido terminal', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(127, '265_i_MARAV', 'IDA', '265', 'Maravilha sentido Terminal', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(128, '270_v_COROA', 'VOLTA', '270', 'Via Coroados sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(129, '275_i_IPAIQ', 'IDA', '275', 'Linha 275 viagem inicial', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(130, '275_v_PAIQU', 'VOLTA', '275', '275 Via sem Vila Rural sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(131, '285_i_GUAIR', 'IDA', '285', '285 Guairacá sentido Irerê', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(132, '290_i_GUARA', 'IDA', '290', 'Via Guaravera sentido Irerê', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(133, '290_v_RURAL', 'VOLTA', '290', '290 Via Vila Rural de Guairacá sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(134, '290_v_SLUIZ', 'VOLTA', '290', 'Via São Luiz sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(135, '295_i_ILERR', 'IDA', '295', 'Lerroville sentido Irerê', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(136, '295_i_ISOLE', 'IDA', '295', 'via só Lerroville sentido irerê', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(137, '601_v_TACAP', 'VOLTA', '601', 'Via Dez de Dezembro sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(138, '603_i_ITECE', 'IDA', '603', 'Via Dez de Dezembro sentido Irerê', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(139, '906_i_FDOMI', 'IDA', '906', 'Via Alphaville sentido Avelino', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(140, '906_v_ICOLU', 'VOLTA', '906', 'Avelino sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(141, '907_i_IGLEB', 'IDA', '907', 'Sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(142, '002_v_TCEPS', 'VOLTA', '2', 'Frota Reserva de operação TCPS', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(143, '088_i_FECOL', 'IDA', '88', 'Especial COL sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(144, '201_i_IJOAO', 'IDA', '201', 'via João Busse sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(145, '201_i_JOAOB', 'IDA', '201', 'Via João Busse sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(146, '201_i_SALGA', 'IDA', '201', 'Via Salgado Filho sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(147, '202_i_ROSEI', 'IDA', '202', 'Roseira sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(148, '202_v_FEIRV', 'VOLTA', '202', 'Roseira Vale Azul via Verona sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(149, '203_v_FGUIL', 'VOLTA', '203', 'Via Guilherme sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(150, '205_i_CAFUM', 'IDA', '205', 'Via Cafezal um sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(151, '205_i_CAUM2', 'IDA', '205', 'Via Cafezal um sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(152, '205_i_ICAFU', 'IDA', '205', 'via um sentido terminal', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(153, '208_v_FHIGI', 'VOLTA', '208', 'sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(154, '210_i_ICINC', 'IDA', '210', 'Via cinco sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(155, '211_v_REGIN', 'VOLTA', '211', 'Patrimônio Regina sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(156, '213_i_CARRE', 'IDA', '213', 'Via Madre Leônia sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(157, '214_i_IFROD', 'IDA', '214', 'Via Pr 445 sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(158, '214_i_RODOV', 'IDA', '214', 'Via PR 445 sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(159, '215_i_FPERO', 'IDA', '215', 'Jardim Perobal sentido Terminal', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(160, '215_i_IPERO', 'IDA', '215', '215 Viagem Inicial sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(161, '215_i_PEROB', 'IDA', '215', 'Linha 215 Jardim perobal sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(162, '219_i_VTITO', 'IDA', '219', 'Via Tito sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(163, '221_i_LPLPS', 'IDA', '221', 'Via Pequena Londres sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(164, '227_i_BTAIN', 'IDA', '227', 'Jardim Botânico sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(165, '229_i_CARRE', 'IDA', '229', 'Carrefour e Gleba sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(166, '229_i_GLEBA', 'IDA', '229', 'Gleba Palhano sentido Centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(167, '231_i_IPLON', 'IDA', '231', 'Pequena Londres sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(168, '270_v_SELVA', 'VOLTA', '270', 'Via PR 445 sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(169, '275_v_RURAL', 'VOLTA', '275', '275 Via com Vila Rural sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(170, '280_i_TAQUA', 'IDA', '280', 'Via Vila Rural com Taquaruna sentido Irerê', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(171, '280_v_TAQRU', 'VOLTA', '280', 'via Vila Rural na IDA', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(172, '285_i_RURAL', 'IDA', '285', 'Linha 285 via Vila Rural de Paiquerê', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(173, '290_i_SLUIZ', 'IDA', '290', 'Via São Luiz sentido Irerê', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(174, '290_v_GUASL', 'VOLTA', '290', 'Linha 290 via São Luiz início voltando para São Luiz', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(175, '295_i_BEIJO', 'IDA', '295', 'via Água do Beijo sentido irerê', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(176, '408L_i_FRECR', 'IDA', '408L', 'Recreio sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(177, '408L_i_OGUID', 'IDA', '408L', 'via Semiu Oguido sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(178, '600_i_FEXPR', 'IDA', '600', 'Expresso Irerê sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(179, '600_v_EXSSO', 'VOLTA', '600', 'Expresso Irerê sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(180, '601_i_FIEEL', 'IDA', '601', 'Colégio IEEL, Terminal Central', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(181, '603_v_TCENT', 'VOLTA', '603', 'Parador Irerê Centro via sem Acapulco sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(182, '801L_i_CIVIC', 'IDA', '801L', 'Terminal Vivi Centro Cívico sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(183, '804L_i_RLAGO', 'IDA', '804L', 'Terminal Oeste Gleba Palhano - sentido Gleba', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(184, '905_i_HUNIV', 'IDA', '905', '905 - sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(185, '905_v_HUNIV', 'VOLTA', '905', '905 - sentido HU', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(186, '913L_i_GLEBA', 'IDA', '913L', 'Sentido Gleba Palhano', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(187, '095_i_CORUJ', 'IDA', '95', 'Corujão Vitória sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(188, '095_i_ICORU', 'IDA', '95', 'Corujão Vitória sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(189, '098_i_GLEBA', 'IDA', '98', 'Corujão Gleba Palhano sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(190, '200L_i_HOSPI', 'IDA', '200L', 'via Hospital do Câncer', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(191, '201_v_FJOAO', 'VOLTA', '201', 'Via João Busse sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(192, '202_v_FVZRO', 'VOLTA', '202', 'Roseira e Vale Azul  sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(193, '203_v_FREGI', 'VOLTA', '203', 'Via Régia sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(194, '205_i_CADO2', 'IDA', '205', 'Via Cafezal dois sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(195, '205_v_FCAFU', 'VOLTA', '205', 'via Cafezal um sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(196, '207_i_UNOPA', 'IDA', '207', 'Unopar sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(197, '208_i_IHIGI', 'IDA', '208', 'sentido centro viagem de início', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(198, '209_i_LACOR', 'IDA', '209', 'Via Lacor - Sentido Centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(199, '210_v_FCINC', 'VOLTA', '210', 'Via cinco sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(200, '211_i_ROYAL', 'IDA', '211', 'Euro Royal sentido Shopping', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(201, '213_i_FMADR', 'IDA', '213', 'Via Madre Leonia Sentido Centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(202, '213_v_VRIJO', 'VOLTA', '213', 'Só Colégio Vicente Rijo', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(203, '214_v_FRODO', 'VOLTA', '214', 'Via PR445 sentido Dequech', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(204, '217_v_VIVEN', 'VOLTA', '217', 'Vivendas sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(205, '220_i_EMAUS', 'IDA', '220', 'Emaús sentido Shopping Catuaí', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(206, '220_v_EMAUS', 'VOLTA', '220', '220 Via Emaús sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(207, '221_v_LIMPL', 'VOLTA', '221', 'Via Pequena Londres sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(208, '224_i_SONOR', 'IDA', '224', 'Sonora sentido Shopping Catuaí', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(209, '226_i_CRIST', 'IDA', '226', 'Via Cristal sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(210, '226_i_ICRIS', 'IDA', '226', 'Via Cristal sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(211, '226_v_CRIST', 'VOLTA', '226', 'Via Cristal sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(212, '227_i_BOTAN', 'IDA', '227', '227 Jardim Botânico sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(213, '229_v_FGLEB', 'VOLTA', '229', 'Gleba Palhano sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(214, '231_v_FPLON', 'VOLTA', '231', 'Pequena Londres sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(215, '250_i_SLUIZ', 'IDA', '250', 'São Luiz sentido Shopping Catuaí', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(216, '260_i_CAMBE', 'IDA', '260', 'Linha 260 via Cambezinho', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(217, '265_v_MARAV', 'VOLTA', '265', 'Maravilha sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(218, '271_i_DECAF', 'IDA', '271', 'via Dequech e chácaras', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(219, '272_i_CIMER', 'IDA', '272', 'Parque Industrial 4', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(220, '280_v_TAQUA', 'VOLTA', '280', 'Via Vila Rural com Taquaruna sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(221, '295_i_LERRO', 'IDA', '295', 'Lerroville sentido Irerê', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(222, '295_v_LERRO', 'VOLTA', '295', 'Lerroville sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(223, '296_i_FELIV', 'IDA', '296', 'Comunidade Eli sentido Lerroville', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(224, '601_i_FMAXI', 'IDA', '601', 'via Colégio Maxi sentido Terminal Central', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(225, '800L_v_RJOAO', 'VOLTA', '800L', 'Via João Cândido sentido Vivi', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(226, '806L_v_FSHOP', 'VOLTA', '806L', 'Saul Elkind, Shopping Catuaí sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(227, '906_v_COLUM', 'VOLTA', '906', 'Avelino sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(228, '906_v_IDOMI', 'VOLTA', '906', 'Via Alphaville até Shopping', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(229, '907_i_GLEBA', 'IDA', '907', '907 - sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(230, '002_i_TACAP', 'IDA', '2', 'Reserva de operação TACAP', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(231, '002_i_TSHOP', 'IDA', '2', 'Reserva de operação TSHOP', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(232, '002_i_TVIVI', 'IDA', '2', 'Reserva de operação TVIVI', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(233, '002_v_TRTAC', 'VOLTA', '2', 'Troca de motorista TACAP', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(234, '085_i_FJKPS', 'IDA', '85', 'Especial Ilece jk sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(235, '095_v_CORUJ', 'VOLTA', '95', 'Corujão Vitória sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(236, '200L_v_BRASI', 'VOLTA', '200L', 'via Vila Brasil - sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(237, '201_i_ISALG', 'IDA', '201', 'Via Salgado Filho sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(238, '201_v_JOAOB', 'VOLTA', '201', 'Via João Busse sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(239, '202_i_VAZUL', 'IDA', '202', 'Roseira e Vale Azul via Verona sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(240, '202_v_FVAZU', 'VOLTA', '202', 'Roseira e Vale Azul sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(241, '203_i_IREGI', 'IDA', '203', 'Via Régia sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(242, '203_i_REGIA', 'IDA', '203', 'Via Régia sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(243, '203_i_REGMA', 'IDA', '203', 'Via Régia e São Marcos sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(244, '203_v_GUILH', 'VOLTA', '203', 'Via Guilherme sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(245, '209_i_CANAA', 'IDA', '209', 'Via Cláudia e Canaã - Sentido Centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(246, '209_i_LACAN', 'IDA', '209', 'Via Lacor e Canaã Sentido Centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(247, '209_v_CANAA', 'VOLTA', '209', 'Via Cláudia e Canaã-Sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(248, '210_i_IFCIN', 'IDA', '210', 'Vitória via 5 sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(249, '211_i_REGIN', 'IDA', '211', 'Patrimônio Regina - Sentido Shopping Catuaí', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(250, '211_v_SOTOC', 'VOLTA', '211', 'Somente Sítio São José sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(251, '216_i_FMIGU', 'IDA', '216', 'Chácara São Miguel sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(252, '216_i_MIGUE', 'IDA', '216', 'Chácara São Miguel sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(253, '218_v_PRNES', 'VOLTA', '218', 'Jatobá via Perobal e Nova Esperança', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(254, '220_i_GHILL', 'IDA', '220', 'via Golden Hill', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(255, '224_i_AUMDO', 'IDA', '224', '224 Via só Alphaville 1 e 2 sentido Shopping Catuaí', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(256, '224_v_AUMDO', 'VOLTA', '224', '224 Via só Alphaville 1 e 2 sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(257, '229_i_IGLEB', 'IDA', '229', 'Linha 229 viagem inicial', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(258, '231_i_FPLON', 'IDA', '231', 'Pequena Londres sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(259, '265_v_FMARA', 'VOLTA', '265', 'Maravilha sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(260, '285_v_RURAL', 'VOLTA', '285', 'via Vila Rural de Paiquerê', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(261, '602_i_FTACA', 'IDA', '602', 'Irerê sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(262, '800L_i_FHIGI', 'IDA', '800L', 'Terminal Vivi até Higienópolis', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(263, '800L_i_RJOAO', 'IDA', '800L', 'Terminal Vivi - Terminal Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(264, '806L_i_SHOPP', 'IDA', '806L', 'Saul Elkind, Shopping Catuaí sentido shopping', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(265, '806L_v_SHOPP', 'VOLTA', '806L', 'Saul Elkind, Shopping Catuaí sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(266, '906_i_DOMAC', 'IDA', '906', 'Via Alphaville sentido Avelino', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(267, '906_i_SHOAV', 'IDA', '906', 'Shopping sentido Avelino', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(268, '002_i_IRERE', 'IDA', '2', 'Reserva de operação TIRERE', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(269, '002_i_TRTIR', 'IDA', '2', 'Troca de motorista TIRERE', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(270, '002_i_TRTSH', 'IDA', '2', 'Troca de motorista TSHOP', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(271, '002_v_TRTVX', 'VOLTA', '2', 'Troca de motorista TVIVI', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(272, '098_v_GLEBA', 'VOLTA', '98', 'Corujão Gleba Palhano sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(273, '200L_v_HOSPI', 'VOLTA', '200L', 'via Hospital do Câncer sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(274, '202_i_IVZRO', 'IDA', '202', 'Roseira e Vale Azul via Paris sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(275, '203_i_IREGM', 'IDA', '203', 'Via Régia com São Marcos sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(276, '203_v_FEIRA', 'VOLTA', '203', 'Via Régia sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(277, '203_v_REGIA', 'VOLTA', '203', 'Via Régia sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(278, '204_i_TITOC', 'IDA', '204', 'Tito Leal sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(279, '205_i_CAFDO', 'IDA', '205', 'Via Cafezal dois sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(280, '205_v_FCADO', 'VOLTA', '205', 'via dois sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(281, '208_i_HIGIE', 'IDA', '208', 'Linha 208 sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(282, '209_v_LACAN', 'VOLTA', '209', 'Via Lacor e Canaa sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(283, '210_i_FVIAU', 'IDA', '210', 'Via Um, Sentido Centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(284, '213_i_MAYOS', 'IDA', '213', 'Via Madre Leônia sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(285, '214_v_RODOV', 'VOLTA', '214', 'Via PR 445 sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(286, '217_i_VIVEN', 'IDA', '217', 'Vivendas do Arvoredo sentido Centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(287, '217_v_VIVE2', 'VOLTA', '217', 'Vivendas sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(288, '218_i_IPERO', 'IDA', '218', 'via Perobal sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(289, '218_i_JATOB', 'IDA', '218', 'Via Jatobá sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(290, '219_i_NESPE', 'IDA', '219', 'Nova Esperança sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(291, '221_i_LIMPL', 'IDA', '221', 'Via Pequena Londres sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(292, '221_v_FLIPL', 'VOLTA', '221', 'Via Pequena Londres sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(293, '222_i_BANDE', 'IDA', '222', 'Avenida Bandeirantes sentido Terminal', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(294, '222_i_VAZPS', 'IDA', '222', 'Vale Azul sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(295, '222_v_UNOEX', 'VOLTA', '222', 'Unopar Expresso sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(296, '224_v_ALSON', 'VOLTA', '224', 'Alphaville e Sonora sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(297, '260_v_USINA', 'VOLTA', '260', '260 Via Usina com Parque Ecológico sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(298, '270_i_COROA', 'IDA', '270', 'Via Coroados sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(299, '270_i_SELVA', 'IDA', '270', '270 Via PR 445 sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(300, '271_v_COROA', 'VOLTA', '271', 'Via Coroados sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(301, '290_i_RURAL', 'IDA', '290', 'Via Vila Rural sentido Irerê', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(302, '295_v_FLERR', 'VOLTA', '295', 'Lerroville sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(303, '408L_i_RECRE', 'IDA', '408L', 'Recreio sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(304, '601_i_CIEEL', 'IDA', '601', 'Via Colégio IEEL sentido Terminal Central', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(305, '602_i_TACAP', 'IDA', '602', 'Parador Irerê até Acapulco sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(306, '603_i_FTCEN', 'IDA', '603', 'Irerê centro via Dez de dezembro e Bandeirantes', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(307, '603_i_TCENT', 'IDA', '603', 'Irerê centro via Dez de dezembro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(308, '906_i_TASHO', 'VOLTA', '906', 'Shopping até Terminal Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(309, '913L_v_FINAL', 'VOLTA', '913L', 'Sentido Av. São João', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(310, '913L_v_GLEBA', 'VOLTA', '913L', 'Sentido Av. São João', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(311, '002_v_TACAP', 'VOLTA', '2', 'Reserva de operação TACAP', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(312, '002_v_TRTSH', 'VOLTA', '2', 'Troca de motorista', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(313, '002_v_TSHOP', 'VOLTA', '2', 'Reserva de operação TSHOP', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(314, '002_v_TVIVI', 'VOLTA', '2', 'Reserva de operação TVIVI', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(315, '085_i_ILE2E', 'IDA', '85', 'Especial Ilece sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(316, '088_i_ESCOL', 'IDA', '88', 'Especial COL sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(317, '200L_i_FBRAS', 'IDA', '200L', 'Vila Brasil Sentido Centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(318, '200_i_HOSPS', 'IDA', '200L', 'via Hospital do Câncer sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(319, '201_i_SAILES', 'IDA', '201', 'Via Salgado Filho e iles sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(320, '201_v_FEIRS', 'VOLTA', '201', 'Via Salgado Filho sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(321, '201_v_FSALG', 'VOLTA', '201', 'Via Salgado Filho sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(322, '202_v_VZROS', 'VOLTA', '202', 'Roseira e Vale Azul sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(323, '203_i_GUILH', 'IDA', '203', 'Via Guilherme sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(324, '203_i_GUIMA', 'IDA', '203', 'Via Guilherme e São Marcos sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(325, '203_i_IGUIL', 'IDA', '203', 'Via Guilherme sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(326, '203_i_INESU', 'IDA', '203', 'Inesul sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(327, '203_v_FEGMA', 'VOLTA', '203', 'Via Guilherme São Marcos com feira', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(328, '209_i_CLAUD', 'IDA', '209', 'Via Cláudia Sentido Centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(329, '209_v_LACOR', 'VOLTA', '209', 'Via Lacor sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(330, '210_i_VIAUM', 'IDA', '210', 'Via 1 sentido Centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(331, '210_v_CINCO', 'VOLTA', '210', 'Via cinco sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(332, '213_i_VRIJO', 'IDA', '213', 'Colégio Vicente Rijo para Terminal Central', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(333, '219_v_CRINA', 'VOLTA', '219', 'via Colégio Rina Maria sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(334, '221_i_IPQTO', 'IDA', '221', 'Estrada do vale Fértil sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(335, '221_v_LIMOE', 'VOLTA', '221', 'Limoeiro sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(336, '221_v_LPQTO', 'VOLTA', '221', 'Estrada do Vale Fértil sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(337, '222_i_UNOEX', 'IDA', '222', 'Unopar Expresso sentido centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(338, '227_v_BOTAN', 'VOLTA', '227', 'Jardim Botânico sentido Botânico', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(339, '228_v_ANELC', 'VOLTA', '228', '228 Avenida Bandeirantes sentido Av. Bandeirantes', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(340, '228_v_FEIRA', 'VOLTA', '228', 'Linha 228 sentido Avenida Bandeirantes', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(341, '250_v_SLUIZ', 'VOLTA', '250', 'São Luiz sentido Bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(342, '260_v_CAMBE', 'VOLTA', '260', 'via Cambezinho sentido bairrro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(343, '270_i_ISELV', 'IDA', '270', 'Selva sentido terminal', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(344, '275_i_RURAL', 'IDA', '275', 'Via Vila Rural sentido Irerê', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(345, '285_v_GUAIR', 'VOLTA', '285', '285 Guairacá sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(346, '290_i_SLVIR', 'IDA', '290', 'Via São Luiz e Vila Rural para terminal', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(347, '295_v_SOBEJ', 'VOLTA', '295', 'Via só água do beijo sentido bairro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(348, '601_v_FEIRA_T', 'VOLTA', '601', 'Parador sentido Acapulco', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(349, '800L_v_FPIAU', 'VOLTA', '800L', 'via Piauí - do Acapulco até centro', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(350, '801L_v_CIVIC', 'VOLTA', '801L', 'Centro Cívico sentido Vivi Xavier', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(351, '802L_i_BANDE', 'IDA', '802L', 'Terminal Vivi Avenida Bandeirantes sentido prefeitura', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(352, '802L_i_FBAND', 'IDA', '802L', 'Terminal Vivi Avenida Bandeirantes sentido prefeitura', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(353, '804L_v_GLEBA', 'VOLTA', '804L', 'Terminal Oeste Gleba Palhano sentido Terminal Oeste', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(354, '906_v_SHOAV', 'VOLTA', '906', 'Avelino até Shopping', '2026-01-01 19:42:13', NULL, NULL, 'ativa'),
(355, '907_v_TASHO', 'VOLTA', '907', 'Acapulco até Shopping Catuaí', '2026-01-01 19:42:13', NULL, NULL, 'ativa');

-- --------------------------------------------------------

--
-- Estrutura para tabela `feriados`
--

CREATE TABLE `feriados` (
  `id` int(11) NOT NULL,
  `data_feriado` date NOT NULL,
  `descricao` varchar(100) DEFAULT NULL,
  `tipo_operacao` enum('uteis','sabados','domingos') NOT NULL DEFAULT 'domingos' COMMENT 'Como a frota opera neste dia',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `feriados`
--

INSERT INTO `feriados` (`id`, `data_feriado`, `descricao`, `tipo_operacao`, `data_criacao`) VALUES
(1, '2025-01-01', 'Confraternização Universal', 'domingos', '2025-12-09 00:15:35'),
(2, '2025-02-28', 'Carnaval (Facultativo)', 'sabados', '2025-12-09 00:15:35'),
(3, '2025-03-04', 'Carnaval', 'domingos', '2025-12-09 00:15:35'),
(4, '2025-04-18', 'Sexta-feira Santa', 'domingos', '2025-12-09 00:15:36'),
(5, '2025-04-21', 'Tiradentes', 'domingos', '2025-12-09 00:15:36'),
(6, '2025-05-01', 'Dia do Trabalho', 'domingos', '2025-12-09 00:15:36'),
(7, '2025-06-19', 'Corpus Christi', 'domingos', '2025-12-09 00:15:36'),
(8, '2025-06-27', 'Sagrado Coração de Jesus (Padroeiro)', 'domingos', '2025-12-09 00:15:36'),
(9, '2025-09-07', 'Independência do Brasil', 'domingos', '2025-12-09 00:15:36'),
(10, '2025-10-12', 'Nossa Sra. Aparecida', 'domingos', '2025-12-09 00:15:36'),
(11, '2025-11-02', 'Finados', 'domingos', '2025-12-09 00:15:36'),
(12, '2025-11-15', 'Proclamação da República', 'domingos', '2025-12-09 00:15:36'),
(13, '2025-12-10', 'Aniversário de Londrina', 'domingos', '2025-12-09 00:15:36'),
(14, '2025-12-25', 'Natal', 'domingos', '2025-12-09 00:15:36'),
(60, '2026-01-01', 'Confraternização Universal', 'domingos', '2025-12-09 18:19:05'),
(61, '2026-02-17', 'Carnaval', 'sabados', '2025-12-09 18:19:35'),
(62, '2026-04-03', 'Sexta feira santa', 'domingos', '2025-12-09 18:20:22'),
(63, '2026-05-01', 'Dia do Trabalhador', 'sabados', '2025-12-09 18:21:07'),
(64, '2026-06-04', 'Corpus Christy', 'sabados', '2025-12-09 18:21:34'),
(65, '2026-09-07', 'Dia da Independência', 'sabados', '2025-12-09 18:22:18'),
(66, '2026-10-12', 'Dia Aparecida', 'domingos', '2025-12-09 18:22:52'),
(67, '2026-11-02', 'Finados', 'sabados', '2025-12-09 18:24:57'),
(68, '2026-11-15', 'Proclamação da República', 'domingos', '2025-12-09 18:25:14'),
(69, '2026-12-20', 'Comércio de Fim de Ano', 'sabados', '2025-12-09 18:26:41'),
(70, '2026-12-25', 'Natal', 'domingos', '2025-12-09 18:29:05'),
(71, '2026-10-04', 'Eleição', 'uteis', '2025-12-09 19:30:21'),
(72, '2026-10-25', 'Eleição', 'uteis', '2025-12-09 19:30:32'),
(73, '2026-04-21', 'Tiradentes', 'sabados', '2025-12-11 12:59:40');

-- --------------------------------------------------------

--
-- Estrutura para tabela `linhas_veiculos`
--

CREATE TABLE `linhas_veiculos` (
  `id` int(11) NOT NULL,
  `linha_numero` varchar(50) NOT NULL,
  `tipo_veiculo` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `linhas_veiculos`
--

INSERT INTO `linhas_veiculos` (`id`, `linha_numero`, `tipo_veiculo`) VALUES
(1, '2', 'Convencional Amarelo'),
(4, '88', 'Micro'),
(5, '88', 'Micro com Ar'),
(6, '85', 'Convencional Amarelo'),
(7, '85', 'Micro'),
(8, '85', 'Micro com Ar'),
(13, '95', 'Convencional Amarelo'),
(14, '95', 'Convencional Amarelo com Ar'),
(15, '95', 'Micro'),
(16, '95', 'Micro com Ar'),
(17, '95', 'Leve'),
(20, '94', 'Micro'),
(21, '94', 'Micro com Ar'),
(22, '98', 'Micro'),
(23, '98', 'Micro com Ar'),
(28, '200L', 'Micro'),
(29, '200L', 'Micro com Ar'),
(30, '200L', 'Leve'),
(31, '201', 'Convencional Amarelo'),
(32, '201', 'Convencional Amarelo com Ar'),
(33, '201', 'Micro'),
(34, '201', 'Leve'),
(37, '203', 'Convencional Amarelo'),
(38, '203', 'Convencional Amarelo com Ar'),
(39, '203', 'Micro'),
(40, '203', 'Leve'),
(41, '204', 'Convencional Amarelo'),
(42, '204', 'Micro'),
(43, '204', 'Micro com Ar'),
(44, '204', 'Leve'),
(49, '207', 'Micro'),
(50, '207', 'Micro com Ar'),
(54, '208', 'Convencional Amarelo'),
(55, '208', 'Convencional Amarelo com Ar'),
(56, '209', 'Convencional Amarelo'),
(57, '209', 'Convencional Amarelo com Ar'),
(58, '210', 'Convencional Amarelo'),
(59, '210', 'Convencional Amarelo com Ar'),
(60, '211', 'Convencional Amarelo'),
(61, '211', 'Convencional Amarelo com Ar'),
(62, '211', 'Micro'),
(63, '211', 'Micro com Ar'),
(64, '211', 'Leve'),
(65, '213', 'Convencional Amarelo'),
(66, '213', 'Convencional Amarelo com Ar'),
(67, '213', 'SuperBus'),
(68, '202', 'Convencional Amarelo'),
(69, '202', 'Convencional Amarelo com Ar'),
(70, '202', 'SuperBus'),
(71, '205', 'Convencional Amarelo'),
(72, '205', 'Convencional Amarelo com Ar'),
(73, '205', 'Micro'),
(74, '205', 'Micro com Ar'),
(75, '205', 'SuperBus'),
(76, '214', 'Convencional Amarelo'),
(77, '214', 'Convencional Amarelo com Ar'),
(78, '214', 'Micro'),
(79, '214', 'Micro com Ar'),
(80, '214', 'Leve'),
(81, '215', 'Micro'),
(82, '215', 'Micro com Ar'),
(83, '215', 'Leve'),
(84, '216', 'Micro'),
(85, '217', 'Convencional Amarelo'),
(86, '217', 'Convencional Amarelo com Ar'),
(87, '217', 'Micro'),
(88, '217', 'Micro com Ar'),
(89, '217', 'SuperBus'),
(90, '218', 'Micro'),
(91, '218', 'Micro com Ar'),
(92, '219', 'Convencional Amarelo'),
(93, '219', 'Micro'),
(94, '219', 'Micro com Ar'),
(95, '219', 'Leve'),
(96, '220', 'Convencional Amarelo'),
(97, '220', 'Micro'),
(98, '220', 'Leve'),
(99, '221', 'Convencional Amarelo'),
(100, '221', 'Micro'),
(101, '221', 'Leve'),
(102, '222', 'Convencional Amarelo'),
(103, '222', 'Convencional Amarelo com Ar'),
(104, '222', 'SuperBus'),
(105, '224', 'Convencional Amarelo'),
(106, '224', 'Convencional Amarelo com Ar'),
(107, '224', 'Micro'),
(108, '224', 'Micro com Ar'),
(109, '224', 'Convencional Azul'),
(110, '224', 'Convencional Azul com Ar'),
(111, '226', 'Convencional Amarelo'),
(112, '226', 'Convencional Amarelo com Ar'),
(113, '226', 'Micro'),
(114, '226', 'Micro com Ar'),
(115, '226', 'Leve'),
(116, '227', 'Convencional Amarelo'),
(117, '227', 'Micro'),
(118, '227', 'Convencional Azul'),
(119, '228', 'Convencional Amarelo'),
(120, '228', 'Convencional Amarelo com Ar'),
(121, '228', 'SuperBus'),
(122, '229', 'Convencional Amarelo'),
(123, '229', 'Convencional Amarelo com Ar'),
(124, '231', 'Convencional Amarelo'),
(125, '231', 'Convencional Amarelo com Ar'),
(126, '250', 'Convencional Amarelo'),
(127, '250', 'Micro'),
(128, '260', 'Micro'),
(129, '265', 'Convencional Amarelo'),
(130, '265', 'Micro'),
(131, '265', 'Leve'),
(132, '270', 'Micro'),
(133, '271', 'Micro'),
(134, '272', 'Convencional Amarelo'),
(135, '272', 'Convencional Amarelo com Ar'),
(136, '275', 'Convencional Amarelo'),
(137, '275', 'Micro'),
(138, '280', 'Micro'),
(139, '285', 'Convencional Amarelo'),
(140, '285', 'Leve'),
(141, '290', 'Convencional Amarelo'),
(145, '295', 'Convencional Amarelo'),
(146, '296', 'Micro'),
(147, '408L', 'Micro'),
(148, '408L', 'Leve'),
(172, '603', 'Convencional Amarelo'),
(173, '603', 'Convencional Amarelo com Ar'),
(174, '602', 'Convencional Amarelo'),
(175, '602', 'Convencional Amarelo com Ar'),
(176, '601', 'Convencional Amarelo'),
(177, '601', 'Convencional Amarelo com Ar'),
(183, '804L', 'Convencional Amarelo'),
(184, '804L', 'Leve'),
(188, '905', 'Micro'),
(189, '905', 'Micro com Ar'),
(190, '906', 'Convencional Azul'),
(191, '906', 'Convencional Azul com Ar'),
(192, '906', 'Padron Azul'),
(193, '906', 'SuperBus'),
(194, '906', 'Leve'),
(195, '806L', 'Convencional Azul'),
(196, '806L', 'Convencional Azul com Ar'),
(197, '806L', 'Padron Azul'),
(198, '806L', 'SuperBus'),
(199, '802L', 'Convencional Azul'),
(200, '802L', 'Convencional Azul com Ar'),
(201, '802L', 'Padron Azul'),
(202, '802L', 'SuperBus'),
(203, '801L', 'Convencional Azul'),
(204, '801L', 'Convencional Azul com Ar'),
(205, '801L', 'Padron Azul'),
(206, '801L', 'SuperBus'),
(207, '800L', 'Convencional Azul'),
(208, '800L', 'Convencional Azul com Ar'),
(209, '800L', 'Padron Azul'),
(210, '800L', 'SuperBus'),
(211, '600', 'Convencional Amarelo'),
(212, '600', 'Convencional Amarelo com Ar'),
(213, '600', 'Convencional Azul'),
(214, '600', 'Convencional Azul com Ar'),
(215, '600', 'Padron Azul'),
(216, '600', 'SuperBus'),
(217, '907', 'Convencional Azul'),
(218, '907', 'Convencional Azul com Ar'),
(219, '907', 'Padron Azul'),
(220, '907', 'SuperBus'),
(221, '913L', 'Convencional Azul'),
(222, '913L', 'Convencional Azul com Ar'),
(223, '913L', 'Padron Azul'),
(224, '913L', 'SuperBus');

-- --------------------------------------------------------

--
-- Estrutura para tabela `metas_planilha_tarifaria`
--

CREATE TABLE `metas_planilha_tarifaria` (
  `id` int(11) NOT NULL,
  `ano` int(4) NOT NULL,
  `mes` int(2) NOT NULL,
  `km_meta` decimal(12,2) DEFAULT 0.00,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `registros_comunicacao`
--

CREATE TABLE `registros_comunicacao` (
  `id` int(11) NOT NULL,
  `data_csv` date DEFAULT NULL COMMENT 'Coluna Data do CSV',
  `vehicle` varchar(50) DEFAULT NULL COMMENT 'Coluna Veículo',
  `motorista_matricula_raw` varchar(255) DEFAULT NULL COMMENT 'Coluna Motorista - Matrícula (original)',
  `bloco` varchar(50) DEFAULT NULL COMMENT 'Coluna Bloco',
  `linha` varchar(100) DEFAULT NULL COMMENT 'Coluna Linha',
  `sentido` varchar(50) DEFAULT NULL COMMENT 'Coluna Sentido',
  `data_evento_inicio` datetime DEFAULT NULL COMMENT 'Coluna Data e Hora - Perda de Sinal',
  `data_evento_fim` datetime DEFAULT NULL COMMENT 'Coluna Data e Hora - Restauração de Sinal',
  `duracao_offline_seg` int(11) DEFAULT 0 COMMENT 'Coluna Tempo Total (convertido para segundos)',
  `latitude` varchar(20) DEFAULT NULL COMMENT 'Coluna Latitude',
  `longitude` varchar(20) DEFAULT NULL COMMENT 'Coluna Longitude',
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Registos de perda de comunicação do Clever Reports';

-- --------------------------------------------------------

--
-- Estrutura para tabela `registros_gerais`
--

CREATE TABLE `registros_gerais` (
  `id` int(11) NOT NULL,
  `depot` varchar(100) DEFAULT NULL,
  `block` varchar(50) DEFAULT NULL,
  `route` varchar(100) DEFAULT NULL,
  `direction` varchar(50) DEFAULT NULL,
  `variation` int(11) DEFAULT NULL,
  `operator` varchar(100) DEFAULT NULL,
  `vehicle` varchar(50) DEFAULT NULL,
  `trip` varchar(100) DEFAULT NULL,
  `nome_parada` varchar(255) DEFAULT NULL,
  `chegada_programada` datetime DEFAULT NULL,
  `chegada_real` datetime DEFAULT NULL,
  `desvio_chegada` time DEFAULT NULL,
  `partida_programada` datetime DEFAULT NULL,
  `partida_real` datetime DEFAULT NULL,
  `desvio_partida` time DEFAULT NULL,
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `carro_atual` varchar(9) DEFAULT NULL,
  `ocorrencia` varchar(100) DEFAULT NULL,
  `incidente` varchar(100) DEFAULT NULL,
  `socorro` tinyint(1) NOT NULL DEFAULT 0,
  `horario_linha` varchar(5) DEFAULT NULL,
  `terminal` varchar(50) DEFAULT NULL,
  `carro_pos` varchar(9) DEFAULT NULL,
  `monitor` varchar(50) DEFAULT NULL,
  `fiscal` varchar(50) DEFAULT NULL,
  `observacao` varchar(250) DEFAULT NULL,
  `timestamp_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `registros_odometro_life`
--

CREATE TABLE `registros_odometro_life` (
  `id` int(11) NOT NULL,
  `data_leitura` datetime NOT NULL COMMENT 'Coluna Data',
  `vehicle` varchar(50) NOT NULL COMMENT 'Coluna Veiculo',
  `funcionario_raw` varchar(255) DEFAULT NULL COMMENT 'Coluna Funcionário (original)',
  `evento` varchar(100) DEFAULT NULL COMMENT 'Coluna Evento',
  `odometro_km` decimal(10,1) DEFAULT NULL COMMENT 'Coluna Odômetro',
  `odometro_fator_correcao` decimal(10,1) DEFAULT NULL COMMENT 'Coluna Odômetro Fator Correção',
  `fator_correcao` varchar(50) DEFAULT NULL COMMENT 'Coluna Fator de Correção',
  `consumo` decimal(10,1) DEFAULT NULL COMMENT 'Coluna Consumo',
  `localizacao` varchar(100) DEFAULT NULL COMMENT 'Coluna Localização',
  `botton` varchar(50) DEFAULT NULL COMMENT 'Coluna Botton',
  `pcid` varchar(50) DEFAULT NULL COMMENT 'Coluna PCID',
  `unidade_empresarial` varchar(100) DEFAULT NULL COMMENT 'Coluna Unid. Empresarial',
  `data_gravacao` datetime DEFAULT NULL COMMENT 'Coluna Data Gravação',
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Leituras do odômetro do rastreador Life';

-- --------------------------------------------------------

--
-- Estrutura para tabela `registros_timepoint_geral`
--

CREATE TABLE `registros_timepoint_geral` (
  `id` int(11) NOT NULL,
  `data_evento` date DEFAULT NULL,
  `vehicle` varchar(50) DEFAULT NULL,
  `bloco` varchar(50) DEFAULT NULL,
  `workid` varchar(50) DEFAULT NULL,
  `operator` varchar(100) DEFAULT NULL,
  `matricula` int(6) DEFAULT NULL,
  `route` varchar(255) DEFAULT NULL,
  `ponto_controle` varchar(255) DEFAULT NULL,
  `sentido_via` varchar(255) DEFAULT NULL,
  `direcao` varchar(50) DEFAULT NULL,
  `horario_real` datetime DEFAULT NULL,
  `horario_programado` datetime DEFAULT NULL,
  `desvio_csv` time DEFAULT NULL,
  `is_last_tp` tinyint(1) DEFAULT NULL,
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_bilhetagem`
--

CREATE TABLE `relatorios_bilhetagem` (
  `id` int(11) NOT NULL,
  `linha` varchar(100) DEFAULT NULL,
  `data_viagem` date DEFAULT NULL,
  `viagens` int(11) DEFAULT 0,
  `frota` int(11) DEFAULT 0,
  `bonus` decimal(10,2) DEFAULT 0.00,
  `comum` decimal(10,2) DEFAULT 0.00,
  `contactless` decimal(10,2) DEFAULT 0.00,
  `emv` decimal(10,2) DEFAULT 0.00,
  `escolar_100` decimal(10,2) DEFAULT 0.00,
  `escolar_duplo` decimal(10,2) DEFAULT 0.00,
  `escolar` decimal(10,2) DEFAULT 0.00,
  `funcionario` decimal(10,2) DEFAULT 0.00,
  `gratuitos` decimal(10,2) DEFAULT 0.00,
  `integracao` decimal(10,2) DEFAULT 0.00,
  `pagantes` decimal(10,2) DEFAULT 0.00,
  `vale_transporte` decimal(10,2) DEFAULT 0.00,
  `total_passageiros` decimal(10,2) DEFAULT 0.00,
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_divisao`
--

CREATE TABLE `relatorios_divisao` (
  `id` int(11) NOT NULL,
  `division` varchar(100) DEFAULT NULL,
  `vehicle` varchar(50) DEFAULT NULL,
  `data_viagem` date DEFAULT NULL,
  `manual_duracao_seg` int(11) DEFAULT 0,
  `manual_distancia_km` decimal(10,2) DEFAULT 0.00,
  `off_duracao_seg` int(11) DEFAULT 0,
  `off_distancia_km` decimal(10,2) DEFAULT 0.00,
  `scheduled_duracao_seg` int(11) DEFAULT 0,
  `scheduled_distancia_km` decimal(10,2) DEFAULT 0.00,
  `unknown_duracao_seg` int(11) DEFAULT 0,
  `unknown_distancia_km` decimal(10,2) DEFAULT 0.00,
  `total_duracao_seg` int(11) DEFAULT 0,
  `total_distancia_km` decimal(10,2) DEFAULT 0.00,
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_frota`
--

CREATE TABLE `relatorios_frota` (
  `id` int(11) NOT NULL,
  `depot` varchar(100) DEFAULT NULL COMMENT 'CSV Col 0',
  `vehicle` varchar(50) DEFAULT NULL COMMENT 'CSV Col 1',
  `operator` varchar(100) DEFAULT NULL COMMENT 'CSV Col 2 (Nome)',
  `matricula` varchar(20) DEFAULT NULL COMMENT 'CSV Col 2 (Matrícula)',
  `data_viagem` date DEFAULT NULL COMMENT 'CSV Col 3',
  `route` varchar(100) DEFAULT NULL COMMENT 'CSV Col 4',
  `tempo_exec_p_seg` int(11) DEFAULT 0 COMMENT 'CSV Col 5',
  `tempo_exec_r_seg` int(11) DEFAULT 0 COMMENT 'CSV Col 6',
  `distancia_p_km` decimal(10,2) DEFAULT 0.00 COMMENT 'CSV Col 7',
  `distancia_r_km` decimal(10,2) DEFAULT 0.00 COMMENT 'CSV Col 8',
  `tempo_produtivo_p_seg` int(11) DEFAULT 0 COMMENT 'CSV Col 9',
  `tempo_produtivo_r_seg` int(11) DEFAULT 0 COMMENT 'CSV Col 10',
  `distancia_produtiva_p_km` decimal(10,2) DEFAULT 0.00 COMMENT 'CSV Col 11',
  `distancia_produtiva_r_km` decimal(10,2) DEFAULT 0.00 COMMENT 'CSV Col 12',
  `tempo_recolha_p_seg` int(11) DEFAULT 0 COMMENT 'CSV Col 13',
  `tempo_recolha_r_seg` int(11) DEFAULT 0 COMMENT 'CSV Col 14',
  `distancia_recolha_p_km` decimal(10,2) DEFAULT 0.00 COMMENT 'CSV Col 15',
  `distancia_recolha_r_km` decimal(10,2) DEFAULT 0.00 COMMENT 'CSV Col 16',
  `tempo_ocioso_p_seg` int(11) DEFAULT 0 COMMENT 'CSV Col 17',
  `tempo_ocioso_r_seg` int(11) DEFAULT 0 COMMENT 'CSV Col 18',
  `distancia_ociosa_p_km` decimal(10,2) DEFAULT 0.00 COMMENT 'CSV Col 19',
  `distancia_ociosa_r_km` decimal(10,2) DEFAULT 0.00 COMMENT 'CSV Col 20',
  `tempo_ocioso_total_r_seg` int(11) DEFAULT 0 COMMENT 'CSV Col 21',
  `distancia_ociosa_total_r_km` decimal(10,2) DEFAULT 0.00 COMMENT 'CSV Col 22',
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_icv_ipv`
--

CREATE TABLE `relatorios_icv_ipv` (
  `id` int(11) NOT NULL,
  `data_relatorio` date NOT NULL,
  `viagens_programadas` int(11) DEFAULT NULL,
  `viagens_realizadas` int(11) DEFAULT NULL,
  `viagens_atrasadas` int(11) DEFAULT NULL,
  `viagens_adiantadas` int(11) DEFAULT NULL,
  `viagens_suprimidas` int(11) DEFAULT NULL,
  `icv_percent` decimal(5,2) DEFAULT NULL,
  `icv_actual_percent` decimal(5,2) DEFAULT NULL,
  `ipv_percent` decimal(5,2) DEFAULT NULL,
  `ipv_trip_percent` decimal(5,2) DEFAULT NULL,
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_km_noxxon_diario`
--

CREATE TABLE `relatorios_km_noxxon_diario` (
  `id` int(11) NOT NULL,
  `vehicle` varchar(50) NOT NULL COMMENT 'Coluna Prefixo',
  `data_leitura` date NOT NULL COMMENT 'Coluna data (convertida)',
  `km_inicial` decimal(10,1) DEFAULT NULL COMMENT 'Coluna Km Inicial',
  `km_final` decimal(10,1) DEFAULT NULL COMMENT 'Coluna Km Final',
  `km_percorrido` decimal(10,1) DEFAULT NULL COMMENT 'Coluna Km Percorrido',
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='KM diário da telemetria Noxxon';

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_km_roleta`
--

CREATE TABLE `relatorios_km_roleta` (
  `id` int(11) NOT NULL,
  `vehicle` varchar(50) NOT NULL,
  `mes_ano` date NOT NULL COMMENT 'Armazena o primeiro dia do mês de referência',
  `km_inicial` int(11) DEFAULT NULL,
  `km_final` int(11) DEFAULT NULL,
  `km_total_roleta` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_km_roleta_diario`
--

CREATE TABLE `relatorios_km_roleta_diario` (
  `id` int(11) NOT NULL,
  `vehicle` varchar(50) NOT NULL COMMENT 'Coluna CARRO do CSV',
  `data_leitura` date NOT NULL COMMENT 'Data extraída do nome do ficheiro',
  `km_inicial` decimal(10,1) DEFAULT NULL COMMENT 'Coluna KM INICIAL do CSV',
  `km_final` decimal(10,1) DEFAULT NULL COMMENT 'Coluna KM FINAL do CSV',
  `total` int(11) NOT NULL,
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='KM diário registado nas fichas de roleta';

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_on_time`
--

CREATE TABLE `relatorios_on_time` (
  `id` int(11) NOT NULL,
  `data_relatorio` date NOT NULL,
  `no_horario_percent` decimal(5,2) DEFAULT NULL,
  `adiantado_percent` decimal(5,2) DEFAULT NULL,
  `atrasado_percent` decimal(5,2) DEFAULT NULL,
  `timepoints_processados` int(11) DEFAULT NULL,
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_servicos`
--

CREATE TABLE `relatorios_servicos` (
  `id` int(11) NOT NULL,
  `VERSION` varchar(50) DEFAULT NULL,
  `PROJECT_NAME` varchar(255) DEFAULT NULL,
  `PROJUNIT_NAME` varchar(100) DEFAULT NULL,
  `DUTY_COMPANYCODE` int(11) DEFAULT NULL COMMENT 'Serviço/WorkID',
  `DUTY_ID` varchar(100) DEFAULT NULL,
  `PIECETYPE_NAME` varchar(100) DEFAULT NULL,
  `REFERREDVB_COMPANYCODE` varchar(50) DEFAULT NULL COMMENT 'Bloco para linkar com viagens',
  `PRETIMESEC` int(11) DEFAULT 0,
  `POSTTIMESEC` int(11) DEFAULT 0,
  `STARTNODE_COMPANYCODE` varchar(100) DEFAULT NULL,
  `STARTNODE_BASIN` varchar(100) DEFAULT NULL,
  `STARTNODE_COMPANY` varchar(100) DEFAULT NULL,
  `ENDNODE_COMPANYCODE` varchar(100) DEFAULT NULL,
  `ENDNODE_BASIN` varchar(100) DEFAULT NULL,
  `ENDNODE_COMPANY` varchar(100) DEFAULT NULL,
  `START_TIME` time DEFAULT NULL,
  `END_TIME` time DEFAULT NULL,
  `data_inicio_vigencia` date DEFAULT NULL COMMENT 'Vigência (do formulário)',
  `data_fim_vigencia` date DEFAULT NULL COMMENT 'Vigência (do formulário)',
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabelas de Serviços/WorkIDs (Turni Guida)';

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_servicos_ajuste`
--

CREATE TABLE `relatorios_servicos_ajuste` (
  `id` int(11) NOT NULL,
  `VERSION` varchar(50) DEFAULT NULL,
  `PROJECT_NAME` varchar(255) DEFAULT NULL,
  `PROJUNIT_NAME` varchar(100) DEFAULT NULL,
  `DUTY_COMPANYCODE` int(11) DEFAULT NULL,
  `DUTY_ID` varchar(100) DEFAULT NULL,
  `PIECETYPE_NAME` varchar(100) DEFAULT NULL,
  `REFERREDVB_COMPANYCODE` varchar(50) DEFAULT NULL,
  `PRETIMESEC` int(11) DEFAULT 0,
  `POSTTIMESEC` int(11) DEFAULT 0,
  `STARTNODE_COMPANYCODE` varchar(100) DEFAULT NULL,
  `STARTNODE_BASIN` varchar(100) DEFAULT NULL,
  `STARTNODE_COMPANY` varchar(100) DEFAULT NULL,
  `ENDNODE_COMPANYCODE` varchar(100) DEFAULT NULL,
  `ENDNODE_BASIN` varchar(100) DEFAULT NULL,
  `ENDNODE_COMPANY` varchar(100) DEFAULT NULL,
  `START_TIME` time DEFAULT NULL,
  `END_TIME` time DEFAULT NULL,
  `data_inicio_vigencia` date DEFAULT NULL,
  `data_fim_vigencia` date DEFAULT NULL,
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabelas de Serviços (Ajuste Operacional)';

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_todos_horarios`
--

CREATE TABLE `relatorios_todos_horarios` (
  `id` int(11) NOT NULL,
  `data_viagem` date NOT NULL COMMENT 'Data extraída do nome do ficheiro',
  `SERVICELEVEL` varchar(50) DEFAULT NULL,
  `CALENDAR` varchar(100) DEFAULT NULL,
  `OPERATINGDAY` varchar(50) DEFAULT NULL,
  `PIECETYPE` varchar(50) DEFAULT NULL,
  `STARTINGDATE` date DEFAULT NULL,
  `ENDINGDATE` date DEFAULT NULL,
  `LINE` varchar(20) DEFAULT NULL,
  `LINEBASIN` varchar(50) DEFAULT NULL,
  `LINECOMPANY` varchar(50) DEFAULT NULL,
  `PATTERN` varchar(100) DEFAULT NULL,
  `DIRECTION` varchar(10) DEFAULT NULL,
  `TRIPCODE` varchar(50) DEFAULT NULL,
  `TRIPCOMPANYCODE` varchar(50) DEFAULT NULL,
  `ACTIVITYNUMBER` varchar(50) DEFAULT NULL,
  `LENGTH` decimal(10,4) DEFAULT NULL,
  `ARRIVALTIME` time DEFAULT NULL,
  `DEPARTURETIME` time DEFAULT NULL,
  `NODE` varchar(50) DEFAULT NULL,
  `NODEBASIN` varchar(50) DEFAULT NULL,
  `NODECOMPANY` varchar(50) DEFAULT NULL,
  `PASSAGEORDER` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_viagens`
--

CREATE TABLE `relatorios_viagens` (
  `id` int(11) NOT NULL,
  `data_viagem` date NOT NULL COMMENT 'Data extraída do nome do ficheiro',
  `BLOCK_NUMBER` varchar(50) DEFAULT NULL COMMENT 'Bloco para linkar com serviços',
  `TRIP_CC` int(11) DEFAULT NULL,
  `START_TIME` time DEFAULT NULL,
  `END_TIME` time DEFAULT NULL,
  `START_PLACE` varchar(255) DEFAULT NULL COMMENT 'Local de início da viagem (do CSV)',
  `END_PLACE` varchar(255) DEFAULT NULL COMMENT 'Local de fim da viagem (do CSV)',
  `ROUTE_ID` varchar(50) DEFAULT NULL COMMENT 'Linha',
  `ROUTE_VARIANT` varchar(50) DEFAULT NULL COMMENT 'Via',
  `DIRECTION_NUM` int(2) DEFAULT NULL COMMENT '0=Ida, 1=Volta',
  `DISTANCE` decimal(10,1) DEFAULT NULL COMMENT 'Distância em METROS (ex: 13363.5)',
  `TRIP_ID` int(11) DEFAULT NULL COMMENT '0 = Ociosa',
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabelas de Viagens Diárias (baseado em data)';

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_viagens_ajuste`
--

CREATE TABLE `relatorios_viagens_ajuste` (
  `id` int(11) NOT NULL,
  `data_viagem` date NOT NULL COMMENT 'Data extraída do nome do ficheiro',
  `BLOCK_NUMBER` varchar(50) DEFAULT NULL,
  `TRIP_CC` int(11) DEFAULT NULL,
  `START_TIME` time DEFAULT NULL,
  `END_TIME` time DEFAULT NULL,
  `START_PLACE` varchar(255) DEFAULT NULL,
  `END_PLACE` varchar(255) DEFAULT NULL,
  `ROUTE_ID` varchar(50) DEFAULT NULL,
  `ROUTE_VARIANT` varchar(50) DEFAULT NULL,
  `DIRECTION_NUM` int(2) DEFAULT NULL,
  `DISTANCE` decimal(10,1) DEFAULT NULL,
  `TRIP_ID` int(11) DEFAULT NULL,
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabelas de Viagens (Ajuste Operacional)';

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorios_viagens_precisao`
--

CREATE TABLE `relatorios_viagens_precisao` (
  `id` int(11) NOT NULL,
  `data_viagem` date NOT NULL,
  `duty_companycode` varchar(50) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `start_node` varchar(100) DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `end_node` varchar(100) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `length_km` decimal(15,6) DEFAULT 0.000000,
  `vehicle_block` varchar(50) DEFAULT NULL,
  `line_code` varchar(50) DEFAULT NULL,
  `pattern` varchar(100) DEFAULT NULL,
  `direction` varchar(20) DEFAULT NULL,
  `is_produtivo` tinyint(1) DEFAULT 0,
  `is_ocioso` tinyint(1) DEFAULT 0,
  `data_importacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `rotas_geometria`
--

CREATE TABLE `rotas_geometria` (
  `codigo_variante` varchar(50) NOT NULL,
  `linha` varchar(10) NOT NULL,
  `sentido` tinyint(1) DEFAULT 0,
  `cor_hex` varchar(7) DEFAULT '#0000FF',
  `pontos_json` longtext NOT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `cadastros_locais`
--
ALTER TABLE `cadastros_locais`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_company_code` (`company_code`);

--
-- Índices de tabela `cadastros_vias`
--
ALTER TABLE `cadastros_vias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_codigo` (`codigo`),
  ADD KEY `idx_linha_agrupamento` (`linha`);

--
-- Índices de tabela `feriados`
--
ALTER TABLE `feriados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_data_feriado` (`data_feriado`);

--
-- Índices de tabela `linhas_veiculos`
--
ALTER TABLE `linhas_veiculos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_linha` (`linha_numero`);

--
-- Índices de tabela `metas_planilha_tarifaria`
--
ALTER TABLE `metas_planilha_tarifaria`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_ano_mes` (`ano`,`mes`);

--
-- Índices de tabela `registros_comunicacao`
--
ALTER TABLE `registros_comunicacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vehicle_data_inicio` (`vehicle`,`data_evento_inicio`),
  ADD KEY `idx_data_csv` (`data_csv`);

--
-- Índices de tabela `registros_gerais`
--
ALTER TABLE `registros_gerais`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `registros_ocorrencias`
--
ALTER TABLE `registros_ocorrencias`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `registros_odometro_life`
--
ALTER TABLE `registros_odometro_life`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vehicle_data` (`vehicle`,`data_leitura`);

--
-- Índices de tabela `registros_timepoint_geral`
--
ALTER TABLE `registros_timepoint_geral`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_matricula` (`matricula`),
  ADD KEY `idx_data_evento` (`data_evento`);

--
-- Índices de tabela `relatorios_bilhetagem`
--
ALTER TABLE `relatorios_bilhetagem`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_linha_data` (`linha`,`data_viagem`),
  ADD KEY `idx_data` (`data_viagem`);

--
-- Índices de tabela `relatorios_divisao`
--
ALTER TABLE `relatorios_divisao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_data_viagem` (`data_viagem`),
  ADD KEY `idx_division` (`division`),
  ADD KEY `idx_vehicle_data` (`vehicle`,`data_viagem`);

--
-- Índices de tabela `relatorios_frota`
--
ALTER TABLE `relatorios_frota`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_data_viagem` (`data_viagem`),
  ADD KEY `idx_vehicle` (`vehicle`),
  ADD KEY `idx_route` (`route`),
  ADD KEY `idx_operator` (`operator`);

--
-- Índices de tabela `relatorios_icv_ipv`
--
ALTER TABLE `relatorios_icv_ipv`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `data_relatorio` (`data_relatorio`);

--
-- Índices de tabela `relatorios_km_noxxon_diario`
--
ALTER TABLE `relatorios_km_noxxon_diario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicle_data` (`vehicle`,`data_leitura`) COMMENT 'Garante um registo por carro por dia';

--
-- Índices de tabela `relatorios_km_roleta`
--
ALTER TABLE `relatorios_km_roleta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicle_mes_ano` (`vehicle`,`mes_ano`);

--
-- Índices de tabela `relatorios_km_roleta_diario`
--
ALTER TABLE `relatorios_km_roleta_diario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicle_data` (`vehicle`,`data_leitura`) COMMENT 'Garante um registo por carro por dia';

--
-- Índices de tabela `relatorios_on_time`
--
ALTER TABLE `relatorios_on_time`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `data_relatorio` (`data_relatorio`);

--
-- Índices de tabela `relatorios_servicos`
--
ALTER TABLE `relatorios_servicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_duty_companycode` (`DUTY_COMPANYCODE`),
  ADD KEY `idx_referredvb_companycode` (`REFERREDVB_COMPANYCODE`),
  ADD KEY `idx_vigencia` (`data_inicio_vigencia`,`data_fim_vigencia`),
  ADD KEY `idx_servicos_referencia` (`REFERREDVB_COMPANYCODE`),
  ADD KEY `idx_servicos_duty` (`DUTY_ID`);

--
-- Índices de tabela `relatorios_servicos_ajuste`
--
ALTER TABLE `relatorios_servicos_ajuste`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_duty_cc_ajuste` (`DUTY_COMPANYCODE`),
  ADD KEY `idx_vigencia_ajuste` (`data_inicio_vigencia`,`data_fim_vigencia`);

--
-- Índices de tabela `relatorios_todos_horarios`
--
ALTER TABLE `relatorios_todos_horarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tripcode` (`TRIPCODE`),
  ADD KEY `idx_busca_linha` (`LINE`,`PATTERN`),
  ADD KEY `idx_busca_horario` (`NODE`,`DEPARTURETIME`);

--
-- Índices de tabela `relatorios_viagens`
--
ALTER TABLE `relatorios_viagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_data_viagem` (`data_viagem`),
  ADD KEY `idx_block_number` (`BLOCK_NUMBER`),
  ADD KEY `idx_route_id` (`ROUTE_ID`),
  ADD KEY `idx_viagens_block` (`BLOCK_NUMBER`),
  ADD KEY `idx_viagens_data` (`data_viagem`);

--
-- Índices de tabela `relatorios_viagens_ajuste`
--
ALTER TABLE `relatorios_viagens_ajuste`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_data_viagem_ajuste` (`data_viagem`);

--
-- Índices de tabela `relatorios_viagens_precisao`
--
ALTER TABLE `relatorios_viagens_precisao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_data_viagem` (`data_viagem`),
  ADD KEY `idx_line` (`line_code`),
  ADD KEY `idx_workid` (`duty_companycode`);

--
-- Índices de tabela `rotas_geometria`
--
ALTER TABLE `rotas_geometria`
  ADD PRIMARY KEY (`codigo_variante`),
  ADD KEY `idx_linha` (`linha`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `cadastros_locais`
--
ALTER TABLE `cadastros_locais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=484;

--
-- AUTO_INCREMENT de tabela `cadastros_vias`
--
ALTER TABLE `cadastros_vias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=356;

--
-- AUTO_INCREMENT de tabela `feriados`
--
ALTER TABLE `feriados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT de tabela `linhas_veiculos`
--
ALTER TABLE `linhas_veiculos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=225;

--
-- AUTO_INCREMENT de tabela `metas_planilha_tarifaria`
--
ALTER TABLE `metas_planilha_tarifaria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `registros_comunicacao`
--
ALTER TABLE `registros_comunicacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `registros_gerais`
--
ALTER TABLE `registros_gerais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `registros_ocorrencias`
--
ALTER TABLE `registros_ocorrencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `registros_odometro_life`
--
ALTER TABLE `registros_odometro_life`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `registros_timepoint_geral`
--
ALTER TABLE `registros_timepoint_geral`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `relatorios_bilhetagem`
--
ALTER TABLE `relatorios_bilhetagem`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `relatorios_divisao`
--
ALTER TABLE `relatorios_divisao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `relatorios_frota`
--
ALTER TABLE `relatorios_frota`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `relatorios_icv_ipv`
--
ALTER TABLE `relatorios_icv_ipv`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `relatorios_km_noxxon_diario`
--
ALTER TABLE `relatorios_km_noxxon_diario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `relatorios_km_roleta`
--
ALTER TABLE `relatorios_km_roleta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `relatorios_km_roleta_diario`
--
ALTER TABLE `relatorios_km_roleta_diario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `relatorios_on_time`
--
ALTER TABLE `relatorios_on_time`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `relatorios_servicos`
--
ALTER TABLE `relatorios_servicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `relatorios_servicos_ajuste`
--
ALTER TABLE `relatorios_servicos_ajuste`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `relatorios_todos_horarios`
--
ALTER TABLE `relatorios_todos_horarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `relatorios_viagens`
--
ALTER TABLE `relatorios_viagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `relatorios_viagens_ajuste`
--
ALTER TABLE `relatorios_viagens_ajuste`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `relatorios_viagens_precisao`
--
ALTER TABLE `relatorios_viagens_precisao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
