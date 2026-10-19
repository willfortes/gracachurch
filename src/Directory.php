<?php
declare(strict_types=1);

namespace App;

/**
 * Guia de negócios dos membros: categorias e busca inteligente.
 * A busca ignora acentos e maiúsculas, entende sinônimos ("bolo" → confeitaria, "cano" → encanador),
 * aceita pedaços de palavras ("eletri") e corrige erros de digitação ("eletrisista").
 */
final class Directory
{
    /** slug => [ícone, pt, en, es] */
    public const CATEGORIES = [
        'alimentacao' => ['flame', 'Alimentação e confeitaria', 'Food and bakery', 'Alimentación y repostería'],
        'beleza' => ['sparkle', 'Beleza e estética', 'Beauty and aesthetics', 'Belleza y estética'],
        'saude' => ['hands', 'Saúde e bem-estar', 'Health and wellness', 'Salud y bienestar'],
        'casa' => ['home', 'Casa, reformas e construção', 'Home, repairs and construction', 'Hogar, reformas y construcción'],
        'automotivo' => ['map', 'Automotivo', 'Automotive', 'Automotriz'],
        'tecnologia' => ['settings', 'Tecnologia e informática', 'Technology and IT', 'Tecnología e informática'],
        'educacao' => ['book', 'Educação e aulas', 'Education and lessons', 'Educación y clases'],
        'juridico' => ['check', 'Advocacia e contabilidade', 'Legal and accounting', 'Abogacía y contabilidad'],
        'eventos' => ['calendar', 'Eventos, foto e vídeo', 'Events, photo and video', 'Eventos, foto y video'],
        'moda' => ['user', 'Moda e costura', 'Fashion and sewing', 'Moda y costura'],
        'marketing' => ['send', 'Marketing e design', 'Marketing and design', 'Marketing y diseño'],
        'imoveis' => ['pin', 'Imóveis', 'Real estate', 'Inmuebles'],
        'pets' => ['drop', 'Pets', 'Pets', 'Mascotas'],
        'servicos' => ['people', 'Serviços gerais', 'General services', 'Servicios generales'],
        'comercio' => ['share', 'Comércio e lojas', 'Shops and retail', 'Comercio y tiendas'],
        'outros' => ['plus', 'Outros', 'Other', 'Otros'],
    ];

    public const MODES = ['local', 'domicilio', 'online'];

    private const STOP = ['de', 'da', 'do', 'das', 'dos', 'e', 'em', 'para', 'pra', 'com', 'a', 'o', 'as', 'os', 'um', 'uma', 'no', 'na', 'nos', 'nas',
        'the', 'for', 'in', 'of', 'and', 'a', 'an', 'near', 'me', 'y', 'el', 'la', 'los', 'las', 'en', 'con', 'por', 'que', 'quem', 'quero', 'preciso',
        'procuro', 'perto', 'mim', 'bom', 'boa', 'melhor', 'servico', 'servicos', 'palhoca', 'need', 'busco', 'necesito', 'cerca'];

