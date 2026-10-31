/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.4.13-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: tdokei
-- ------------------------------------------------------
-- Server version	11.4.13-MariaDB-ubu2404

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `escolas`
--

DROP TABLE IF EXISTS `escolas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `escolas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `historico_status`
--

DROP TABLE IF EXISTS `historico_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `historico_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_solicitacao` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `status_anterior` varchar(30) DEFAULT NULL,
  `status_novo` varchar(30) NOT NULL,
  `observacao` varchar(400) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_hist_usuario` (`id_usuario`),
  KEY `idx_historico_solicitacao` (`id_solicitacao`),
  CONSTRAINT `fk_hist_solicitacao` FOREIGN KEY (`id_solicitacao`) REFERENCES `solicitacoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hist_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log_auditoria`
--

DROP TABLE IF EXISTS `log_auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `log_auditoria` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int(10) unsigned NOT NULL,
  `perfil` varchar(20) NOT NULL,
  `acao` varchar(50) NOT NULL,
  `entidade` varchar(30) NOT NULL,
  `id_entidade` int(10) unsigned DEFAULT NULL,
  `descricao` varchar(500) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`id_usuario`),
  KEY `idx_acao` (`acao`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log_qrcode`
--

DROP TABLE IF EXISTS `log_qrcode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `log_qrcode` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_solicitacao` int(11) NOT NULL,
  `id_diretor` int(11) NOT NULL,
  `token_usado` varchar(64) NOT NULL,
  `resultado` enum('sucesso','retornado','item_em_falta','erro_token','erro_destino') NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `observacao` varchar(400) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_log_diretor` (`id_diretor`),
  KEY `idx_log_qr_solicitacao` (`id_solicitacao`),
  CONSTRAINT `fk_log_diretor` FOREIGN KEY (`id_diretor`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_log_solicitacao` FOREIGN KEY (`id_solicitacao`) REFERENCES `solicitacoes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notificacoes`
--

DROP TABLE IF EXISTS `notificacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `mensagem` varchar(400) NOT NULL,
  `lida` tinyint(1) NOT NULL DEFAULT 0,
  `id_ref` int(11) DEFAULT NULL COMMENT 'ID da entidade relacionada',
  `ref_tipo` varchar(30) DEFAULT NULL COMMENT 'Ex: solicitacao, oportunidade',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notificacoes_usuario` (`id_usuario`,`lida`),
  CONSTRAINT `fk_notif_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `oportunidade_escolas`
--

DROP TABLE IF EXISTS `oportunidade_escolas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oportunidade_escolas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_oportunidade` int(11) NOT NULL,
  `id_escola` int(11) NOT NULL,
  `decisao` enum('pendente','aceita','rejeitada') NOT NULL DEFAULT 'pendente',
  `id_solicitacao` int(11) DEFAULT NULL COMMENT 'Gerada ao aceitar',
  `decidido_em` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_oport_escola` (`id_oportunidade`,`id_escola`),
  KEY `fk_oesc_escola` (`id_escola`),
  KEY `fk_oesc_solic` (`id_solicitacao`),
  CONSTRAINT `fk_oesc_escola` FOREIGN KEY (`id_escola`) REFERENCES `escolas` (`id`),
  CONSTRAINT `fk_oesc_oport` FOREIGN KEY (`id_oportunidade`) REFERENCES `oportunidades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oesc_solic` FOREIGN KEY (`id_solicitacao`) REFERENCES `solicitacoes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `oportunidade_itens`
--

DROP TABLE IF EXISTS `oportunidade_itens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oportunidade_itens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_oportunidade` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `id_unidade` int(11) NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_oport_item_oport` (`id_oportunidade`),
  KEY `fk_oport_item_produto` (`id_produto`),
  KEY `fk_oport_item_unidade` (`id_unidade`),
  CONSTRAINT `fk_oport_item_oport` FOREIGN KEY (`id_oportunidade`) REFERENCES `oportunidades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oport_item_produto` FOREIGN KEY (`id_produto`) REFERENCES `produtos` (`id`),
  CONSTRAINT `fk_oport_item_unidade` FOREIGN KEY (`id_unidade`) REFERENCES `unidades_medida` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `oportunidades`
--

