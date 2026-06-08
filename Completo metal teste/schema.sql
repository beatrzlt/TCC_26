
CREATE DATABASE IF NOT EXISTS metal_financeiro
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE metal_financeiro;

-- ================================================================
--  TABELA: usuarios
-- ================================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    nome                VARCHAR(80)     NOT NULL,
    sobrenome           VARCHAR(80)     NOT NULL DEFAULT '',
    email               VARCHAR(180)    NOT NULL UNIQUE,
    senha_hash          VARCHAR(255)    NOT NULL,
    perfil              ENUM('admin','usuario') NOT NULL DEFAULT 'usuario',
    telefone            VARCHAR(20)     NOT NULL DEFAULT '',
    data_nascimento     DATE            NULL,
    genero              ENUM('Masculino','Feminino','Outro','') NOT NULL DEFAULT '',
    cidade              VARCHAR(80)     NOT NULL DEFAULT '',
    estado              VARCHAR(80)     NOT NULL DEFAULT '',
    bio                 TEXT            NOT NULL DEFAULT '',
    avatar              VARCHAR(120)    NOT NULL DEFAULT '',
    tema                ENUM('dark','light') NOT NULL DEFAULT 'dark',
    fonte               ENUM('pequeno','medio','grande') NOT NULL DEFAULT 'medio',
    cfg_alerta_metas    TINYINT(1)      NOT NULL DEFAULT 1,
    cfg_resumo_semanal  TINYINT(1)      NOT NULL DEFAULT 1,
    cfg_alerta_gastos   TINYINT(1)      NOT NULL DEFAULT 0,
    cfg_email_notif     TINYINT(1)      NOT NULL DEFAULT 1,
    cfg_2fa             TINYINT(1)      NOT NULL DEFAULT 0,
    cfg_ocultar_saldo   TINYINT(1)      NOT NULL DEFAULT 0,
    cfg_sessao          VARCHAR(10)     NOT NULL DEFAULT '30min',
    cfg_sync_auto       TINYINT(1)      NOT NULL DEFAULT 1,
    cfg_animacoes       TINYINT(1)      NOT NULL DEFAULT 1,
    criado_em           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso       DATETIME        NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
--  TABELA: metas
-- ================================================================
CREATE TABLE IF NOT EXISTS metas (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED    NOT NULL,
    nome        VARCHAR(120)    NOT NULL,
    categoria   VARCHAR(30)     NOT NULL DEFAULT 'outro',
    valor_total DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    valor_atual DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
    prazo       VARCHAR(7)      NOT NULL,
    status      ENUM('andamento','concluida','atrasada') NOT NULL DEFAULT 'andamento',
    criado_em   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_metas_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Índices ───────────────────────────────────────────────────────
CREATE INDEX IF NOT EXISTS idx_metas_usuario ON metas (usuario_id);

-- ================================================================
--  SEED: Usuários
--
--  Credenciais de acesso:
--
--  ┌─────────────┬──────────────────────────┬────────────────┐
--  │  Perfil     │  E-mail                  │  Senha         │
--  ├─────────────┼──────────────────────────┼────────────────┤
--  │  admin      │  admin@metalfinanceiro.com│  Admin@2024    │
--  │  usuario    │  bernardo@email.com       │  Bernardo@2024 │
--  └─────────────┴──────────────────────────┴────────────────┘
--
--  As senhas estão com bcrypt custo 12 ($2y$12$...).
--  Compatíveis com PHP password_verify().
-- ================================================================

INSERT INTO usuarios
    (nome, sobrenome, email, senha_hash, perfil,
     telefone, data_nascimento, genero, cidade, estado,
     bio, tema, fonte,
     cfg_alerta_metas, cfg_resumo_semanal, cfg_alerta_gastos,
     cfg_email_notif, cfg_2fa, cfg_ocultar_saldo,
     cfg_sessao, cfg_sync_auto, cfg_animacoes,
     criado_em)
VALUES

-- ── 1. Administrador ─────────────────────────────────────────────
(
    'Admin',
    'Sistema',
    'admin@metalfinanceiro.com',
    '$2y$12$PAA8LORmBuvss0tvxseM6eADShu.Eq3.7I7eNtZZGI6faDzGtXYFu',   -- Admin@2024
    'admin',
    '(11) 99999-0000',
    '1990-01-01',
    'Outro',
    'São Paulo',
    'SP',
    'Conta administrativa do sistema METAL Financeiro.',
    'dark',
    'medio',
    1, 1, 1,   -- alerta_metas, resumo_semanal, alerta_gastos
    1, 0, 0,   -- email_notif, 2fa, ocultar_saldo
    '30min', 1, 1,
    NOW()
),

-- ── 2. Usuário: Bernardo ──────────────────────────────────────────
(
    'Bernardo',
    'Pires',
    'bernardo@email.com',
    '$2y$12$QQaLhRdTUfmyQrwmZINFy.BX04pkA5QxHv2QtrmgNTndIUjlEXIqS',   -- Bernardo@2024
    'usuario',
    '(11) 98888-1234',
    '2000-05-15',
    'Masculino',
    'São Paulo',
    'SP',
    'Estudante de tecnologia e entusiasta de finanças pessoais.',
    'dark',
    'medio',
    1, 1, 0,
    1, 0, 0,
    '30min', 1, 1,
    NOW()
);

-- ================================================================
--  SEED: Metas do Bernardo (usuario_id = 2)
-- ================================================================

INSERT INTO metas
    (usuario_id, nome, categoria, valor_total, valor_atual, prazo, status, criado_em)
VALUES

(2, 'Viagem para Europa',      'viagem',        15000.00,  8500.00, '2025-12', 'andamento', NOW()),
(2, 'Fundo de emergência',     'emergencia',    10000.00, 10000.00, '2024-06', 'concluida', NOW()),
(2, 'Entrada do apartamento',  'casa',          60000.00, 22000.00, '2026-12', 'andamento', NOW()),
(2, 'Curso de MBA',            'educacao',       8000.00,  1200.00, '2024-03', 'atrasada',  NOW()),
(2, 'Reserva para o carro',    'carro',         25000.00,  9800.00, '2025-06', 'andamento', NOW());

-- ================================================================
--  SEED: Metas do Admin (usuario_id = 1)
-- ================================================================

INSERT INTO metas
    (usuario_id, nome, categoria, valor_total, valor_atual, prazo, status, criado_em)
VALUES

(1, 'Aposentadoria antecipada', 'aposentadoria', 500000.00, 128000.00, '2035-01', 'andamento', NOW()),
(1, 'Reserva de liquidez',      'emergencia',     30000.00,  30000.00, '2024-12', 'concluida', NOW());

-- ================================================================
--  Verificação final (opcional – comente se não quiser)
-- ================================================================
-- SELECT id, nome, sobrenome, email, perfil, criado_em FROM usuarios;
-- SELECT id, usuario_id, nome, status FROM metas;