    /** Termos que a pessoa digita => termos que aparecem nos cadastros */
    private const SYNONYMS = [
        'bolo' => ['confeitaria', 'doces', 'bolos', 'confeiteira'], 'bolos' => ['confeitaria', 'doces'], 'doce' => ['confeitaria', 'doces', 'brigadeiro'],
        'salgado' => ['salgados', 'buffet', 'lanches'], 'marmita' => ['marmitas', 'refeicao', 'comida', 'quentinha'], 'comida' => ['alimentacao', 'refeicao', 'marmita'],
        'lanche' => ['lanches', 'hamburguer', 'lanchonete'], 'pizza' => ['pizzaria'], 'cafe' => ['cafeteria'],
        'unha' => ['manicure', 'unhas', 'pedicure', 'nail'], 'unhas' => ['manicure', 'pedicure'], 'cabelo' => ['cabeleireiro', 'cabeleireira', 'salao', 'barbearia'],
        'barba' => ['barbearia', 'barbeiro'], 'maquiagem' => ['make', 'maquiadora', 'estetica'], 'sobrancelha' => ['design', 'estetica', 'micropigmentacao'],
        'carro' => ['mecanico', 'oficina', 'automotivo', 'funilaria', 'auto'], 'moto' => ['mecanico', 'oficina', 'motos'], 'pneu' => ['borracharia'],
        'luz' => ['eletricista', 'eletrica'], 'tomada' => ['eletricista', 'eletrica'], 'eletrica' => ['eletricista'],
        'cano' => ['encanador', 'hidraulica'], 'vazamento' => ['encanador', 'hidraulica'], 'agua' => ['encanador', 'hidraulica'],
        'pintura' => ['pintor', 'pinturas'], 'parede' => ['pintor', 'pedreiro'], 'reforma' => ['pedreiro', 'construcao', 'reformas'], 'obra' => ['pedreiro', 'construcao', 'engenheiro'],
        'ar' => ['climatizacao', 'refrigeracao'], 'geladeira' => ['refrigeracao'], 'movel' => ['marcenaria', 'moveis', 'marceneiro'], 'moveis' => ['marcenaria', 'marceneiro'],
        'computador' => ['informatica', 'tecnologia', 'notebook', 'manutencao'], 'notebook' => ['informatica'], 'celular' => ['assistencia', 'smartphone', 'celulares'],
        'site' => ['sites', 'desenvolvimento', 'marketing', 'tecnologia', 'web'], 'app' => ['aplicativo', 'desenvolvimento', 'tecnologia'],
        'advogado' => ['advocacia', 'juridico', 'direito', 'advogada'], 'contador' => ['contabilidade', 'contabil', 'contadora'], 'imposto' => ['contabilidade'], 'mei' => ['contabilidade'],
        'dentista' => ['odontologia', 'dentes', 'odonto'], 'medico' => ['saude', 'clinica', 'medica'], 'psicologo' => ['psicologia', 'terapia', 'psicologa'],
        'massagem' => ['massoterapia', 'terapia'], 'academia' => ['personal', 'treino'], 'nutricionista' => ['nutricao', 'dieta'], 'fisioterapia' => ['fisioterapeuta'],
        'aula' => ['aulas', 'professor', 'professora', 'reforco', 'curso'], 'ingles' => ['idiomas', 'aulas', 'english'], 'musica' => ['aulas', 'violao', 'teclado', 'canto'],
        'festa' => ['eventos', 'decoracao', 'buffet', 'festas'], 'casamento' => ['eventos', 'fotografo', 'decoracao', 'cerimonial'],
        'foto' => ['fotografo', 'fotografia', 'fotografa'], 'video' => ['filmagem', 'videomaker'], 'som' => ['eventos', 'dj', 'sonorizacao'],
        'roupa' => ['moda', 'confeccao', 'costura', 'loja'], 'costura' => ['costureira', 'ajustes', 'reparos'], 'conserto' => ['reparos', 'manutencao', 'assistencia'],
        'casa' => ['imoveis', 'corretor', 'corretora'], 'aluguel' => ['imoveis', 'locacao'], 'apartamento' => ['imoveis', 'corretor'],
        'cachorro' => ['pet', 'pets', 'banho', 'tosa', 'veterinario'], 'gato' => ['pet', 'pets', 'veterinario'],
        'limpeza' => ['diarista', 'faxina', 'faxineira'], 'faxina' => ['diarista', 'limpeza'], 'frete' => ['mudanca', 'transporte', 'carreto'], 'mudanca' => ['frete', 'transporte', 'carreto'],
        'seguro' => ['seguros', 'corretora'], 'logo' => ['design', 'marketing'], 'instagram' => ['marketing', 'social', 'midia'],
        // inglês
        'cake' => ['confeitaria', 'bolo'], 'bakery' => ['confeitaria'], 'food' => ['alimentacao', 'comida'], 'hair' => ['cabeleireiro', 'salao'], 'nails' => ['manicure'],
        'plumber' => ['encanador'], 'electrician' => ['eletricista'], 'lawyer' => ['advocacia'], 'accountant' => ['contabilidade'], 'mechanic' => ['mecanico'],
        'cleaning' => ['limpeza', 'diarista'], 'photographer' => ['fotografo'], 'teacher' => ['aulas', 'professor'], 'painter' => ['pintor'], 'dentist' => ['odontologia'],
        // espanhol
        'pastel' => ['confeitaria', 'bolo'], 'pelo' => ['cabeleireiro', 'salao'], 'unas' => ['manicure'], 'fontanero' => ['encanador'], 'plomero' => ['encanador'],
        'electricista' => ['eletricista'], 'abogado' => ['advocacia'], 'limpieza' => ['limpeza', 'diarista'], 'fotografo' => ['fotografia'], 'clases' => ['aulas'],
    ];

