-- Dados reais do site atual (gracachurchpalhoca.org). Tudo editável no painel.
INSERT INTO settings (k, v) VALUES
('church_name', 'Graça Church'),
('church_city_label', 'Palhoça · SC'),
('hero_title', 'Uma igreja que crê no avivamento para a nossa geração.'),
('hero_text', 'Venha como você está. Aqui você encontra uma família para caminhar com Deus.'),
('about_title', 'Cremos em um avivamento para nossa geração'),
('about_text', 'Através dos pilares de oração, jejum, leitura e aplicação da Bíblia Sagrada e santidade, buscamos como comunidade respeitar profundamente o Espírito Santo.\n\nEstamos vivendo um sonho como igreja. Nem em nossos melhores planos poderíamos imaginar o que estamos vivenciando. Deus pode fazer através de você. Junte-se a nós nesta jornada incrível!'),
('address_street', 'BR 282, KM 18'),
('address_city', 'Palhoça'),
('address_state', 'SC'),
('address_zip', '88135-550'),
('lat', ''),
('lng', ''),
('directions_note', ''),
('whatsapp_1', '48988501006'),
('whatsapp_2', '48996749702'),
('instagram', 'gracachurchpalhoca'),
('email', ''),
('groups_intro', 'Os grupos de convívio são encontros semanais nas casas, perto de você. Um tempo de oração, Palavra e amizade durante a semana.');

INSERT INTO events (slug, title, summary, description, category, recurrence, weekday, nth, start_time, duration_min, featured, sort, reminder_min) VALUES
('culto-de-domingo', 'Culto de domingo', 'Nosso culto principal da semana, com louvor, Palavra e oração.', 'Nosso culto principal da semana. Um tempo de louvor, Palavra e oração para toda a família.', 'culto', 'weekly', 0, NULL, '19:30:00', 120, 1, 1, 120),
('culto-de-quarta', 'Culto de quarta', 'Culto no meio da semana para renovar as forças.', 'Culto no meio da semana para renovar as forças, com louvor e ministração da Palavra.', 'culto', 'weekly', 3, NULL, '20:00:00', 120, 1, 2, 120),
('homens-de-honra', 'Homens de Honra', 'Encontro mensal dos homens da igreja.', 'Encontro mensal dos homens da Graça Church.', 'ministerio', 'monthly', 1, 1, '20:00:00', 120, 0, 3, 240),
('mulheres', 'Mulheres', 'Encontro mensal das mulheres da igreja.', 'Encontro mensal das mulheres da Graça Church.', 'ministerio', 'monthly', 1, 2, '20:00:00', 120, 0, 4, 240),
('graca-business', 'Graça Business', 'Encontro mensal de empreendedores e profissionais.', 'Encontro mensal de empreendedores e profissionais da Graça Church.', 'ministerio', 'monthly', 2, 3, '20:00:00', 120, 0, 5, 240),
('graca-point', 'Graça Point', 'Encontro mensal aos sábados.', 'Encontro mensal da Graça Church, no primeiro sábado do mês.', 'ministerio', 'monthly', 6, 1, '19:30:00', 120, 0, 6, 240);

INSERT INTO faqs (question, answer, sort) VALUES
('Qual o horário dos cultos da Graça Church?', 'Os cultos são aos domingos às 19h30 e às quartas às 20h, no templo da BR 282, KM 18, em Palhoça (SC).', 1),
('Onde fica a Graça Church?', 'Na BR 282, KM 18, Palhoça – SC, CEP 88135-550. Na página Como chegar há atalhos para Google Maps, Waze e Apple Maps.', 2),
('Preciso ser membro para participar dos cultos?', 'Não. Os cultos são abertos a todos. Venha como você está.', 3),
('O que são os grupos de convívio?', 'São encontros semanais nas casas, em vários bairros, para oração, estudo da Bíblia e amizade. Na página Grupos você encontra o mais perto de você e fala com o líder pelo WhatsApp.', 4),
('Como recebo avisos da programação no celular?', 'Toque em "Receber avisos" no site e permita as notificações. No iPhone, primeiro adicione o site à Tela de Início pelo botão Compartilhar do Safari, abra o app e ative os avisos.', 5),
('Como falo com a igreja?', 'Pelo WhatsApp (48) 98850-1006 ou (48) 99674-9702, ou pelo Instagram @gracachurchpalhoca.', 6);
