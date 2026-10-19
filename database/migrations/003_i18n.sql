-- Traduções opcionais do conteúdo (o português é o original). entity: event | group | faq | setting
CREATE TABLE translations (
  entity VARCHAR(20) NOT NULL,
  ref_id INT UNSIGNED NOT NULL,
  field VARCHAR(40) NOT NULL,
  lang CHAR(2) NOT NULL,
  value TEXT NOT NULL,
  PRIMARY KEY (entity, ref_id, field, lang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE push_subscriptions ADD COLUMN lang CHAR(2) NOT NULL DEFAULT 'pt' AFTER topics;

-- Título e mensagem em outros idiomas: {"en":{"title":"...","body":"..."},"es":{...}}
ALTER TABLE notifications ADD COLUMN i18n JSON NULL AFTER body;

-- Programação real
INSERT INTO translations (entity, ref_id, field, lang, value)
SELECT 'event', id, f.field, f.lang, f.value FROM events
JOIN (
  SELECT 'culto-de-domingo' AS slug, 'title' AS field, 'en' AS lang, 'Sunday Service' AS value
  UNION ALL SELECT 'culto-de-domingo', 'title', 'es', 'Culto del domingo'
  UNION ALL SELECT 'culto-de-domingo', 'summary', 'en', 'Our main service of the week, with worship, the Word and prayer.'
  UNION ALL SELECT 'culto-de-domingo', 'summary', 'es', 'Nuestro culto principal de la semana, con alabanza, Palabra y oración.'
  UNION ALL SELECT 'culto-de-domingo', 'description', 'en', 'Our main service of the week. A time of worship, the Word and prayer for the whole family.'
  UNION ALL SELECT 'culto-de-domingo', 'description', 'es', 'Nuestro culto principal de la semana. Un tiempo de alabanza, Palabra y oración para toda la familia.'
  UNION ALL SELECT 'culto-de-quarta', 'title', 'en', 'Wednesday Service'
  UNION ALL SELECT 'culto-de-quarta', 'title', 'es', 'Culto del miércoles'
  UNION ALL SELECT 'culto-de-quarta', 'summary', 'en', 'A midweek service to renew your strength.'
  UNION ALL SELECT 'culto-de-quarta', 'summary', 'es', 'Un culto a mitad de semana para renovar las fuerzas.'
  UNION ALL SELECT 'culto-de-quarta', 'description', 'en', 'A midweek service to renew your strength, with worship and teaching from the Word.'
  UNION ALL SELECT 'culto-de-quarta', 'description', 'es', 'Un culto a mitad de semana para renovar las fuerzas, con alabanza y enseñanza de la Palabra.'
  UNION ALL SELECT 'homens-de-honra', 'title', 'en', 'Men of Honor'
  UNION ALL SELECT 'homens-de-honra', 'title', 'es', 'Hombres de Honra'
  UNION ALL SELECT 'homens-de-honra', 'summary', 'en', 'Monthly gathering for the men of the church.'
  UNION ALL SELECT 'homens-de-honra', 'summary', 'es', 'Encuentro mensual de los hombres de la iglesia.'
  UNION ALL SELECT 'homens-de-honra', 'description', 'en', 'Monthly gathering for the men of Graça Church.'
  UNION ALL SELECT 'homens-de-honra', 'description', 'es', 'Encuentro mensual de los hombres de Graça Church.'
  UNION ALL SELECT 'mulheres', 'title', 'en', 'Women'
  UNION ALL SELECT 'mulheres', 'title', 'es', 'Mujeres'
  UNION ALL SELECT 'mulheres', 'summary', 'en', 'Monthly gathering for the women of the church.'
  UNION ALL SELECT 'mulheres', 'summary', 'es', 'Encuentro mensual de las mujeres de la iglesia.'
  UNION ALL SELECT 'mulheres', 'description', 'en', 'Monthly gathering for the women of Graça Church.'
  UNION ALL SELECT 'mulheres', 'description', 'es', 'Encuentro mensual de las mujeres de Graça Church.'
  UNION ALL SELECT 'graca-business', 'summary', 'en', 'Monthly gathering for entrepreneurs and professionals.'
  UNION ALL SELECT 'graca-business', 'summary', 'es', 'Encuentro mensual de emprendedores y profesionales.'
  UNION ALL SELECT 'graca-business', 'description', 'en', 'Monthly gathering for the entrepreneurs and professionals of Graça Church.'
  UNION ALL SELECT 'graca-business', 'description', 'es', 'Encuentro mensual de emprendedores y profesionales de Graça Church.'
  UNION ALL SELECT 'graca-point', 'summary', 'en', 'Monthly gathering on Saturdays.'
  UNION ALL SELECT 'graca-point', 'summary', 'es', 'Encuentro mensual los sábados.'
  UNION ALL SELECT 'graca-point', 'description', 'en', 'Monthly Graça Church gathering on the first Saturday of the month.'
  UNION ALL SELECT 'graca-point', 'description', 'es', 'Encuentro mensual de Graça Church el primer sábado del mes.'
) f ON f.slug = events.slug;

-- Textos gerais
INSERT INTO translations (entity, ref_id, field, lang, value) VALUES
('setting', 0, 'hero_title', 'en', 'A church that believes in revival for our generation.'),
('setting', 0, 'hero_title', 'es', 'Una iglesia que cree en el avivamiento para nuestra generación.'),
('setting', 0, 'hero_text', 'en', 'Come as you are. Here you will find a family to walk with God.'),
('setting', 0, 'hero_text', 'es', 'Ven como eres. Aquí encontrarás una familia para caminar con Dios.'),
('setting', 0, 'about_title', 'en', 'We believe in revival for our generation'),
('setting', 0, 'about_title', 'es', 'Creemos en un avivamiento para nuestra generación'),
('setting', 0, 'about_text', 'en', 'Through the pillars of prayer, fasting, reading and living out the Holy Bible, and holiness, we seek as a community to deeply honor the Holy Spirit.\n\nWe are living a dream as a church. Not even in our best plans could we have imagined what we are experiencing. God can work through you. Join us on this incredible journey!'),
('setting', 0, 'about_text', 'es', 'A través de los pilares de la oración, el ayuno, la lectura y aplicación de la Santa Biblia, y la santidad, buscamos como comunidad honrar profundamente al Espíritu Santo.\n\nEstamos viviendo un sueño como iglesia. Ni en nuestros mejores planes podríamos imaginar lo que estamos viviendo. Dios puede obrar a través de ti. ¡Únete a nosotros en este viaje increíble!'),
('setting', 0, 'groups_intro', 'en', 'Community groups are weekly gatherings in homes near you. A time of prayer, the Word and friendship during the week.'),
('setting', 0, 'groups_intro', 'es', 'Los grupos de convivencia son encuentros semanales en casas cerca de ti. Un tiempo de oración, Palabra y amistad durante la semana.');

-- Perguntas frequentes (pela ordem)
INSERT INTO translations (entity, ref_id, field, lang, value)
SELECT 'faq', id, f.field, f.lang, f.value FROM faqs
JOIN (
  SELECT 1 AS s, 'question' AS field, 'en' AS lang, 'What time are the services at Graça Church?' AS value
  UNION ALL SELECT 1, 'answer', 'en', 'Services are on Sundays at 7:30 PM and Wednesdays at 8 PM, at BR 282, KM 18, in Palhoça (SC), Brazil.'
  UNION ALL SELECT 1, 'question', 'es', '¿A qué hora son los cultos de Graça Church?'
  UNION ALL SELECT 1, 'answer', 'es', 'Los cultos son los domingos a las 19:30 y los miércoles a las 20:00, en la BR 282, KM 18, en Palhoça (SC), Brasil.'
  UNION ALL SELECT 2, 'question', 'en', 'Where is Graça Church?'
  UNION ALL SELECT 2, 'answer', 'en', 'At BR 282, KM 18, Palhoça – SC, Brazil, ZIP 88135-550. The Directions page has shortcuts to Google Maps, Waze and Apple Maps.'
  UNION ALL SELECT 2, 'question', 'es', '¿Dónde queda Graça Church?'
  UNION ALL SELECT 2, 'answer', 'es', 'En la BR 282, KM 18, Palhoça – SC, Brasil, CP 88135-550. En la página Cómo llegar hay accesos a Google Maps, Waze y Apple Maps.'
  UNION ALL SELECT 3, 'question', 'en', 'Do I need to be a member to attend?'
  UNION ALL SELECT 3, 'answer', 'en', 'No. Services are open to everyone. Come as you are.'
  UNION ALL SELECT 3, 'question', 'es', '¿Necesito ser miembro para participar?'
  UNION ALL SELECT 3, 'answer', 'es', 'No. Los cultos están abiertos a todos. Ven como eres.'
  UNION ALL SELECT 4, 'question', 'en', 'What are community groups?'
  UNION ALL SELECT 4, 'answer', 'en', 'Weekly gatherings in homes across many neighborhoods, for prayer, Bible study and friendship. On the Groups page you can find the closest one and message the leader on WhatsApp.'
  UNION ALL SELECT 4, 'question', 'es', '¿Qué son los grupos de convivencia?'
  UNION ALL SELECT 4, 'answer', 'es', 'Son encuentros semanales en casas de varios barrios, para orar, estudiar la Biblia y compartir. En la página Grupos encuentras el más cercano y hablas con el líder por WhatsApp.'
  UNION ALL SELECT 5, 'question', 'en', 'How do I get schedule alerts on my phone?'
  UNION ALL SELECT 5, 'answer', 'en', 'Tap "Get alerts" on the website and allow notifications. On iPhone, first add the site to your Home Screen from the Share button in Safari, open the app and turn on alerts.'
  UNION ALL SELECT 5, 'question', 'es', '¿Cómo recibo avisos de la programación en el celular?'
  UNION ALL SELECT 5, 'answer', 'es', 'Toca "Recibir avisos" en el sitio y permite las notificaciones. En iPhone, primero agrega el sitio a la pantalla de inicio desde el botón Compartir de Safari, abre la app y activa los avisos.'
  UNION ALL SELECT 6, 'question', 'en', 'How can I contact the church?'
  UNION ALL SELECT 6, 'answer', 'en', 'On WhatsApp at +55 48 98850-1006 or +55 48 99674-9702, or on Instagram @gracachurchpalhoca.'
  UNION ALL SELECT 6, 'question', 'es', '¿Cómo me comunico con la iglesia?'
  UNION ALL SELECT 6, 'answer', 'es', 'Por WhatsApp al +55 48 98850-1006 o +55 48 99674-9702, o por Instagram @gracachurchpalhoca.'
) f ON f.s = faqs.sort;
