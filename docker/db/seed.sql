-- Dados de exemplo (FICTICIOS) para a primeira instalacao do TdOkei.
-- Senha provisoria de TODOS os usuarios abaixo: Trocar@123
-- TROQUE AS SENHAS no primeiro acesso e apague os usuarios de exemplo em producao.
SET NAMES utf8mb4;

INSERT INTO escolas (id, nome, codigo, endereco, telefone, ativo) VALUES
  (1, 'Escola Exemplo', 'EX001', 'Rua das Flores, 100', '(00) 0000-0000', 1);

INSERT INTO usuarios (nome, username, email, senha_hash, perfil, categoria, id_escola, ativo) VALUES
  ('Super Administrador', 'admin',       'admin@exemplo.local',       '$2y$10$WEIcRUcPKd9/u4MGhyhroet1bT1tDub29e1ZhXdvL2AZU97rvf.qu', 'superadmin',    NULL,      NULL, 1),
  ('Admin de Produtos',   'adm.produtos', 'produtos@exemplo.local',   '$2y$10$WEIcRUcPKd9/u4MGhyhroet1bT1tDub29e1ZhXdvL2AZU97rvf.qu', 'administrador', 'produto', NULL, 1),
  ('Admin de Servicos',   'adm.servicos', 'servicos@exemplo.local',   '$2y$10$WEIcRUcPKd9/u4MGhyhroet1bT1tDub29e1ZhXdvL2AZU97rvf.qu', 'administrador', 'servico', NULL, 1),
  ('Secretario Exemplo',  'secretario',   'secretario@exemplo.local', '$2y$10$WEIcRUcPKd9/u4MGhyhroet1bT1tDub29e1ZhXdvL2AZU97rvf.qu', 'secretario',    NULL,      NULL, 1),
  ('Diretor Exemplo',     'diretor',      'diretor@exemplo.local',    '$2y$10$WEIcRUcPKd9/u4MGhyhroet1bT1tDub29e1ZhXdvL2AZU97rvf.qu', 'diretor',       NULL,      1,    1),
  ('Escriturario Exemplo','escriturario', 'escriturario@exemplo.local','$2y$10$WEIcRUcPKd9/u4MGhyhroet1bT1tDub29e1ZhXdvL2AZU97rvf.qu','escriturario',  NULL,      1,    1),
  ('Estagiario Exemplo',  'estagiario',   'estagiario@exemplo.local', '$2y$10$WEIcRUcPKd9/u4MGhyhroet1bT1tDub29e1ZhXdvL2AZU97rvf.qu', 'estagiario',    NULL,      1,    1);

INSERT INTO unidades_medida (nome, sigla, ativo) VALUES
  ('Caixa', 'cx', 1),
  ('Fardo', 'fd', 1),
  ('Folha', 'fl', 1),
  ('Grama', 'g', 1),
  ('Litro', 'l', 1),
  ('Maço', 'mç', 1),
  ('Metro', 'm', 1),
  ('Mililitro', 'ml', 1),
  ('Pacote', 'pct', 1),
  ('Pares', 'par', 1),
  ('Quilograma', 'kg', 1),
  ('Resma', 'rsm', 1),
  ('Unidade', 'un', 1);

INSERT INTO produtos (nome, categoria, descricao, ativo) VALUES
  ('Papel Sulfite A4',  'papelaria', 'Resma com 500 folhas', 1),
  ('Marcador de lousa', 'papelaria', NULL, 1),
  ('Papel Higienico',   'higiene',   NULL, 1),
  ('Sabonete Liquido',  'higiene',   NULL, 1),
  ('Detergente',        'limpeza',   NULL, 1),
  ('Desinfetante',      'limpeza',   NULL, 1);
