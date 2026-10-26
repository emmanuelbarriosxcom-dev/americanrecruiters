<?php
/** AR 2026-10-26. Pending AQ integration; extends Yoast's graph, no second JSON-LD. */
if (!defined('ABSPATH')) { exit; }

function ar_seo_editorial_post($id) {
    $host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
    return in_array($host, [
        'desarrollo.americanrecruiters.mx',
        'www.americanrecruiters.com.mx',
        'americanrecruiters.com.mx',
    ], true) && in_array((int) $id, [
        156,159,718,762,766,769,772,775,778,781,783,808,816,818,820,822,824,
    ], true);
}

function ar_seo_editorial_name($name) {
    return is_singular('post') && ar_seo_editorial_post(get_the_ID())
        ? 'Equipo editorial de American Recruiters' : $name;
}

function ar_seo_has_reference($value, $id, $root = true) {
    if (!is_array($value)) { return false; }
    // A node's own ID is not an incoming reference.
    if (!$root && isset($value['@id']) && $value['@id'] === $id) { return true; }
    foreach ($value as $key => $child) {
        if ($key !== '@id' && is_array($child) && ar_seo_has_reference($child, $id, false)) {
            return true;
        }
    }
    return false;
}

function ar_seo_editorial_graph($graph) {
    if (!is_array($graph) || !is_singular('post') || !ar_seo_editorial_post(get_queried_object_id())) {
        return $graph;
    }
    $editorial_id = home_url('/blog/#equipo-editorial');
    $previous = [];
    $changed = false;
    foreach ($graph as &$node) {
        $types = (array) ($node['@type'] ?? []);
        if (!array_intersect($types, ['Article','BlogPosting','NewsArticle'])
            && !(in_array('WebPage', $types, true) && isset($node['author']))) {
            continue;
        }
        if (isset($node['author']['@id'])) { $previous[] = $node['author']['@id']; }
        $node['author'] = ['@id' => $editorial_id];
        $changed = true;
    }
    unset($node);
    if (!$changed) { return $graph; }

    $kept = [];
    foreach ($graph as $node) {
        $id = $node['@id'] ?? '';
        // Rebuild exactly one editorial node, without manufacturing a legal entity.
        if ($id === $editorial_id) { continue; }
        if (in_array('Person', (array) ($node['@type'] ?? []), true) && in_array($id, $previous, true)) {
            $referenced_elsewhere = false;
            foreach ($graph as $other) {
                if (($other['@id'] ?? '') !== $id && ar_seo_has_reference($other, $id)) {
                    $referenced_elsewhere = true;
                    break;
                }
            }
            if (!$referenced_elsewhere) { continue; }
        }
        $kept[] = $node;
    }
    $kept[] = [
        '@type' => 'Organization',
        '@id' => $editorial_id,
        'name' => 'Equipo editorial de American Recruiters',
        'url' => home_url('/blog/'),
    ];
    return $kept;
}
add_filter('the_author', 'ar_seo_editorial_name');
add_filter('wpseo_schema_graph', 'ar_seo_editorial_graph', 20);
