<?php
add_shortcode('adminz_countviews', 'adminz_countview_function');
function adminz_countview_function($atts) {
    extract(shortcode_atts(array(
        'icon' => 'eye',
        'textbefore' => '',
        'textafter' => '',
        'class' => 'adminz_count_view',
    ), $atts));

    ob_start();
?>
    <div class="<?= esc_attr($class) ?>">
        <?= esc_attr($textbefore) ?>
        <?= adminz_get_icon($icon, ["style" => ["width" => "1em", "display" => "inline-block"],]) ?>
        <span>
            <?php
            $view = get_post_meta(get_the_ID(), 'adminz_countview', true);
            echo $view ? $view : "0";
            ?>
        </span>
        <?= esc_attr($textafter) ?>
    </div>
<?php

    // bonus count view 
    $count = (int) get_post_meta(get_the_ID(), 'adminz_countview', true);
    $count++;
    update_post_meta(get_the_ID(), 'adminz_countview', $count);

    return ob_get_clean();
}
