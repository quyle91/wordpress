<?php

namespace Adminz\Helper;

class FlatsomePortfolio {

    function __construct() {
        //
    }

    function rename_post_type($portfolio_name) {
        add_filter('featured_itemposttype_args', function ($return) use ($portfolio_name) {
            if (!$portfolio_name) {
                return $return;
            }
            $return['labels'] = [
                'name'               => $portfolio_name,
                'singular_name'      => $portfolio_name,
                'add_new'            => __('Add New', 'flatsome-admin'), // phpcs:ignore
                'add_new_item'       => __('Add New', 'flatsome-admin'), // phpcs:ignore
                'edit_item'          => 'Edit ' . $portfolio_name,
                'new_item'           => 'Add new ' . $portfolio_name,
                'view_item'          => 'View ' . $portfolio_name,
                'search_items'       => 'Search ' . $portfolio_name,
                'not_found'          => __('No items found', 'flatsome-admin'), // phpcs:ignore
                'not_found_in_trash' => __('No items found in trash', 'flatsome-admin'), // phpcs:ignore
            ];
            $return['rewrite'] = [
                'slug' => sanitize_title($portfolio_name)
            ];
            return $return;
        }, 10, 1);
    }

    function rename_category($portfolio_category) {
        add_filter('featured_itemposttype_category_args', function ($return) use ($portfolio_category) {
            if (!$portfolio_category) {
                return $return;
            }
            $return['labels'] = [
                'name'                       => $portfolio_category,
                'singular_name'              => $portfolio_category,
                'menu_name'                  => $portfolio_category,
                'edit_item'                  => __('Edit Tag', 'flatsome-admin'), // phpcs:ignore
                'update_item'                => __('Update Tag', 'flatsome-admin'), // phpcs:ignore
                'add_new_item'               => __('Add New Tag', 'flatsome-admin'), // phpcs:ignore
                'new_item_name'              => __('New Tag Name', 'flatsome-admin'), // phpcs:ignore
                'parent_item'                => __('Parent Tag', 'flatsome-admin'), // phpcs:ignore
                'parent_item_colon'          => __('Parent Tag:', 'flatsome-admin'), // phpcs:ignore
                'all_items'                  => __('All Tags', 'flatsome-admin'), // phpcs:ignore
                'search_items'               => __('Search Tags', 'flatsome-admin'), // phpcs:ignore
                'popular_items'              => __('Popular Tags', 'flatsome-admin'), // phpcs:ignore
                'separate_items_with_commas' => __('Separate tags with commas', 'flatsome-admin'), // phpcs:ignore
                'add_or_remove_items'        => __('Add or remove tags', 'flatsome-admin'), // phpcs:ignore
                'choose_from_most_used'      => __('Choose from the most used tags', 'flatsome-admin'), // phpcs:ignore
                'not_found'                  => __('No tags found.', 'flatsome-admin'), // phpcs:ignore
            ];
            $return['rewrite'] = [
                'slug' => sanitize_title($portfolio_category)
            ];
            return $return;
        }, 10, 1);
    }

    function rename_tag($portfolio_tag) {
        add_filter('featured_itemposttype_tag_args', function ($return) use ($portfolio_tag) {
            if (!$portfolio_tag) {
                return $return;
            }
            $return['labels']  = [
                'name'                       => $portfolio_tag,
                'singular_name'              => $portfolio_tag,
                'menu_name'                  => $portfolio_tag,
                'edit_item'                  => __('Edit Category', 'flatsome-admin'), // phpcs:ignore
                'update_item'                => __('Update Category', 'flatsome-admin'), // phpcs:ignore
                'add_new_item'               => __('Add New Category', 'flatsome-admin'), // phpcs:ignore
                'new_item_name'              => __('New Category Name', 'flatsome-admin'), // phpcs:ignore
                'parent_item'                => __('Parent Category', 'flatsome-admin'), // phpcs:ignore
                'parent_item_colon'          => __('Parent Category:', 'flatsome-admin'), // phpcs:ignore
                'all_items'                  => __('All Categories', 'flatsome-admin'), // phpcs:ignore
                'search_items'               => __('Search Categories', 'flatsome-admin'), // phpcs:ignore
                'popular_items'              => __('Popular Categories', 'flatsome-admin'), // phpcs:ignore
                'separate_items_with_commas' => __('Separate categories with commas', 'flatsome-admin'), // phpcs:ignore
                'add_or_remove_items'        => __('Add or remove categories', 'flatsome-admin'), // phpcs:ignore
                'choose_from_most_used'      => __('Choose from the most used categories', 'flatsome-admin'), // phpcs:ignore
                'not_found'                  => __('No categories found.', 'flatsome-admin'), // phpcs:ignore
            ];
            $return['rewrite'] = [
                'slug' => sanitize_title($portfolio_tag)
            ];
            return $return;
        }, 10, 1);
    }
}
