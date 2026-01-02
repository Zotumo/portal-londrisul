-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 02/01/2026 às 12:31
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
-- Banco de dados: `portal_motorista-v1`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `administradores`
--

CREATE TABLE `administradores` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL COMMENT 'Login único para o admin',
  `email` varchar(255) DEFAULT NULL COMMENT 'Email (opcional)',
  `senha` varchar(255) NOT NULL COMMENT 'Senha criptografada (hash)',
  `nivel_acesso` varchar(50) NOT NULL COMMENT 'Nível de permissão',
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Usuários administrativos';

--
-- Despejando dados para a tabela `administradores`
--

INSERT INTO `administradores` (`id`, `nome`, `username`, `email`, `senha`, `nivel_acesso`, `data_cadastro`) VALUES
(1, 'Pedro', 'admin', 'admin@email.com', '$2y$10$C1tVlN71/4/MgCPAhlICYOoGL0o4tQnj6NbZ0oqiA51pIi54jACuW', 'Administrador', '2025-05-01 18:35:11'),
(2, 'Agente Teste', 'agente', 'agente@email.com', '$2y$10$xgYAuxST7I11MFLiC6G3F.mD8aDWGJvz3YgZ9aj71TeAdZ6A5cpYC', 'Agente de Terminal', '2025-05-01 18:35:11'),
(3, 'Tio Sam', 'sam', 'sam@email.com', '$2y$10$l4HjbjXjjsUUp3A2Vq2D/eGe0z3Rphx0hJzEgsFJqta55jp/BVQDG', 'Gerência', '2025-05-07 01:16:37');

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcoes_operacionais`
--

CREATE TABLE `funcoes_operacionais` (
  `id` int(11) NOT NULL,
  `nome_funcao` varchar(100) NOT NULL COMMENT 'Ex: Motorista Reserva, Agente de Terminal, Porteiro',
  `work_id_prefixo` varchar(10) NOT NULL COMMENT 'Prefixo para o WorkID, ex: RES, AGT, PORT',
  `descricao` text DEFAULT NULL COMMENT 'Descrição da função',
  `locais_permitidos_tipo` enum('Garagem','Terminal','CIOP','Qualquer') DEFAULT NULL COMMENT 'Tipo de local primário (para filtrar selects)',
  `locais_permitidos_ids` varchar(255) DEFAULT NULL COMMENT 'IDs específicos de locais permitidos, separados por vírgula (ex: 1,2,3), ou NULL se qualquer do tipo é permitido ou se não aplicável',
  `local_fixo_id` int(11) DEFAULT NULL COMMENT 'Se a função é SEMPRE em um local específico (FK para locais.id)',
  `turnos_disponiveis` varchar(50) NOT NULL COMMENT 'Turnos possíveis, ex: 01,02,03 ou 01,02',
  `requer_posicao_especifica` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Se necessita de A, B, C no WorkID (ex: CIOP)',
  `max_posicoes_por_turno` int(11) DEFAULT NULL COMMENT 'Quantas posições A, B, C... existem por turno',
  `ignorar_validacao_jornada` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Se TRUE, as validações de horas de motorista de linha não se aplicam',
  `status` enum('ativa','inativa') NOT NULL DEFAULT 'ativa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `funcoes_operacionais`
--

INSERT INTO `funcoes_operacionais` (`id`, `nome_funcao`, `work_id_prefixo`, `descricao`, `locais_permitidos_tipo`, `locais_permitidos_ids`, `local_fixo_id`, `turnos_disponiveis`, `requer_posicao_especifica`, `max_posicoes_por_turno`, `ignorar_validacao_jornada`, `status`) VALUES
(1, 'Motorista Reserva', '000', 'Motorista Reserva', 'Terminal', '2,3,4,5,6,7,9', NULL, '01,02,03', 0, NULL, 0, 'ativa'),
(2, 'Agente de Terminal', 'AGT', 'Agente de Terminal', 'Terminal', '2,3,4,5,6,9', NULL, '01,02', 0, NULL, 1, 'ativa'),
(3, 'Instrutor', 'INST', 'Instrutor', 'Garagem', NULL, 1, '01,02', 0, NULL, 1, 'ativa'),
(4, 'Porteiro', 'PORT', 'Porteiro', 'Terminal', '3,4,5', NULL, '01,02,03', 0, NULL, 1, 'ativa'),
(5, 'Soltura', 'SOLT', 'Soltura', 'Garagem', '1,7', NULL, '01,02,03', 0, NULL, 1, 'ativa'),
(6, 'Catraca', 'CATR', 'Catraca', 'Terminal', '3,4,5', NULL, '01,02,03', 0, NULL, 1, 'ativa'),
(8, 'CIOP Monitoramento', 'CIOP-MON', 'CIOP Monitoramento', 'CIOP', '', 8, '01,02', 1, 3, 1, 'ativa'),
(9, 'CIOP Planejamento', 'CIOP-PLAN', 'CIOP Planejamento', 'CIOP', '', 8, '01', 1, 5, 1, 'ativa');

-- --------------------------------------------------------

--
-- Estrutura para tabela `locais`
--

CREATE TABLE `locais` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `imagem_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `locais`
--

