-- RELPJAM - Migração do painel de vendedores
-- Execute uma vez no banco já existente.
ALTER TABLE categorias
  ADD COLUMN vendedor_id BIGINT(20) UNSIGNED NULL AFTER imagem,
  ADD KEY fk_categoria_vendedor (vendedor_id);

ALTER TABLE categorias
  ADD CONSTRAINT fk_categoria_vendedor
  FOREIGN KEY (vendedor_id) REFERENCES vendedores(id)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- Compatibilidade com o login atual do projeto
ALTER TABLE usuarios
  ADD COLUMN login_tipo VARCHAR(30) DEFAULT 'email' AFTER two_factor_enabled;