    public static function categoryLabel(string $slug, ?string $lang = null): string
    {
        $i = ['pt' => 1, 'en' => 2, 'es' => 3][$lang ?? I18n::$lang];
        return (self::CATEGORIES[$slug] ?? self::CATEGORIES['outros'])[$i];
    }

    public static function categoryIcon(string $slug): string
    {
        return (self::CATEGORIES[$slug] ?? self::CATEGORIES['outros'])[0];
    }

    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = strtr($text, [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a', 'é' => 'e', 'ê' => 'e', 'è' => 'e', 'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o', 'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n',
        ]);
        return trim(preg_replace('/[^a-z0-9]+/', ' ', $text) ?? '');
    }

    /** @return list<string> */
    public static function tokens(string $text, bool $dropStop = false): array
    {
        $out = [];
        foreach (explode(' ', self::normalize($text)) as $t) {
            if ($t === '' || ($dropStop && in_array($t, self::STOP, true))) {
                continue;
            }
            // plural simples: "eletricistas" encontra "eletricista"
            if (strlen($t) > 4 && str_ends_with($t, 's')) {
                $out[] = substr($t, 0, -1);
            }
            $out[] = $t;
        }
        return array_values(array_unique($out));
    }

    /** Texto pesquisável gravado no cadastro (inclui o nome da categoria nos 3 idiomas). */
    public static function searchText(array $b): string
    {
        $cat = self::CATEGORIES[$b['category']] ?? self::CATEGORIES['outros'];
        return self::normalize(implode(' ', [$b['name'], $cat[1], $cat[2], $cat[3], $b['tags'], $b['description'], $b['neighborhood'], $b['city'], $b['owner_name'], $b['service_area'] ?? '']));
    }

    /**
     * Busca nos cadastros aprovados.
     * @return array{results: list<array>, suggestion: ?string, terms: list<string>}
     */
    public static function search(string $query, string $category = ''): array
    {
        $params = [];
        $where = "status = 'aprovado'";
        if ($category !== '' && isset(self::CATEGORIES[$category])) {
            $where .= ' AND category = ?';
            $params[] = $category;
        }
        $rows = Db::all("SELECT * FROM businesses WHERE $where", $params);
        $terms = self::tokens($query, true);
        if (!$terms) {
            usort($rows, fn ($a, $b) => [$b['featured'], $a['name']] <=> [$a['featured'], $b['name']]);
            return ['results' => $rows, 'suggestion' => null, 'terms' => []];
        }

        // Vocabulário: todas as palavras dos cadastros (para corrigir erros de digitação)
        $vocab = [];
        foreach ($rows as $r) {
            foreach (explode(' ', (string) $r['search_text']) as $w) {
                if (strlen($w) >= 3) {
                    $vocab[$w] = true;
                }
            }
        }
        foreach (array_keys(self::SYNONYMS) as $w) {
            $vocab[$w] = true;
        }

        $expanded = [];
        $corrected = [];
        foreach ($terms as $t) {
            $set = [$t];
            if (!self::inVocab($t, $vocab)) {
                $fix = self::closest($t, array_keys($vocab));
                if ($fix !== null) {
                    $set[] = $fix;
                    $corrected[$t] = $fix;
                }
            }
            foreach ($set as $s) {
                foreach (self::SYNONYMS[$s] ?? [] as $syn) {
                    $set[] = $syn;
                }
                foreach (self::CATEGORIES as $slug => $c) {
                    foreach ([$c[1], $c[2], $c[3]] as $label) {
                        if (in_array($s, self::tokens($label), true)) {
                            $set[] = $slug;
                        }
                    }
                }
            }
            $expanded[$t] = array_values(array_unique($set));
        }

        $scored = [];
        foreach ($rows as $r) {
            $fields = [
                [self::normalize($r['name']), 6],
                [self::normalize(self::categoryLabel($r['category'], 'pt') . ' ' . self::categoryLabel($r['category'], 'en') . ' ' . self::categoryLabel($r['category'], 'es') . ' ' . $r['category']), 4],
                [self::normalize($r['tags']), 4],
                [self::normalize($r['neighborhood'] . ' ' . $r['city'] . ' ' . ($r['service_area'] ?? '')), 2],
                [self::normalize($r['description']), 1.5],
                [self::normalize($r['owner_name']), 1],
            ];
            $score = 0.0;
            $matchedAll = true;
            foreach ($expanded as $orig => $set) {
                $best = 0.0;
                foreach ($fields as [$text, $w]) {
                    $words = explode(' ', $text);
                    foreach ($set as $k => $s) {
                        $factor = $k === 0 ? 1.0 : 0.7; // termo original vale mais que sinônimo/correção
                        foreach ($words as $word) {
                            if ($word === $s) {
                                $best = max($best, $w * $factor * 1.3);
                            } elseif (strlen($s) >= 3 && str_starts_with($word, $s)) {
                                $best = max($best, $w * $factor);
                            }
                        }
                    }
                }
                if ($best == 0.0) {
                    $matchedAll = false;
                }
                $score += $best;
            }
            if ($score > 0) {
                $r['_score'] = $score + ($matchedAll ? 10 : 0) + ($r['featured'] ? 1 : 0);
                $r['_all'] = $matchedAll;
                $scored[] = $r;
            }
        }
        // Se alguma coisa casou com todos os termos, mostra só essas
        if (array_filter($scored, fn ($r) => $r['_all'])) {
            $scored = array_values(array_filter($scored, fn ($r) => $r['_all']));
        }
        usort($scored, fn ($a, $b) => $b['_score'] <=> $a['_score']);

        $suggestion = null;
        if ($corrected) {
            $suggestion = implode(' ', array_map(fn ($t) => $corrected[$t] ?? $t, self::tokens($query, true)));
        }
        return ['results' => $scored, 'suggestion' => $suggestion, 'terms' => $terms];
    }

    private static function inVocab(string $t, array $vocab): bool
    {
        if (isset($vocab[$t])) {
            return true;
        }
        if (strlen($t) < 3) {
            return true; // curto demais para corrigir
        }
        foreach ($vocab as $w => $_) {
            if (str_starts_with($w, $t)) {
                return true;
            }
        }
        return false;
    }

    private static function closest(string $t, array $words): ?string
    {
        $max = strlen($t) >= 7 ? 2 : (strlen($t) >= 4 ? 1 : 0);
        if ($max === 0) {
            return null;
        }
        $best = null;
        $bestD = $max + 1;
        foreach ($words as $w) {
            if (abs(strlen($w) - strlen($t)) > $max) {
                continue;
            }
            $d = levenshtein($t, $w);
            if ($d < $bestD) {
                $bestD = $d;
                $best = $w;
            }
        }
        return $bestD <= $max ? $best : null;
    }

    /** Conta cadastros aprovados por categoria. */
    public static function counts(): array
    {
        $out = [];
        foreach (Db::all("SELECT category, COUNT(*) n FROM businesses WHERE status = 'aprovado' GROUP BY category") as $r) {
            $out[$r['category']] = (int) $r['n'];
        }
        return $out;
    }
}
