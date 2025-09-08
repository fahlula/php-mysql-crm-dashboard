-- Sistema de Gerenciamento de Contatos - Schema do Banco de Dados

-- Configurar charset para suportar acentos (UTF-8)
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    profile_image LONGTEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de contatos
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    company VARCHAR(255),
    notes TEXT,
    image LONGTEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de eventos do calendário
CREATE TABLE IF NOT EXISTS calendar_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME,
    contact_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de tarefas
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pendente', 'em_andamento', 'concluida') DEFAULT 'pendente',
    priority ENUM('baixa', 'media', 'alta') DEFAULT 'media',
    due_date DATE,
    contact_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Inserir usuário padrão
INSERT INTO users (name, email, password_hash) VALUES 
('Fabiana Almeida Lula', 'fabiana.almeida1@gmail.com', '202cb962ac59075b964b07152d234b70')
ON DUPLICATE KEY UPDATE email = email;

-- Inserir dados de exemplo para contatos
INSERT INTO contacts (user_id, name, email, phone, company, address, notes) VALUES 
(1, 'João Santos', 'joao.santos@empresa.com', '(11) 99999-1234', 'TechCorp Brasil', 'Av. Paulista, 1000 - São Paulo, SP', 'Cliente importante - sempre pontual nas reuniões'),
(1, 'Ana Costa', 'ana.costa@consultoria.com', '(21) 98888-5678', 'Consultoria Estratégica', 'Rua das Flores, 200 - Rio de Janeiro, RJ', 'Especialista em marketing digital'),
(1, 'Carlos Oliveira', 'carlos@startup.tech', '(11) 97777-9999', 'StartupTech', 'Rua da Inovação, 50 - São Paulo, SP', 'CEO de startup promissora'),
(1, 'Fernanda Lima', 'fernanda.lima@advocacia.com', '(85) 96666-3333', 'Lima & Associados', 'Av. Beira Mar, 300 - Fortaleza, CE', 'Advogada corporativa - contratos'),
(1, 'Roberto Mendes', 'roberto@design.com', '(31) 95555-7777', 'Design Criativo', 'Rua do Design, 150 - Belo Horizonte, MG', 'Designer gráfico freelancer'),
(1, 'Juliana Pereira', 'juliana@vendas.com', '(47) 94444-2222', 'Vendas & Cia', 'Av. Central, 400 - Florianópolis, SC', 'Gerente de vendas regional'),
(1, 'Pedro Almeida', 'pedro@contabilidade.com', '(61) 93333-8888', 'Contábil Brasília', 'SQN 200, Bloco A - Brasília, DF', 'Contador responsável')
ON DUPLICATE KEY UPDATE name = name;

-- Inserir tarefas de exemplo
INSERT INTO tasks (user_id, title, description, status, priority, due_date, contact_id) VALUES
(1, 'Finalizar proposta comercial', 'Preparar documentação para reunião com João Santos na próxima semana', 'em_andamento', 'alta', '2025-09-10', 1),
(1, 'Revisar estratégia digital', 'Analisar resultados da campanha com Ana Costa', 'pendente', 'alta', '2025-09-08', 2),
(1, 'Apresentação StartupTech', 'Criar slides para pitch com Carlos Oliveira', 'pendente', 'media', '2025-09-12', 3),
(1, 'Validação jurídica', 'Revisar contratos enviados pela Fernanda Lima', 'em_andamento', 'media', '2025-09-09', 4),
(1, 'Aprovação final design', 'Validar layouts criados por Roberto Mendes', 'concluida', 'baixa', '2025-09-05', 5),
(1, 'Relatório mensal vendas', 'Compilar dados de setembro com Juliana', 'pendente', 'alta', '2025-09-15', 6),
(1, 'Planejamento fiscal Q4', 'Reunião com Pedro sobre estratégias de fim de ano', 'pendente', 'media', '2025-09-11', 7),
-- Tarefas em atraso (vencidas)
(1, 'Enviar orçamento urgente', 'Proposta comercial que deveria ter sido enviada semana passada', 'pendente', 'alta', '2025-09-02', 1),
(1, 'Atualizar site empresa', 'Revisar conteúdo e corrigir links quebrados', 'pendente', 'media', '2025-09-01', NULL),
(1, 'Follow-up cliente antigo', 'Entrar em contato com Carlos sobre feedback do projeto', 'pendente', 'baixa', '2025-08-30', 3),
(1, 'Renovar certificados SSL', 'Certificados de segurança venceram na semana passada', 'em_andamento', 'alta', '2025-09-03', NULL),
(1, 'Revisar contratos pendentes', 'Análise jurídica de documentos acumulados', 'pendente', 'media', '2025-09-04', 4)
ON DUPLICATE KEY UPDATE title = title;

-- Inserir eventos do calendário
INSERT INTO calendar_events (user_id, title, description, event_date, event_time, contact_id) VALUES
(1, 'Reunião semanal TechCorp', 'Acompanhamento do projeto em andamento', '2025-09-09', '14:00:00', 1),
(1, 'Workshop estratégia digital', 'Análise de métricas e próximos passos', '2025-09-08', '10:00:00', 2),
(1, 'Pitch StartupTech', 'Apresentação da proposta de parceria', '2025-09-12', '15:30:00', 3),
(1, 'Consultoria jurídica', 'Revisão de contratos e documentação legal', '2025-09-09', '16:00:00', 4),
(1, 'Entrega projeto design', 'Apresentação dos layouts finalizados', '2025-09-06', '11:00:00', 5),
(1, 'Reunião vendas setembro', 'Análise de resultados do mês', '2025-09-13', '09:00:00', 6),
(1, 'Planejamento contábil', 'Estratégias fiscais para Q4 2025', '2025-09-11', '13:30:00', 7),
(1, 'Conferência Tech SP', 'Evento de networking e inovação', '2025-09-14', '08:00:00', NULL),
(1, 'Treinamento equipe', 'Capacitação em novas ferramentas de gestão', '2025-09-16', '14:00:00', NULL)
ON DUPLICATE KEY UPDATE title = title;