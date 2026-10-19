-- Grupos e evento de EXEMPLO, só para testar o site localmente.
-- Não rode em produção. Carregar com: php bin/migrate.php --demo
INSERT INTO cell_groups (slug, name, neighborhood, city, address, show_address, lat, lng, weekday, start_time, audience, leader_name, leader_phone, description) VALUES
('exemplo-pagani', 'Grupo Pagani (exemplo)', 'Pagani', 'Palhoça', 'Rua de exemplo, 100 – Pagani', 0, -27.6370, -48.6690, 4, '20:00:00', 'Todos', 'Líder de exemplo', '48900000001', 'Grupo de exemplo cadastrado para testar o site.'),
('exemplo-ponte-imaruim', 'Grupo Ponte do Imaruim (exemplo)', 'Ponte do Imaruim', 'Palhoça', 'Rua de exemplo, 200 – Ponte do Imaruim', 0, -27.6215, -48.6490, 2, '20:00:00', 'Casais', 'Líder de exemplo', '48900000002', 'Grupo de exemplo cadastrado para testar o site.'),
('exemplo-centro-sjose', 'Grupo Kobrasol (exemplo)', 'Kobrasol', 'São José', 'Rua de exemplo, 300 – Kobrasol', 0, -27.5915, -48.6140, 5, '19:30:00', 'Jovens', 'Líder de exemplo', '48900000003', 'Grupo de exemplo cadastrado para testar o site.');

UPDATE settings SET v = '-27.6510' WHERE k = 'lat';
UPDATE settings SET v = '-48.7150' WHERE k = 'lng';

INSERT INTO events (slug, title, summary, description, category, recurrence, date, start_time, duration_min, featured, sort) VALUES
('exemplo-conferencia', 'Conferência de exemplo', 'Evento de exemplo para testar a página.', 'Evento de exemplo cadastrado para testar o site.', 'evento', 'once', DATE_ADD(CURDATE(), INTERVAL 12 DAY), '19:00:00', 180, 0, 10);
