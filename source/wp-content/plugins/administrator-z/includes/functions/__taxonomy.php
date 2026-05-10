<?php
function adminz_get_taxonomy_options($post_type = 'product') {
    $taxonomies = get_object_taxonomies($post_type, 'objects');
    $return = [];
    foreach ($taxonomies as $key => $term) {
        if ($term->publicly_queryable) {
            $return[$key] = "Tax: $term->label";
        }
    }
    return $return;
}

function adminz_get_attribute_options($post_type = 'product') {
    $taxonomies = get_object_taxonomies($post_type, 'objects');
    $return = [];
    foreach ($taxonomies as $key => $term) {
        if (str_starts_with($term->name, 'pa_')) {
            $return[$key] = "Attribute: $term->label";
        }
    }
    return $return;
}

function adminz_get_meta_options($post_type = 'product', $exclude_empty = true, $exclude_hidden = true) {
    global $wpdb;
    $query = "
	SELECT DISTINCT($wpdb->postmeta.meta_key) 
	FROM $wpdb->posts 
	LEFT JOIN $wpdb->postmeta 
	ON $wpdb->posts.ID = $wpdb->postmeta.post_id 
	WHERE $wpdb->posts.post_type = '%s'
	";
    if ($exclude_empty)
        $query .= " AND $wpdb->postmeta.meta_key != ''";
    if ($exclude_hidden)
        $query .= " AND $wpdb->postmeta.meta_key NOT RegExp '(^[_0-9].+$)' ";
    $meta_keys = $wpdb->get_col($wpdb->prepare($query, $post_type));

    $return = [];
    foreach ($meta_keys as $key) {
        $value = apply_filters('adminz_get_meta_key_label', $key);
        $return[$key] = "Meta: $value";
    }
    return $return;
}

function adminz_get_meta_values($post_type = 'product', $meta_keys = []) {
    global $wpdb;
    $query = "
SELECT pm.meta_value 
FROM {$wpdb->postmeta} pm
JOIN {$wpdb->posts} p ON pm.post_id = p.ID
WHERE p.post_type = %s
";
    $params = [$post_type];
    if (!empty($meta_keys)) {
        $placeholders = implode(', ', array_fill(0, count($meta_keys), '%s'));
        $query .= " AND pm.meta_key IN ($placeholders)";
        $params = array_merge($params, $meta_keys);
    }
    $results = $wpdb->get_results($wpdb->prepare($query, ...$params));
    $meta_values = array();
    if (!empty($results)) {
        foreach ($results as $result) {
            // Add the meta_value to the array
            if ($result->meta_value and !in_array($result->meta_value, $meta_values)) {
                $meta_values[] = $result->meta_value;
            }
        }
    }
    sort($meta_values);
    return ($meta_values);
}