DROP TABLE IF EXISTS `oportunidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oportunidades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_admin` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descricao` varchar(400) NOT NULL,
  `pedir_quantidade` tinyint(1) NOT NULL DEFAULT 0,
  `id_unidade_quantidade` int(11) DEFAULT NULL,
  `categoria` enum('produto','servico') NOT NULL,
  `status` enum('ativa','desabilitada','cancelada') NOT NULL DEFAULT 'ativa',
  `editavel` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Falso após primeira decisão',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_oport_admin` (`id_admin`),
  CONSTRAINT `fk_oport_admin` FOREIGN KEY (`id_admin`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `produtos`
--

DROP TABLE IF EXISTS `produtos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `categoria` enum('papelaria','higiene','limpeza','outros') NOT NULL DEFAULT 'outros',
  `descricao` varchar(400) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessoes`
--

DROP TABLE IF EXISTS `sessoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` int(11) NOT NULL,
  `session_token` varchar(128) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `ultimo_acesso` datetime NOT NULL DEFAULT current_timestamp(),
  `expira_em` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `fk_sessao_usuario` (`id_usuario`),
  CONSTRAINT `fk_sessao_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `solicitacao_comentarios`
--

DROP TABLE IF EXISTS `solicitacao_comentarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitacao_comentarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_solicitacao` int(10) unsigned NOT NULL,
  `id_usuario` int(10) unsigned NOT NULL,
  `perfil` varchar(20) NOT NULL,
  `mensagem` varchar(1000) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_solicitacao` (`id_solicitacao`),
  KEY `idx_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `solicitacao_itens`
--

DROP TABLE IF EXISTS `solicitacao_itens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitacao_itens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_solicitacao` int(11) NOT NULL,
  `id_produto` int(11) NOT NULL,
  `id_unidade` int(11) NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_item_solicitacao` (`id_solicitacao`),
  KEY `fk_item_produto` (`id_produto`),
  KEY `fk_item_unidade` (`id_unidade`),
  CONSTRAINT `fk_item_produto` FOREIGN KEY (`id_produto`) REFERENCES `produtos` (`id`),
  CONSTRAINT `fk_item_solicitacao` FOREIGN KEY (`id_solicitacao`) REFERENCES `solicitacoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_unidade` FOREIGN KEY (`id_unidade`) REFERENCES `unidades_medida` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `solicitacao_itens_entregues`
--

DROP TABLE IF EXISTS `solicitacao_itens_entregues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitacao_itens_entregues` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_solicitacao` int(10) unsigned NOT NULL,
  `id_item` int(10) unsigned NOT NULL,
  `id_log_qrcode` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_solicitacao` (`id_solicitacao`),
  KEY `idx_item` (`id_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `solicitacoes`
--

DROP TABLE IF EXISTS `solicitacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_escola` int(11) NOT NULL,
  `id_diretor` int(11) NOT NULL,
  `id_admin` int(11) DEFAULT NULL COMMENT 'Admin responsável',
  `categoria` enum('produto','servico') NOT NULL,
  `status` enum('pendente','em_analise','em_processamento','despachado','retornado','item_em_falta','concluido','cancelado','rejeitado') NOT NULL DEFAULT 'pendente',
  `descricao_servico` varchar(300) DEFAULT NULL COMMENT 'Apenas para serviços',
  `foto_servico` varchar(255) DEFAULT NULL,
  `qr_token` varchar(64) DEFAULT NULL,
  `qr_invalidado` tinyint(1) NOT NULL DEFAULT 0,
  `nome_recebedor` varchar(150) DEFAULT NULL COMMENT 'Preenchido na conclusão via QR',
  `obs_item_falta` varchar(400) DEFAULT NULL COMMENT 'Preenchido quando há item faltando',
  `cancelado_por` int(11) DEFAULT NULL,
  `motivo_rejeicao` varchar(300) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_token` (`qr_token`),
  KEY `fk_solic_diretor` (`id_diretor`),
  KEY `fk_solic_admin` (`id_admin`),
  KEY `fk_solic_cancelado_por` (`cancelado_por`),
  KEY `idx_solicitacoes_status` (`status`),
  KEY `idx_solicitacoes_escola` (`id_escola`),
  KEY `idx_solicitacoes_categoria` (`categoria`),
  CONSTRAINT `fk_solic_admin` FOREIGN KEY (`id_admin`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_solic_cancelado_por` FOREIGN KEY (`cancelado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_solic_diretor` FOREIGN KEY (`id_diretor`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_solic_escola` FOREIGN KEY (`id_escola`) REFERENCES `escolas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sugestoes_produto`
--

DROP TABLE IF EXISTS `sugestoes_produto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sugestoes_produto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_escola` int(11) NOT NULL,
  `id_diretor` int(11) NOT NULL,
  `descricao` varchar(400) NOT NULL,
  `link` varchar(500) DEFAULT NULL,
  `status` enum('pendente','analisada','rejeitada') NOT NULL DEFAULT 'pendente',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_sugestao_escola` (`id_escola`),
  KEY `fk_sugestao_diretor` (`id_diretor`),
  CONSTRAINT `fk_sugestao_diretor` FOREIGN KEY (`id_diretor`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_sugestao_escola` FOREIGN KEY (`id_escola`) REFERENCES `escolas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `unidades_medida`
--

DROP TABLE IF EXISTS `unidades_medida`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `unidades_medida` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `sigla` varchar(10) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sigla` (`sigla`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `username` varchar(60) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `perfil` enum('superadmin','administrador','diretor','secretario','escriturario','estagiario') NOT NULL,
  `categoria` enum('produto','servico') DEFAULT NULL COMMENT 'Obrigatório para administrador',
  `id_escola` int(11) DEFAULT NULL COMMENT 'Obrigatório para diretor',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `tentativas_login` int(11) NOT NULL DEFAULT 0,
  `bloqueado` tinyint(1) NOT NULL DEFAULT 0,
  `trocar_senha` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Forca troca de senha no proximo login (superadmin do seed)',
  `ultimo_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_usuario_escola` (`id_escola`),
  CONSTRAINT `fk_usuario_escola` FOREIGN KEY (`id_escola`) REFERENCES `escolas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-10-30 19:51:08
