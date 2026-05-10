<?php
$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_tab_icons';
$___->shortcode_title = 'Tab icon';
$___->shortcode_icon = 'text';
$___->options = [
    'options' => [
        'type' => 'group',
        'heading' => __('Options'),
        'description' => 'Set Tabs Element a Class name and copy it here',
        'options' => [
            'tab_class' => array(
                'type' => 'textfield',
                'heading' => 'Tabs Element class',
                'default' => '',
            ),
            'ids' => array(
                'type' => 'gallery',
                'heading' => 'Icons',
            ),
        ]
    ]

];
$___->shortcode_callback = function ($atts) {
    extract(
        shortcode_atts(
            [
                'tab_class' => '',
                'ids' => '',
            ],
            $atts
        )
    );
    ob_start();
?>
    <style type="text/css">
        .<?php echo esc_attr($tab_class); ?>.nav .tab>a>span {
            display: inline-flex;
            gap: 0.5em;
            align-items: center;
            justify-content: center;
        }

        .<?php echo esc_attr($tab_class); ?>.nav .tab>a>span:before {
            content: "";
            width: 2em;
            height: 2em;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center center;
        }

        <?php
        $ids = explode(',', $ids);
        if (!empty($ids) and is_array($ids)) {
            foreach ($ids as $key => $id) {
        ?>.<?php echo esc_attr($tab_class); ?>.nav .tab:nth-child(<?php echo esc_attr($key + 1); ?>)>a>span:before {
            background-image: url("<?php echo wp_get_attachment_image_url($id, 'full', false); ?>");
        }

        <?php
            }
        }
        ?>
    </style>
<?php
    return ob_get_clean();
};
$___->general_element();