INSERT INTO `locais` (`id`, `nome`, `tipo`, `imagem_path`) VALUES
(1, 'Garcia', 'Garagem', NULL),
(2, 'T. Central', 'Terminal', NULL),
(3, 'T. Irerê', 'Terminal', NULL),
(4, 'T. Acapulco', 'Terminal', NULL),
(5, 'T. Shop', 'Terminal', NULL),
(6, 'T. Vivi', 'Terminal', NULL),
(7, 'Londrisul', 'Garagem', NULL),
(8, 'CIOP', 'CIOP', NULL),
(9, 'T. Oeste', 'Terminal', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens_motorista`
--

CREATE TABLE `mensagens_motorista` (
  `id` int(11) NOT NULL,
  `motorista_id` int(11) NOT NULL,
  `remetente` varchar(100) DEFAULT 'Operacional',
  `assunto` varchar(255) DEFAULT NULL,
  `mensagem` text NOT NULL,
  `data_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_leitura` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `motoristas`
--

CREATE TABLE `motoristas` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `data_contratacao` date DEFAULT NULL,
  `tipo_veiculo` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `matricula` varchar(50) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo' COMMENT 'Status do motorista no sistema',
  `cargo` varchar(100) NOT NULL DEFAULT 'Motorista',
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `motoristas`
--

INSERT INTO `motoristas` (`id`, `nome`, `data_contratacao`, `tipo_veiculo`, `email`, `telefone`, `matricula`, `senha`, `status`, `cargo`, `data_cadastro`) VALUES
(1, 'Pedro Teste', NULL, NULL, NULL, NULL, '12345', '$2y$10$uOnAa2A5WxKgzAlghBI3UueOStzmJMl/uavDges6Wzguwfe.oIKc.', 'ativo', 'Motorista', '2025-04-19 13:55:27'),
(2, 'Pedro', NULL, NULL, NULL, NULL, '78945', '$2y$10$nw96ohDD4pLHjOY.BTOGle6darmnA1QSzhd1B4ghyLrpejDbkdlsC', 'ativo', 'Motorista', '2025-04-19 17:39:19'),
(3, 'Nily', NULL, 'Convencional', NULL, NULL, '15975', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Porteiro', '2025-05-07 02:08:39'),
(4, 'EDSON APARECIDO LOPES', '1995-08-15', 'Convencional', NULL, NULL, '60474', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(5, 'RONALDO VENANCIO DOS SANTOS', '1995-08-22', 'Convencional', NULL, NULL, '60475', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(6, 'RONALDO FORTUNATO', '1997-01-02', 'Convencional', NULL, NULL, '60574', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(7, 'MARCOS ROBERTO DA SILVA', '1997-01-02', 'Convencional', NULL, NULL, '60591', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(8, 'JEOVA TENORIO DA SILVA', '1997-01-02', 'Convencional', NULL, NULL, '60595', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(9, 'EDMILSON GOMES', '1997-01-02', 'Convencional', NULL, NULL, '60601', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(10, 'ARQUIMEDES DA SILVA', '1997-08-02', 'Convencional', NULL, NULL, '60642', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(11, 'LUIZ SERGIO VALDERRAMO', '1999-09-01', 'Convencional', NULL, NULL, '60927', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(12, 'ADELMIRO DE SOUZA SILVA', '1999-08-25', 'Convencional', NULL, NULL, '60955', '$2y$10$fG68w7N045QJECg3O.Ew.eghdGQM9cktcSbdjuisE/zA1/6JmmEIW', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(13, 'LUCIANO BARBOSA', '2000-08-12', 'Convencional', NULL, NULL, '61029', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(14, 'JAIR STUANI', '2001-07-03', 'Convencional', NULL, NULL, '61083', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(15, 'RICARDO FRESCHI', '2002-04-02', 'Convencional', NULL, NULL, '61130', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(16, 'CELSO FERNANDES ALVES', '2002-10-06', 'Convencional', NULL, NULL, '61170', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(17, 'VALDECIR FERREIRA', '2002-09-25', 'Convencional', NULL, NULL, '61194', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(18, 'OSMAR CAETANO', '2003-10-29', 'Convencional', NULL, NULL, '61196', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(19, 'ARTUR RODRIGUES DA SILVA', '2004-01-07', 'Convencional', NULL, NULL, '61319', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(20, 'NIVALDO APARECIDO GONCALVES', '2004-07-26', 'Convencional', NULL, NULL, '61320', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(21, 'PAULO RAMOS DE NADAI', '2004-01-08', 'Convencional', NULL, NULL, '61338', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(22, 'GILBERTO CAMARGO LIMA', '2004-07-08', 'Convencional', NULL, NULL, '61339', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(23, 'CLAUDIO LOPES DE ASSIS', '2004-01-09', 'Convencional', NULL, NULL, '61359', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(24, 'LUIZ CARLOS FERNANDES', '2004-06-10', 'Convencional', NULL, NULL, '61372', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(25, 'FRANCISCO PAULO JOAO', '2006-07-03', 'Convencional', NULL, NULL, '61536', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(26, 'ALBERTO FONTANELA NETO', '2006-11-04', 'Convencional', NULL, NULL, '61552', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(27, 'PAULO CESAR MACHADO', '2006-05-06', 'Convencional', NULL, NULL, '61568', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(28, 'MARIO ROBERTO FERRAZ', '2006-06-28', 'Convencional', NULL, NULL, '61574', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(29, 'EDSON CANDIDO DA COSTA', '2007-04-01', 'Convencional', NULL, NULL, '61614', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(30, 'ADRIANO CESAR CONDE FERREIRA', '2007-05-02', 'Convencional', NULL, NULL, '61625', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(31, 'ELIAS FRANCISCO DA SILVA', '2007-04-16', 'Convencional', NULL, NULL, '61645', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(32, 'JOSE ROBERTO DA COSTA', '2007-10-08', 'Convencional', NULL, NULL, '61683', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(33, 'JOSE ROBERTO GONCALVES DA SILVA', '2008-06-16', 'Convencional', NULL, NULL, '61790', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(34, 'EVERTON ARRUDA DOS ANJOS', '2008-04-09', 'Convencional', NULL, NULL, '61833', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(35, 'DARINHO CANDIDO DA SILVA', '2009-06-01', 'Convencional', NULL, NULL, '61887', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(36, 'LUCIANO ADAO ALVES', '2009-01-14', 'Convencional', NULL, NULL, '61893', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(37, 'CLAUDECIR GARCIA DIAS', '2009-02-02', 'Convencional', NULL, NULL, '61901', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(38, 'LEANDRO CESAR SZULEK', '2009-02-14', 'Convencional', NULL, NULL, '61910', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(39, 'GENIVALDO BENICIO DA SILVA', '2009-06-13', 'Convencional', NULL, NULL, '62007', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(40, 'RONALDO APARECIDO LOUZADO', '2009-10-08', 'Convencional', NULL, NULL, '62034', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(41, 'JOAO BATISTA DOS SANTOS', '2009-04-06', 'Convencional', NULL, NULL, '62044', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(42, 'RAMALIO BATISTA DE LIMA', '2010-01-02', 'Convencional', NULL, NULL, '62053', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(43, 'CLAUDENIR ALVES', '2010-01-02', 'Convencional', NULL, NULL, '62054', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(44, 'GILBERTO DE SOUZA MELO', '2010-07-07', 'Convencional', NULL, NULL, '62061', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(45, 'SILAS DOS REIS', '2011-09-05', 'Convencional', NULL, NULL, '62115', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(46, 'JOSE VENANCIO DA SILVA FILHO', '2011-09-12', 'Convencional', NULL, NULL, '62155', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(47, 'SERGIO GOMES DE PAULA', '2011-12-22', 'Convencional', NULL, NULL, '62158', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(48, 'JOAO CREMONEZZI NETO', '2012-03-22', 'Convencional', NULL, NULL, '62178', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(49, 'ELTON PEDRO DIAS', '2012-04-17', 'Convencional', NULL, NULL, '62184', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(50, 'MARCOS AUGUSTO FERREIRA', '2012-09-05', 'Convencional', NULL, NULL, '62190', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(51, 'EDSON DOS SANTOS', '2012-05-25', 'Convencional', NULL, NULL, '62195', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(52, 'ERNANDES STEIN', '2012-07-14', 'Convencional', NULL, NULL, '62206', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(53, 'EDILSON JOSE DE MOURA', '2012-10-09', 'Convencional', NULL, NULL, '62220', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(54, 'JOSE APARECIDO GUELERE DE LIMA', '2012-10-17', 'Convencional', NULL, NULL, '62224', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(55, 'ODAIR MARTINS', '2012-10-17', 'Convencional', NULL, NULL, '62240', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(56, 'VALDECIR DURANTE', '2013-03-19', 'Convencional', NULL, NULL, '62257', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(57, 'ANDERSON LUIZ MARCELINO', '2013-09-05', 'Convencional', NULL, NULL, '62270', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(58, 'SAMUEL BRAZ DE PROENCA', '2013-01-07', 'Micro', NULL, NULL, '62284', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(59, 'MAGNO LUIZ BARBOSA', '2013-08-26', 'Convencional', NULL, NULL, '62306', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(60, 'VILSON ANTUNES', '2013-09-25', 'Convencional', NULL, NULL, '62311', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(61, 'HORACIO FARINHAKE', '2013-11-11', 'Convencional', NULL, NULL, '62318', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(62, 'GILSON MENDES', '2013-06-12', 'Convencional', NULL, NULL, '62342', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(63, 'EVERTON DE LIMA GRASSI', '2014-07-25', 'Convencional', NULL, NULL, '62383', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(64, 'ANTONIO MARCOS DE BRITO', '2015-01-07', 'Convencional', NULL, NULL, '62414', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(65, 'SILVANO DE CARVALHO SERRA', '2015-10-22', 'Convencional', NULL, NULL, '62434', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(66, 'ANDERSON INACIO', '2013-11-12', 'Convencional', NULL, NULL, '62445', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(67, 'CLAUDECIR NOGUEIRA SOARES', '2016-02-16', 'Convencional', NULL, NULL, '62452', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(68, 'PEDRO TRINDADE', '2016-08-04', 'Convencional', NULL, NULL, '62466', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(69, 'FABIO CESAR DA ROCHA', '2016-12-05', 'Convencional', NULL, NULL, '62468', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(70, 'ROSELY APARECIDA COSTA', '2019-01-02', 'Convencional', NULL, NULL, '62535', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(71, 'ISRAEL FERREIRA PINHO', '2019-06-24', 'Convencional', NULL, NULL, '62554', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(72, 'EDSON VITORIANO DE SOUZA', '2019-02-08', 'Convencional', NULL, NULL, '62560', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(73, 'CLAUDIO VALDECI NEVES', '2019-10-09', 'Convencional', NULL, NULL, '62565', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(74, 'JEFERSON HENRIQUE TADELE', '2019-10-17', 'Convencional', NULL, NULL, '62571', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(75, 'VALDELIR MACHADO RODRIGUES DOS SANTOS', '2019-10-18', 'Convencional', NULL, NULL, '62572', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(76, 'DANIELI PONEZ RIBEIRO DOS SANTOS', '2019-10-18', 'Convencional', NULL, NULL, '62573', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(77, 'PETTERSON CARVALHO SILVA', '2019-10-18', 'Convencional', NULL, NULL, '62574', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(78, 'JOSE MASSONI', '2019-10-18', 'Convencional', NULL, NULL, '62577', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(79, 'WESER MARCOS FELIZARDO', '2019-10-23', 'Convencional', NULL, NULL, '62582', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(80, 'CARMEN JAQUES', '2019-07-11', 'Convencional', NULL, NULL, '62584', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(81, 'JACIRO MENDES DE CAMPOS', '2019-09-12', 'Convencional', NULL, NULL, '62601', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(82, 'CARLOS DEIVES SILVA MARUYAMA', '2019-09-12', 'Convencional', NULL, NULL, '62602', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(83, 'VALDENIR FONSECA SIQUEIRA', '2019-09-12', 'Convencional', NULL, NULL, '62611', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(84, 'PREMISLAU SERAFIM MACHADO', '2019-09-12', 'Convencional', NULL, NULL, '62612', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(85, 'MILTON PEDRO DA SILVA', '2019-09-12', 'Convencional', NULL, NULL, '62634', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(86, 'SEFERINO SCHUARTZ', '2019-09-12', 'Convencional', NULL, NULL, '62635', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(87, 'CELSO BUENO DO AMARAL', '2019-09-12', 'Convencional', NULL, NULL, '62636', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(88, 'SERGIO JERONIMO', '2019-09-12', 'Convencional', NULL, NULL, '62638', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(89, 'MARCOS ANTONIO GUARNIERI', '2019-09-12', 'Convencional', NULL, NULL, '62643', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(90, 'FRANCISCO RAFAEL VARJAO', '2019-09-12', 'Convencional', NULL, NULL, '62646', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(91, 'LUIS FERNANDO SANTIAGO', '2019-09-12', 'Convencional', NULL, NULL, '62659', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(92, 'EDUARDO MARCIANO COSTA', '2019-09-12', 'Convencional', NULL, NULL, '62661', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(93, 'ALEXANDRA APARECIDA RIBEIRO', '2019-09-12', 'Convencional', NULL, NULL, '62665', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(94, 'MARCOS LEANDRO SANTOS', '2019-09-12', 'Convencional', NULL, NULL, '62672', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(95, 'ALEX FELIX FERREIRA', '2019-12-13', 'Convencional', NULL, NULL, '62675', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(96, 'ANDRE ALEXANDRE DE GOES', '2019-12-13', 'Convencional', NULL, NULL, '62682', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(97, 'SEBASTIAO GEREMIAS FILHO', '2019-12-13', 'Convencional', NULL, NULL, '62683', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(98, 'REINALDO ANELI FILHO', '2019-12-13', 'Convencional', NULL, NULL, '62684', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(99, 'HILDO BATISTA CESTARI CORREA', '2019-12-13', 'Convencional', NULL, NULL, '62685', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(100, 'OLDAIR ALVES', '2019-12-13', 'Convencional', NULL, NULL, '62687', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(101, 'DAIANA FAUSTINO BITENCOURT', '2019-12-13', 'Convencional', NULL, NULL, '62688', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(102, 'JOEL GODOI', '2019-12-13', 'Convencional', NULL, NULL, '62690', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(103, 'EDSON ROSA DA SILVA', '2019-12-18', 'Convencional', NULL, NULL, '62727', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(104, 'MAISON BIDOIA CARVALHO MATIAS', '2019-12-18', 'Convencional', NULL, NULL, '62729', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(105, 'ELIZEU BARBOSA', '2019-12-18', 'Convencional', NULL, NULL, '62733', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(106, 'SERGIO HENRIQUE FERREIRA', '2024-11-21', 'Convencional', NULL, NULL, '62740', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(107, 'JOAO TIAGO DE SOUZA', '2019-02-19', 'Convencional', NULL, NULL, '62763', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(108, 'EXPEDITO PEREIRA DE SOUZA', '2020-01-17', 'Convencional', NULL, NULL, '62765', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(109, 'JOSE CARLOS DE SOUZA', '2021-09-27', 'Convencional', NULL, NULL, '62815', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(110, 'DANIEL ALVES DE ALMEIDA', '2021-07-10', 'Convencional', NULL, NULL, '62817', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(111, 'RONALDO ALEXANDRE LOUREANO', '2021-12-11', 'Convencional', NULL, NULL, '62828', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(112, 'GABRIEL APARECIDO MELLO', '2021-01-12', 'Convencional', NULL, NULL, '62833', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(113, 'JOSE CARLOS DE OLIVEIRA', '2021-01-12', 'Convencional', NULL, NULL, '62834', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(114, 'LUIS ANTONIO ALVES', '2021-01-12', 'Convencional', NULL, NULL, '62835', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(115, 'IZAEL DE PAULA GUIMARAES', '2021-12-18', 'Convencional', NULL, NULL, '62843', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(116, 'ANDRE PEREIRA', '2021-12-22', 'Convencional', NULL, NULL, '62844', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(117, 'ELIAS JORGE DE LIMA', '2021-12-18', 'Convencional', NULL, NULL, '62872', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(118, 'ROBSON BATISTA XAVIER', '2022-08-04', 'Convencional', NULL, NULL, '62873', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(119, 'GILBERTO SOUZA MELO', '2022-08-04', 'Convencional', NULL, NULL, '62878', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(120, 'WALTER DOS SANTOS', '2022-04-22', 'Convencional', NULL, NULL, '62882', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(121, 'ANTONIO BERALDO', '0000-00-00', 'Convencional', NULL, NULL, '62883', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(122, 'ALEXANDRO DOMINGUES DE SOUZA', '2022-09-05', 'Convencional', NULL, NULL, '62886', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(123, 'FABIO LUIZ FRANCISCO', '2022-09-05', 'Convencional', NULL, NULL, '62888', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(124, 'JAIR RODOLFO DE OLIVEIRA', '2022-09-05', 'Convencional', NULL, NULL, '62889', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(125, 'PAULO CESAR DA SILVA', '2022-08-07', 'Convencional', NULL, NULL, '62902', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(126, 'EDER APARECIDO DOS SANTOS', '2022-07-20', 'Convencional', NULL, NULL, '62906', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(127, 'CARLOS ALBERTO PEREIRA', '0000-00-00', 'Convencional', NULL, NULL, '62912', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(128, 'ANDRE LUIS FRAGA', '2022-09-08', 'Convencional', NULL, NULL, '62916', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(129, 'ERICSON MERCES RODRIGUES', '2022-08-18', 'Convencional', NULL, NULL, '62917', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(130, 'VALDECI DA SILVA', '2022-08-18', 'Convencional', NULL, NULL, '62919', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(131, 'ROBERTO BARBOSA', '2022-08-18', 'Convencional', NULL, NULL, '62921', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(132, 'ANTONIO VITORIANO DE SOUZA', '2022-09-15', 'Convencional', NULL, NULL, '62927', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(133, 'ARNALDO DE PAULA FERREIRA', '2022-09-26', 'Convencional', NULL, NULL, '62930', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(134, 'MARCELO NUZA DOS SANTOS', '2022-09-26', 'Convencional', NULL, NULL, '62931', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(135, 'ANDRE PEREIRA DO CARMO', '2022-04-10', 'Convencional', NULL, NULL, '62932', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(136, 'LUCAS ZANCOPE', '2022-04-10', 'Convencional', NULL, NULL, '62934', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(137, 'THIAGO RAMOS TARDIOLLI', '2022-05-10', 'Convencional', NULL, NULL, '62937', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(138, 'MARCOS GUIMARAES JULIANO', '2022-10-10', 'Convencional', NULL, NULL, '62938', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(139, 'FRANCISCO MARTINS DE OLIVEIRA JUNIOR', '2022-10-17', 'Convencional', NULL, NULL, '62939', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(140, 'PAULO CESAR BATISTA DINIZ', '2022-10-20', 'Convencional', NULL, NULL, '62942', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(141, 'ELIZEU THEODORO', '2022-03-11', 'Convencional', NULL, NULL, '62944', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(142, 'SIDNEI DO NASCIMENTO', '2023-03-01', 'Convencional', NULL, NULL, '62955', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(143, 'RODRIGO ALVES PEREIRA', '2023-01-23', 'Convencional', NULL, NULL, '62964', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(144, 'CLAUDINEI EVARISTO', '2023-01-23', 'Convencional', NULL, NULL, '62965', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(145, 'FERNANDA ALVES DE LIMA GOES', '2023-01-23', 'Convencional', NULL, NULL, '62967', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(146, 'ROGERIO FERRARO WEISS', '2023-01-26', 'Convencional', NULL, NULL, '62972', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(147, 'JURANDIR LOPES FERREIRA', '2023-01-26', 'Convencional', NULL, NULL, '62973', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(148, 'MILTON SCHMOELLER RODRIGUES', '2023-01-02', 'Convencional', NULL, NULL, '62975', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(149, 'DEMARCIO MACIEL GOES', '2023-01-02', 'Convencional', NULL, NULL, '62976', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(150, 'EDSON VIEIRA DA SILVA', '2023-01-02', 'Convencional', NULL, NULL, '62978', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(151, 'MATHEUS FRESCHI DA SILVA', '2023-06-02', 'Convencional', NULL, NULL, '62983', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(152, 'ADEMILSON FERNANDES DA SILVA', '2023-02-17', 'Convencional', NULL, NULL, '62986', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(153, 'JOAO LUIZ DE SENE', '2023-02-17', 'Convencional', NULL, NULL, '62987', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(154, 'RONALDO APARECIDO DA SILVA', '2023-03-17', 'Convencional', NULL, NULL, '63007', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(155, 'FABIO ADRIANO DA SILVA', '2023-03-17', 'Convencional', NULL, NULL, '63009', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(156, 'DANILO GUILHERME DOS SANTOS', '2023-04-04', 'Convencional', NULL, NULL, '63015', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(157, 'WILLIAN DOUMIT MENEZES TRAD', '2023-04-04', 'Convencional', NULL, NULL, '63018', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(158, 'BRUNO MAISON VIEIRA DE AMORIM', '2023-04-04', 'Convencional', NULL, NULL, '63019', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(159, 'SANDRO ROGERIO DE ABREU FANTE', '2023-04-04', 'Convencional', NULL, NULL, '63022', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(160, 'ADRIANO APARECIDO FUMEGALI', '2023-04-18', 'Convencional', NULL, NULL, '63030', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(161, 'GILSON ALVES DA SILVA', '2023-10-05', 'Convencional', NULL, NULL, '63041', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(162, 'HELDER SARABIA DA SILVA', '2023-11-05', 'Convencional', NULL, NULL, '63042', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(163, 'ELIAS FERREIRA DE SA', '2023-11-05', 'Convencional', NULL, NULL, '63044', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(164, 'MARCO VINICIUS DIAS', '2023-01-06', 'Convencional', NULL, NULL, '63050', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(165, 'DOUGLAS AZEVEDO DA ENCARNACAO', '2023-06-26', 'Convencional', NULL, NULL, '63065', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(166, 'JOSE PERDIGAO PEREIRA NETO', '2023-08-21', 'Convencional', NULL, NULL, '63088', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(167, 'JEFFERSON DA SILVA', '2023-08-21', 'Convencional', NULL, NULL, '63089', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(168, 'SERGIO PERCINOTO', '2023-08-21', 'Convencional', NULL, NULL, '63093', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(169, 'AMAURY PLATH', '2023-04-09', 'Micro', NULL, NULL, '63096', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(170, 'SERGIO NICACIO DA SILVA', '2023-04-10', 'Convencional', NULL, NULL, '63111', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(171, 'ANTONIO MARCOS VIEIRA', '2023-04-10', 'Convencional', NULL, NULL, '63112', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(172, 'RODRIGO VITOR DE SOUZA', '2023-05-10', 'Convencional', NULL, NULL, '63115', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(173, 'FERNANDO MENDES', '2008-04-14', 'Convencional', NULL, NULL, '63117', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(174, 'THIAGO RIBEIRO DOS SANTOS', '2023-10-13', 'Convencional', NULL, NULL, '63120', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(175, 'HELTON CAVALLARI PAIM', '2023-10-23', 'Convencional', NULL, NULL, '63123', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(176, 'PAULO RUBETUSSO', '2023-06-11', 'Convencional', NULL, NULL, '63130', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(177, 'LUIZ FERNANDO SALCEDO', '2023-11-24', 'Convencional', NULL, NULL, '63135', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(178, 'ROGERIO OLIVEIRA DA SILVA', '2024-07-02', 'Convencional', NULL, NULL, '63142', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(179, 'MATHEUS DUARTE DAUTA', '2022-09-23', 'Convencional', NULL, NULL, '63143', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(180, 'FRANKLIN ANTONIO DE CASTRO VERAS', '2024-04-03', 'Convencional', NULL, NULL, '63152', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(181, 'THIAGO RODRIGUES DOS SANTOS', '2024-04-03', 'Convencional', NULL, NULL, '63153', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(182, 'JOAO CICERO VIEIRA', '2024-04-03', 'Convencional', NULL, NULL, '63154', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(183, 'JULIO CESAR PIRES DOS SANTOS', '2024-03-18', 'Convencional', NULL, NULL, '63159', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(184, 'AVANIR FERREIRA BORGES', '2024-03-18', 'Convencional', NULL, NULL, '63160', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(185, 'PATRICIA DE SOUZA SANTANA', '2024-03-18', 'Convencional', NULL, NULL, '63162', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(186, 'ALESSANDRO MOURA BOLETI', '2024-03-18', 'Convencional', NULL, NULL, '63164', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(187, 'REINALDO CAMARGO FRACA', '2024-02-04', 'Convencional', NULL, NULL, '63168', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(188, 'MAURO RUBENS AMARANTES', '2024-02-04', 'Convencional', NULL, NULL, '63169', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(189, 'ODAIR DIAS', '2024-02-04', 'Convencional', NULL, NULL, '63170', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(190, 'RONALDO CARDOSO', '2024-02-04', 'Micro', NULL, NULL, '63172', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(191, 'ALLITON ANTONIO DELGADO DE OLIVEIRA', '2024-02-04', 'Convencional', NULL, NULL, '63174', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(192, 'EVERTON ARAUJO DE SOUZA', '2024-03-04', 'Convencional', NULL, NULL, '63175', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(193, 'JORGE LUIZ DOS SANTOS', '2024-03-04', 'Convencional', NULL, NULL, '63176', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(194, 'RODRIGO DE HOLANDA AMORIM', '2024-11-04', 'Convencional', NULL, NULL, '63179', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(195, 'ERASMO FIGUEIRA GOMES DA SILVA', '2024-11-04', 'Convencional', NULL, NULL, '63180', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(196, 'MARCELO APARECIDO DE SOUZA', '2024-10-05', 'Convencional', NULL, NULL, '63185', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(197, 'MATHEUS ALVES MACHADO', '2024-10-05', 'Convencional', NULL, NULL, '63186', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(198, 'ADEMIR DOS SANTOS', '2024-03-06', 'Convencional', NULL, NULL, '63199', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(199, 'RONIVALDO DE LOREDO', '2024-02-07', 'Convencional', NULL, NULL, '63203', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(200, 'WESLEI HENRIQUE DA LUZ MOREIRA', '2024-02-07', 'Convencional', NULL, NULL, '63204', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(201, 'ELAINE CRISTINA BUENO FREDERICO', '2024-02-07', 'Convencional', NULL, NULL, '63205', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(202, 'MARCOS APARECIDO DE LIMA', '2024-02-07', 'Convencional', NULL, NULL, '63206', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(203, 'MARCIO MARQUES', '2024-02-07', 'Convencional', NULL, NULL, '63207', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(204, 'ANDRE ANTONIO BATISTA', '2024-02-07', 'Convencional', NULL, NULL, '63208', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(205, 'JOSE APARECIDO NASCIMENTO', '2024-04-03', 'Convencional', NULL, NULL, '63210', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(206, 'SILVANO SIQUEIRA LINO', '2024-04-07', 'Convencional', NULL, NULL, '63211', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(207, 'MARCOS ROBERTO BONIFACIO AMARANS', '2024-08-07', 'Convencional', NULL, NULL, '63215', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(208, 'REGINALDO CORREIA DE LIMA', '2024-08-07', 'Convencional', NULL, NULL, '63217', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(209, 'EDUARDO BUENO FREDERICO', '2024-12-07', 'Convencional', NULL, NULL, '63218', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(210, 'JANIO FRANCISCO DOS SANTOS', '2024-07-22', 'Convencional', NULL, NULL, '63221', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(211, 'MAIKON ANTONIO DE SOUZA', '2024-01-08', 'Convencional', NULL, NULL, '63225', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(212, 'ANDERSON JUNIOR MULLER', '2024-01-08', 'Convencional', NULL, NULL, '63226', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(213, 'MARCIO ROBERTO RIBEIRO', '2024-01-08', 'Convencional', NULL, NULL, '63228', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(214, 'JEFFERSON GONCALVES', '2024-01-08', 'Convencional', NULL, NULL, '63230', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(215, 'YULDOR GIL MANRIQUE', '2024-01-08', 'Convencional', NULL, NULL, '63231', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(216, 'MARILTON RODRIGUES', '2024-01-08', 'Convencional', NULL, NULL, '63232', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(217, 'MATHEUS LUCAS PAULINO DA SILVA CREMONEZZI', '2024-01-08', 'Convencional', NULL, NULL, '63233', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(218, 'TIAGO HENRIQUE DA SILVA', '2024-05-08', 'Convencional', NULL, NULL, '63235', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(219, 'ELTON FLAVIO DE LIMA', '2024-07-08', 'Convencional', NULL, NULL, '63239', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(220, 'LUCIO ANTONIO NUNES', '2024-07-08', 'Convencional', NULL, NULL, '63240', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(221, 'VALDINEI DA LUZ', '2023-05-12', 'Convencional', NULL, NULL, '63241', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(222, 'EVERALDO TOZZI', '2024-12-08', 'Convencional', NULL, NULL, '63243', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(223, 'ANDERSON LUIS DOS SANTOS', '2024-08-15', 'Convencional', NULL, NULL, '63246', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(224, 'FABIANA GONCALVES MARINHO DE MIRANDA', '2024-08-15', 'Convencional', NULL, NULL, '63247', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(225, 'FERNANDO DONIZETTI DE SOUZA', '2024-02-09', 'Convencional', NULL, NULL, '63250', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(226, 'FABIANO LOPES', '2024-02-09', 'Convencional', NULL, NULL, '63251', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(227, 'ROBNALDO DE OLIVEIRA ROSA', '2024-02-09', 'Convencional', NULL, NULL, '63252', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(228, 'EVERTON DE OLIVEIRA', '2024-02-09', 'Convencional', NULL, NULL, '63254', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(229, 'ELIAS JESUS FERNANDES', '2024-02-09', 'Convencional', NULL, NULL, '63255', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(230, 'WILSON RIBEIRO DE FRANCA', '2024-02-09', 'Convencional', NULL, NULL, '63257', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(231, 'ROGERIO LEAO TRINDADE', '2024-09-19', 'Convencional', NULL, NULL, '63273', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(232, 'WESLEY PEGORARI', '2024-09-19', 'Convencional', NULL, NULL, '63274', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(233, 'JEFERSON LUAN FIRMINO', '2024-09-19', 'Convencional', NULL, NULL, '63275', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(234, 'MARCIO HENRIQUE DA SILVA', '2024-09-19', 'Convencional', NULL, NULL, '63277', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(235, 'DONIZETE CONRADO GOMES', '2024-01-10', 'Convencional', NULL, NULL, '63282', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(236, 'SEBASTIAO BENEDITO MARTINS', '2024-10-23', 'Convencional', NULL, NULL, '63290', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(237, 'LEONARDO POLONI  DE SOUZA', '2024-10-23', 'Convencional', NULL, NULL, '63292', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(238, 'PAULO ROBERTO LUDOVICO', '2024-10-23', 'Convencional', NULL, NULL, '63295', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(239, 'HUGO CAIQUE ALVES DE SOUZA', '2024-04-11', 'Convencional', NULL, NULL, '63297', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(240, 'ANTONIO BARBOSA', '2024-10-23', 'Micro', NULL, NULL, '63299', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(241, 'EDNEIA AGUIAR', '2024-04-11', 'Convencional', NULL, NULL, '63300', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(242, 'MARIA DE FATIMA ZARELLI', '2024-04-11', 'Convencional', NULL, NULL, '63302', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(243, 'CHRISTIAN BUENO DO AMARAL', '2024-04-11', 'Convencional', NULL, NULL, '63304', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(244, 'JORGE LUIS FERNANDES', '2024-11-11', 'Convencional', NULL, NULL, '63312', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(245, 'FABIO AUGUSTO RAZABONI DA SILVA', '2024-11-11', 'Convencional', NULL, NULL, '63313', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(246, 'ROBERTO APARECIDO RODRIGUES', '2024-11-18', 'Convencional', NULL, NULL, '63323', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(247, 'RODRIGO VERONICA', '2024-11-21', 'Convencional', NULL, NULL, '63324', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(248, 'LUCAS HENRIQUE REIS DOS SANTOS', '2024-11-21', 'Convencional', NULL, NULL, '63328', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(249, 'PATRIC PEREIRA DA CRUZ', '2024-11-21', 'Convencional', NULL, NULL, '63330', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(250, 'DIEGO MORAES VIEIRA', '2024-12-19', 'Convencional', NULL, NULL, '63335', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(251, 'CLAUDIO APARECIDO DA SILVA', '2024-12-19', 'Convencional', NULL, NULL, '63336', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(252, 'FRANCIELLE DA SILVA MASSANEIRO ', '2024-12-19', 'Convencional', NULL, NULL, '63337', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(253, 'VINICIUS ELIAS DA SILVA', '2024-12-19', 'Convencional', NULL, NULL, '63338', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(254, 'LUIZ HENRIQUE GARCIA ', '2024-12-19', 'Convencional', NULL, NULL, '63340', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(255, 'LEANDER CARDOSO DA SILVA', '2025-01-13', 'Convencional', NULL, NULL, '63343', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(256, 'LUCAS NATAN M DE PAULA', '2025-01-13', 'Convencional', NULL, NULL, '63346', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(257, 'JOSIVALDO ALVES?PEREIRA', '2025-01-13', 'Convencional', NULL, NULL, '63347', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(258, 'ANDERSON RAFAEL ZECLAN', '2025-01-13', 'Convencional', NULL, NULL, '63350', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(259, 'ROMULO JOSE MARQUES GOMES', '2025-01-13', 'Micro', NULL, NULL, '63351', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(260, 'AURELIANO BATISTA', '2025-01-13', 'Micro', NULL, NULL, '63352', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(261, 'MARCIO JOSE SCHOLZE', '2025-01-13', 'Micro', NULL, NULL, '63353', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(262, 'RICARDO DOS SANTOS', '2025-01-16', 'Micro', NULL, NULL, '63355', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00');
INSERT INTO `motoristas` (`id`, `nome`, `data_contratacao`, `tipo_veiculo`, `email`, `telefone`, `matricula`, `senha`, `status`, `cargo`, `data_cadastro`) VALUES
(263, 'CLAYTON JUNIOR ALVES', '2027-01-13', 'Micro', NULL, NULL, '63356', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(264, 'MAURICIO FERNANDO MARTINS DA SILVA', '2025-01-20', 'Micro', NULL, NULL, '63357', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(265, 'ADEMIR APARECIDO VIEIRA', '2025-03-02', 'Micro', NULL, NULL, '63360', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(266, 'JHONATAN FLOR DA SILVA', '2025-03-02', 'Micro', NULL, NULL, '63361', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(267, 'JOSE CARLOS APARECIDO GOES ', '2025-03-02', 'Micro', NULL, NULL, '63363', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(268, 'RONALDO BATISTA', '2025-02-18', 'Micro', NULL, NULL, '63381', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(269, 'DIEGO RIBEIRO DE GODOI', '2025-02-18', 'Micro', NULL, NULL, '63382', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(270, 'RENATA APARECIDA GEROMEL', '2025-02-22', 'Micro', NULL, NULL, '63383', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(271, 'THIAGO BENHUT PIROLO', '2025-02-18', 'Micro', NULL, NULL, '63384', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(272, 'JAIR SALES PAIM', '2025-02-20', 'Micro', NULL, NULL, '63385', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(273, 'VALDECIR DA SILVA', '2025-02-24', 'Micro', NULL, NULL, '63386', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(274, 'WILSON MELQUIDES SOARES', '2025-02-22', 'Micro', NULL, NULL, '63387', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(275, 'ANDERSON LUIZ GUASSU', '2025-02-22', 'Micro', NULL, NULL, '63388', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(276, 'MAICON FERNANDO DOS SANTOS MENEZES', '2025-10-03', 'Micro', NULL, NULL, '63389', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(277, 'ANTONIO FERREIRA?DE?LIMA', '2025-10-03', 'Micro', NULL, NULL, '63390', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(278, 'JOAO PAULO GOMES DUARTE', '2025-10-03', 'Micro', NULL, NULL, '63391', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(279, 'CLAUDEMAR APARECIDO NERY', '2025-10-03', 'Micro', NULL, NULL, '63392', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(280, 'MILENE FERREIRA DE BARROS', '2025-02-22', 'Micro', NULL, NULL, '63393', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(281, 'JUNIOR CESAR PUPIM', '2025-02-22', 'Micro', NULL, NULL, '63395', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(282, 'ADRIANO GASPAR SA SILVA', '2025-02-04', 'Micro', NULL, NULL, '63396', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(283, 'ANTONIO', '2025-02-04', 'Micro', NULL, NULL, '63401', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(284, 'REGINALDO', '2025-02-04', 'Micro', NULL, NULL, '63402', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(285, 'FABIO', '2025-02-04', 'Micro', NULL, NULL, '63403', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(286, 'ALEXANDRE', '2025-02-04', 'Micro', NULL, NULL, '63404', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(287, 'REGINALDO', '2025-02-04', 'Micro', NULL, NULL, '63405', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(288, 'LUIZ', '2025-02-04', 'Micro', NULL, NULL, '63406', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00'),
(289, 'ANDRE', '2025-02-04', 'Micro', NULL, NULL, '63407', '$2y$10$I2.wxlv9EbHGMKkyNhUARu9P8w4cTyhjuQi3ik9qFxEvIpMUMcvKy', 'ativo', 'Motorista', '2025-05-18 03:00:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `motorista_escalas`
--

CREATE TABLE `motorista_escalas` (
  `id` int(11) NOT NULL,
  `motorista_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `work_id` varchar(50) NOT NULL DEFAULT '000000',
  `tabela_escalas` varchar(10) DEFAULT '00' COMMENT 'Num. Tabela de referência para esta escala/turno',
  `eh_extra` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Marca se é turno extra (1=Sim, 0=Não)',
  `veiculo_id` int(11) DEFAULT NULL,
  `linha_origem_id` varchar(50) DEFAULT NULL,
  `funcao_operacional_id` int(11) DEFAULT NULL COMMENT 'ID da função operacional, se aplicável',
  `hora_inicio_prevista` time DEFAULT NULL,
  `local_inicio_turno_id` int(11) DEFAULT NULL,
  `hora_fim_prevista` time DEFAULT NULL,
  `local_fim_turno_id` int(11) DEFAULT NULL,
  `escala_publicada` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `motorista_escalas`
--

INSERT INTO `motorista_escalas` (`id`, `motorista_id`, `data`, `work_id`, `tabela_escalas`, `eh_extra`, `veiculo_id`, `linha_origem_id`, `funcao_operacional_id`, `hora_inicio_prevista`, `local_inicio_turno_id`, `hora_fim_prevista`, `local_fim_turno_id`, `escala_publicada`) VALUES
(1, 12, '2025-12-31', '2100701', NULL, 0, 1, '213', NULL, '05:00:00', 1, '09:50:00', 2, 1),
(3, 12, '2025-12-31', '2100102', NULL, 0, 15, '210', NULL, '10:00:00', 2, '11:50:00', 2, 1),
(4, 12, '2026-01-01', '32130101', NULL, 0, 1, '213', NULL, '05:35:00', 1, '12:05:00', 2, 1),
(6, 12, '2026-01-02', '2100701', NULL, 0, 16, '213', NULL, '05:33:00', 1, '10:06:00', 1, 1),
(7, 12, '2026-01-06', '2100701', NULL, 0, 20, '210/213/250', NULL, '05:33:00', 1, '10:06:00', 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `motorista_escalas_diaria`
--

CREATE TABLE `motorista_escalas_diaria` (
  `id` int(11) NOT NULL,
  `motorista_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `work_id` varchar(50) NOT NULL,
  `tabela_escalas` varchar(10) DEFAULT NULL COMMENT 'Num. Tabela de referência para esta escala/turno',
  `eh_extra` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Marca se é turno extra (1=Sim, 0=Não)',
  `veiculo_id` int(11) DEFAULT NULL,
  `linha_origem_id` int(11) DEFAULT NULL,
  `funcao_operacional_id` int(11) DEFAULT NULL COMMENT 'ID da função operacional, se aplicável',
  `hora_inicio_prevista` time DEFAULT NULL,
  `local_inicio_turno_id` int(11) DEFAULT NULL,
  `hora_fim_prevista` time DEFAULT NULL,
  `local_fim_turno_id` int(11) DEFAULT NULL,
  `data_ultima_modificacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Quando esta entrada diária foi modificada',
  `modificado_por_admin_id` int(11) DEFAULT NULL COMMENT 'ID do admin que modificou',
  `observacoes_ajuste` text DEFAULT NULL COMMENT 'Observações sobre o ajuste diário'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Escalas diárias ajustadas para consulta interna e agentes';

-- --------------------------------------------------------

--
-- Estrutura para tabela `noticias`
--

CREATE TABLE `noticias` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `resumo` text DEFAULT NULL,
  `conteudo_completo` longtext DEFAULT NULL,
  `data_publicacao` datetime NOT NULL,
  `imagem_destaque` varchar(255) DEFAULT NULL,
  `status` enum('publicada','rascunho','arquivada') NOT NULL DEFAULT 'rascunho',
  `data_modificacao` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp() COMMENT 'Data da última modificação da notícia'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `noticias`
--

INSERT INTO `noticias` (`id`, `titulo`, `resumo`, `conteudo_completo`, `data_publicacao`, `imagem_destaque`, `status`, `data_modificacao`) VALUES
(1, 'Alteração de Horário Linha 213', 'Atenção motoristas: a linha 213 terá seu horário de pico da tarde alterado a partir da próxima segunda-feira.', 'Devido a ajustes operacionais e visando melhor atender aos passageiros nos horários de maior movimento, informamos que a tabela horária da linha 213 (Terminal Central / Shopping Catuaí) sofrerá alterações nos dias úteis a partir da próxima segunda-feira, dia 21/04/2025. Os horários de pico entre 17:00 e 19:00 serão adiantados em 5 minutos. Consulte a tabela completa na sua escala ou no site da CMTU.', '2025-04-19 10:00:00', NULL, 'publicada', NULL),
(3, 'Nova Frota Chegando', 'Estamos preparando a chegada de novos veículos para melhorar o conforto!', 'Detalhes completos sobre os novos ônibus serão divulgados em breve...', '2025-04-19 11:00:00', 'noticia_681a737bdcad75.50917991.png', 'publicada', '2025-05-06 20:46:50'),
(4, 'Novo Cenário!', 'Confira todas as mudanças do novo cenário, que irá ao ar dia 19/05/2025', 'Abaixo você confere todas as mudanças do novo cenário, que vai ao ar dia 19/05/2025:\r\n\r\nMudanças de horários nas linhas:\r\n202 - 222 - 209 - 801 - 802 - 906 - 907\r\n\r\nNovo layout dos Diário de Bordo, com mais informações para lhe ajudar durante seu trabalho, seguindo o padrão aqui do Portal;\r\nPortal do Motorista já com a adequação para o novo cenário (tabelas, WorkIDs, rotas/traçados, pontos).\r\nLançamento do link \"Procedimentos\", no menu acima (ou nos 3 \"tracinhos\" pelo aplicativo), que lista todos os procedimentos que o motorista deve fazer para cada situação (TDM, Validador, Plataforma de Cadeirante, Uso do Crachá, Etc);\r\nCorreções de bug do sistema, que vocês nos apontaram (obrigado!);\r\nE muito mais...', '2025-05-06 23:21:00', NULL, 'publicada', '2025-05-06 21:21:23');

-- --------------------------------------------------------

--
-- Estrutura para tabela `veiculos`
--

CREATE TABLE `veiculos` (
  `id` int(11) NOT NULL,
  `prefixo` varchar(20) NOT NULL,
  `tipo` enum('Convencional Amarelo','Convencional Amarelo com Ar','Micro','Micro com Ar','Convencional Azul','Convencional Azul com Ar','Padron Azul','SuperBus','Leve') DEFAULT NULL COMMENT 'Tipo do veículo',
  `status` enum('operação','fora de operação') NOT NULL DEFAULT 'operação' COMMENT 'Status operacional do veículo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `veiculos`
--

INSERT INTO `veiculos` (`id`, `prefixo`, `tipo`, `status`) VALUES
(1, '5057', 'Convencional Amarelo', 'operação'),
(3, '5058', 'Convencional Amarelo', 'operação'),
(4, '5059', 'Micro com Ar', 'operação'),
(5, '5060', 'Micro com Ar', 'operação'),
(6, '5061', 'Convencional Amarelo', 'operação'),
(7, '5062', 'Convencional Amarelo', 'operação'),
(8, '5063', 'Convencional Amarelo', 'operação'),
(9, '5064', 'Convencional Amarelo', 'operação'),
(10, '5065', 'Convencional Amarelo', 'operação'),
(11, '5100', 'Convencional Amarelo', 'operação'),
(12, '5101', 'Convencional Amarelo', 'operação'),
(13, '5102', 'Convencional Amarelo', 'operação'),
(14, '5103', 'Convencional Amarelo', 'operação'),
(15, '5104', 'Convencional Amarelo', 'operação'),
(16, '5105', 'Convencional Amarelo', 'operação'),
(17, '5106', 'Convencional Amarelo', 'operação'),
(18, '5107', 'Convencional Amarelo', 'operação'),
(19, '5108', 'Convencional Amarelo', 'operação'),
(20, '5109', 'Convencional Amarelo', 'operação'),
(21, '5110', 'Convencional Amarelo', 'operação'),
(22, '5111', 'Convencional Amarelo', 'operação'),
(23, '5112', 'Convencional Amarelo', 'operação'),
(24, '5113', 'Convencional Amarelo', 'operação'),
(25, '5114', 'Convencional Amarelo', 'operação'),
(26, '5115', 'Convencional Amarelo', 'operação'),
(27, '5116', 'Convencional Amarelo', 'operação'),
(28, '5117', 'Convencional Amarelo', 'operação'),
(29, '5118', 'Convencional Amarelo', 'operação'),
(30, '5119', 'Convencional Amarelo', 'operação'),
(31, '5120', 'Convencional Amarelo', 'operação'),
(32, '5121', 'Convencional Amarelo', 'operação'),
(33, '5122', 'Convencional Amarelo', 'operação'),
(34, '5123', 'Convencional Amarelo', 'operação'),
(35, '5124', 'Convencional Amarelo', 'operação'),
(36, '5125', 'Convencional Amarelo', 'operação'),
(37, '5126', 'Convencional Amarelo', 'operação'),
(38, '5127', 'Convencional Amarelo', 'operação'),
(39, '5128', 'Convencional Amarelo', 'operação'),
(40, '5129', 'Leve', 'operação'),
(41, '5130', 'Leve', 'operação'),
(42, '5131', 'Leve', 'operação'),
(43, '5132', 'Leve', 'operação'),
(44, '5133', 'Leve', 'operação'),
(45, '5134', 'Leve', 'operação'),
(46, '5135', 'Leve', 'operação'),
(47, '5136', 'Micro', 'operação'),
(48, '5137', 'Micro', 'operação'),
(49, '5138', 'Micro', 'operação'),
(50, '5139', 'Micro', 'operação'),
(51, '5140', 'Micro', 'operação'),
(52, '5141', 'Micro', 'operação'),
(53, '5142', 'Micro', 'operação'),
(54, '5143', 'Micro', 'operação'),
(55, '5144', 'Micro', 'operação'),
(56, '5145', 'Micro', 'operação'),
(57, '5146', 'Micro', 'operação'),
(58, '5147', 'Micro', 'operação'),
(59, '5149', 'Micro', 'operação'),
(60, '5150', 'Convencional Amarelo', 'operação'),
(61, '5151', 'Micro', 'operação'),
(62, '5152', 'Convencional Amarelo', 'operação'),
(63, '5153', 'Convencional Amarelo', 'operação'),
(64, '5154', 'Convencional Amarelo', 'operação'),
(65, '5155', 'Convencional Amarelo', 'operação'),
(66, '5156', 'Convencional Amarelo', 'operação'),
(67, '5157', 'Convencional Amarelo', 'operação'),
(68, '5158', 'Convencional Amarelo', 'operação'),
(69, '5159', 'Convencional Amarelo', 'operação'),
(70, '5160', 'Convencional Amarelo', 'operação'),
(71, '5161', 'Convencional Amarelo', 'operação'),
(72, '5162', 'Convencional Amarelo', 'operação'),
(73, '5163', 'Convencional Amarelo', 'operação'),
(74, '5164', 'Convencional Amarelo', 'operação'),
(75, '5165', 'Convencional Amarelo', 'operação'),
(76, '5166', 'Convencional Amarelo', 'operação'),
(77, '5167', 'Convencional Amarelo', 'operação'),
(78, '5168', 'Convencional Amarelo', 'operação'),
(79, '5169', 'Convencional Amarelo', 'operação'),
(80, '5170', 'Convencional Amarelo', 'operação'),
(81, '5171', 'Convencional Amarelo', 'operação'),
(82, '5172', 'Convencional Amarelo', 'operação'),
(83, '5173', 'Convencional Amarelo', 'operação'),
(84, '5174', 'Convencional Amarelo', 'operação'),
(85, '5175', 'Convencional Amarelo', 'operação'),
(86, '5176', 'Convencional Amarelo', 'operação'),
(87, '5177', 'Convencional Amarelo', 'operação'),
(88, '5178', 'Convencional Amarelo', 'operação'),
(89, '5179', 'Micro', 'operação'),
(90, '5180', 'Micro', 'operação'),
(91, '5300', 'Convencional Amarelo com Ar', 'operação'),
(92, '5301', 'Convencional Amarelo com Ar', 'operação'),
(93, '5302', 'Convencional Amarelo com Ar', 'operação'),
(94, '5303', 'Convencional Amarelo com Ar', 'operação'),
(95, '5304', 'Convencional Amarelo com Ar', 'operação'),
(96, '5305', 'Convencional Amarelo com Ar', 'operação'),
(97, '5306', 'Convencional Amarelo com Ar', 'operação'),
(98, '5307', 'Convencional Amarelo com Ar', 'operação'),
(99, '5308', 'Convencional Amarelo com Ar', 'operação'),
(100, '5309', 'Convencional Amarelo com Ar', 'operação'),
(101, '5310', 'Convencional Amarelo com Ar', 'operação'),
(102, '5311', 'Convencional Amarelo com Ar', 'operação'),
(103, '5312', 'Convencional Amarelo com Ar', 'operação'),
(104, '5313', 'Convencional Amarelo com Ar', 'operação'),
(105, '5314', 'Convencional Amarelo com Ar', 'operação'),
(106, '5315', 'Convencional Amarelo com Ar', 'operação'),
(107, '5316', 'Convencional Amarelo com Ar', 'operação'),
(108, '7012', 'Padron Azul', 'operação'),
(109, '7013', 'Padron Azul', 'operação'),
(110, '7014', 'Padron Azul', 'operação'),
(111, '7015', 'Padron Azul', 'operação'),
(112, '7016', 'Padron Azul', 'operação'),
(113, '7017', 'Padron Azul', 'operação'),
(114, '7023', 'Convencional Azul', 'operação'),
(115, '7024', 'Convencional Azul', 'operação'),
(116, '7025', 'Convencional Azul', 'operação'),
(117, '7026', 'Convencional Azul', 'operação'),
(118, '7027', 'Convencional Azul', 'operação'),
(119, '7028', 'Convencional Azul', 'operação'),
(120, '7029', 'Convencional Azul com Ar', 'operação'),
(121, '7030', 'Convencional Azul com Ar', 'operação'),
(122, '7031', 'Convencional Azul com Ar', 'operação'),
(123, '7032', 'Convencional Azul com Ar', 'operação'),
(124, '7033', 'Convencional Azul com Ar', 'operação'),
(125, '7034', 'Convencional Azul com Ar', 'operação'),
(126, '7035', 'Convencional Azul com Ar', 'operação'),
(127, '7036', 'Convencional Azul com Ar', 'operação'),
(128, '7037', 'Convencional Azul com Ar', 'operação'),
(129, '8000', 'SuperBus', 'operação'),
(130, '8001', 'SuperBus', 'operação'),
(131, '8002', 'SuperBus', 'operação'),
(132, '8003', 'SuperBus', 'operação'),
(133, '8004', 'SuperBus', 'operação'),
(134, '8006', 'SuperBus', 'operação');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `funcoes_operacionais`
--
ALTER TABLE `funcoes_operacionais`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome_funcao` (`nome_funcao`),
  ADD UNIQUE KEY `work_id_prefixo` (`work_id_prefixo`),
  ADD KEY `fk_funcoes_local_fixo_restrict` (`local_fixo_id`);

--
-- Índices de tabela `locais`
--
ALTER TABLE `locais`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`),
  ADD KEY `idx_locais_nome` (`nome`);

--
-- Índices de tabela `mensagens_motorista`
--
ALTER TABLE `mensagens_motorista`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_msg_motorista_leitura` (`motorista_id`,`data_leitura`);

--
-- Índices de tabela `motoristas`
--
ALTER TABLE `motoristas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricula` (`matricula`);

--
-- Índices de tabela `motorista_escalas`
--
ALTER TABLE `motorista_escalas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `veiculo_id` (`veiculo_id`),
  ADD KEY `local_inicio_turno_id` (`local_inicio_turno_id`),
  ADD KEY `local_fim_turno_id` (`local_fim_turno_id`),
  ADD KEY `idx_motorista_data` (`motorista_id`,`data`),
  ADD KEY `idx_tabela_escala` (`tabela_escalas`),
  ADD KEY `idx_funcao_operacional` (`funcao_operacional_id`),
  ADD KEY `idx_data_publicada` (`data`,`escala_publicada`),
  ADD KEY `idx_linha_numero` (`linha_origem_id`);

--
-- Índices de tabela `motorista_escalas_diaria`
--
ALTER TABLE `motorista_escalas_diaria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diaria_motorista_data` (`motorista_id`,`data`),
  ADD KEY `idx_diaria_tabela_escala` (`tabela_escalas`),
  ADD KEY `veiculo_id_diaria` (`veiculo_id`),
  ADD KEY `linha_origem_id_diaria` (`linha_origem_id`),
  ADD KEY `local_inicio_turno_id_diaria` (`local_inicio_turno_id`),
  ADD KEY `local_fim_turno_id_diaria` (`local_fim_turno_id`),
  ADD KEY `modificado_por_admin_id_diaria` (`modificado_por_admin_id`),
  ADD KEY `idx_diaria_funcao_operacional` (`funcao_operacional_id`);

--
-- Índices de tabela `noticias`
--
ALTER TABLE `noticias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_data_publicacao` (`data_publicacao`);

--
-- Índices de tabela `veiculos`
--
ALTER TABLE `veiculos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `prefixo` (`prefixo`),
  ADD KEY `idx_veiculos_prefixo` (`prefixo`),
  ADD KEY `idx_veiculos_status` (`status`),
  ADD KEY `idx_veiculos_tipo` (`tipo`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `administradores`
--
ALTER TABLE `administradores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `funcoes_operacionais`
--
ALTER TABLE `funcoes_operacionais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `locais`
--
ALTER TABLE `locais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `mensagens_motorista`
--
ALTER TABLE `mensagens_motorista`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `motoristas`
--
ALTER TABLE `motoristas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=812;

--
-- AUTO_INCREMENT de tabela `motorista_escalas`
--
ALTER TABLE `motorista_escalas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `motorista_escalas_diaria`
--
ALTER TABLE `motorista_escalas_diaria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `noticias`
--
ALTER TABLE `noticias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `veiculos`
--
ALTER TABLE `veiculos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=135;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `funcoes_operacionais`
--
ALTER TABLE `funcoes_operacionais`
  ADD CONSTRAINT `fk_funcoes_local_fixo_restrict` FOREIGN KEY (`local_fixo_id`) REFERENCES `locais` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `mensagens_motorista`
--
ALTER TABLE `mensagens_motorista`
  ADD CONSTRAINT `mensagens_motorista_ibfk_1` FOREIGN KEY (`motorista_id`) REFERENCES `motoristas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `motorista_escalas`
--
ALTER TABLE `motorista_escalas`
  ADD CONSTRAINT `fk_escala_funcao_operacional` FOREIGN KEY (`funcao_operacional_id`) REFERENCES `funcoes_operacionais` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `motorista_escalas_ibfk_1` FOREIGN KEY (`motorista_id`) REFERENCES `motoristas` (`id`),
  ADD CONSTRAINT `motorista_escalas_ibfk_2` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `motorista_escalas_ibfk_4` FOREIGN KEY (`local_inicio_turno_id`) REFERENCES `locais` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `motorista_escalas_ibfk_5` FOREIGN KEY (`local_fim_turno_id`) REFERENCES `locais` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `motorista_escalas_diaria`
--
ALTER TABLE `motorista_escalas_diaria`
  ADD CONSTRAINT `fk_diaria_admin_mod` FOREIGN KEY (`modificado_por_admin_id`) REFERENCES `administradores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_diaria_linha` FOREIGN KEY (`linha_origem_id`) REFERENCES `linhas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_diaria_local_fim` FOREIGN KEY (`local_fim_turno_id`) REFERENCES `locais` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_diaria_local_ini` FOREIGN KEY (`local_inicio_turno_id`) REFERENCES `locais` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_diaria_motorista` FOREIGN KEY (`motorista_id`) REFERENCES `motoristas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_diaria_veiculo` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_escala_diaria_funcao_operacional` FOREIGN KEY (`funcao_operacional_id`) REFERENCES `funcoes_operacionais` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
