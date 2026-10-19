-- Grupos de convívio reais, copiados de gracachurchpalhoca.org/mapaGC.html (outubro de 2026)
ALTER TABLE cell_groups ADD COLUMN assistants VARCHAR(160) NULL AFTER leader_phone;

DELETE FROM cell_groups WHERE slug LIKE 'exemplo-%';
DELETE FROM events WHERE slug LIKE 'exemplo-%';

-- Local exato da igreja (o grupo 1 se reúne no templo)
UPDATE settings SET v = '-27.673343' WHERE k = 'lat';
UPDATE settings SET v = '-48.693981' WHERE k = 'lng';
UPDATE settings SET v = 'Entrada pela Servidão José Kirchner, 750, no bairro Aririu.' WHERE k = 'directions_note' AND (v IS NULL OR v = '');
INSERT IGNORE INTO translations (entity, ref_id, field, lang, value) VALUES
('setting', 0, 'directions_note', 'en', 'Entrance on Servidão José Kirchner, 750, in the Aririu neighborhood.'),
('setting', 0, 'directions_note', 'es', 'Entrada por la Servidão José Kirchner, 750, en el barrio Aririu.');

INSERT IGNORE INTO cell_groups (slug, name, neighborhood, city, address, show_address, lat, lng, weekday, start_time, audience, leader_name, leader_phone, assistants) VALUES
('gc-aririu-marlison-e-katy', 'GC Marlison e Katy', 'Aririu', 'Palhoça', 'BR-282, KM 18, Servidão José Kirchner, 750 (na igreja)', 1, -27.673343, -48.693981, 4, '20:00:00', 'Homens e mulheres', 'Marlison e Katy', '48988154326', NULL),
('gc-aririu-john-e-maevelyn', 'GC John e Maevelyn', 'Aririu', 'Palhoça', 'Rua Caetano de Medeiros, 527', 1, -27.670568, -48.681149, 5, '20:30:00', 'Jovens', 'John e Maevelyn', '48996526558', 'Elias e Paula'),
('gc-caminho-novo-fernando-e-scheila', 'GC Fernando e Scheila', 'Caminho Novo', 'Palhoça', 'Rua João Duarte Raimundo, 45', 1, -27.647044, -48.689572, 4, '20:00:00', 'Homens e mulheres', 'Fernando e Scheila', '48999085872', 'Alamir e Katiane'),
('gc-barra-do-aririu-jhonathan-e-laura', 'GC Jhonathan e Laura', 'Barra do Aririu', 'Palhoça', 'Rua Gerardus Post, 301', 1, -27.677143, -48.654757, 4, '20:00:00', 'Homens e mulheres', 'Jhonathan e Laura', '48996271385', 'Aldori e Edileuza'),
('gc-brejaru-andreia', 'GC Andreia', 'Brejaru', 'Palhoça', 'Rua José Linhares, 122', 1, -27.620254, -48.669820, 5, '20:00:00', 'Homens e mulheres', 'Andreia', '48996265266', 'Paulo e Lizandra'),
('gc-aririu-aline-e-rick', 'GC Aline e Rick', 'Aririu', 'Palhoça', 'Rua Adam Schütz, 92', 1, -27.671136, -48.685980, 6, '16:00:00', 'Adolescentes', 'Aline e Rick', '45999402644', NULL),
('gc-centro-glacy-e-tayse', 'GC Glacy e Tayse', 'Centro', 'Palhoça', 'Centro Comercial Thiago, R. José Maria da Luz, 2747, sala 308, 3º andar', 1, -27.646337, -48.666496, 4, '20:00:00', 'Mulheres', 'Glacy e Tayse', '48996507869', NULL),
('gc-centro-mauro-e-janete', 'GC Mauro e Janete', 'Centro', 'Palhoça', 'Rua Dario Nicodemos de Oliveira, 49, ap. 202', 1, -27.654357, -48.672867, 4, '20:00:00', 'Homens e mulheres', 'Mauro e Janete', '48996751754', 'Lia'),
('gc-enseada-da-pinheira-gustavo', 'GC Gustavo', 'Enseada da Pinheira', 'Palhoça', 'Servidão Manoel Paulino de Matos, 33', 1, -27.890484, -48.585641, 5, '20:00:00', 'Homens e mulheres', 'Gustavo', '48991259333', NULL);

-- Público de cada grupo em inglês e espanhol
INSERT IGNORE INTO translations (entity, ref_id, field, lang, value)
SELECT 'group', g.id, 'audience', t.lang, t.value FROM cell_groups g
JOIN (
  SELECT 'Homens e mulheres' AS aud, 'en' AS lang, 'Men and women' AS value
  UNION ALL SELECT 'Homens e mulheres', 'es', 'Hombres y mujeres'
  UNION ALL SELECT 'Jovens', 'en', 'Young adults'
  UNION ALL SELECT 'Jovens', 'es', 'Jóvenes'
  UNION ALL SELECT 'Adolescentes', 'en', 'Teens'
  UNION ALL SELECT 'Adolescentes', 'es', 'Adolescentes'
  UNION ALL SELECT 'Mulheres', 'en', 'Women'
  UNION ALL SELECT 'Mulheres', 'es', 'Mujeres'
) t ON t.aud = g.audience
WHERE g.slug LIKE 'gc-%';
