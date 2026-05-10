<?php

namespace WpDatabaseHelperV2;

class Bootstrap {
    public static function run() {
        (new self())->init();
    }

    // must be run on init hook
    public function init() {

        // \WpDatabaseHelperV2\Meta\WpMeta::make()
        //     ->post_type('page')
        //     ->label('Complex Settings 1')
        //     ->fields(
        //         [
        //         // Field cơ bản
        //         \WpDatabaseHelperV2\Fields\WpField::make()
        //             ->kind('input')
        //             ->type('text')
        //             ->name('___page_subtitle')
        //             ->label('Subtitle')
        //             ->attributes(['placeholder' => 'Enter subtitle'])
        //             ->adminColumn(true)
        //             ->default('This is default subtitle'),
        //         ]
        //     )->register();

        // \WpDatabaseHelperV2\Meta\WpMeta::make()
        //     ->post_type('page')
        //     ->label('Complex Settings 2')
        //     ->fields(
        //         [
        //         // Field cơ bản
        //         \WpDatabaseHelperV2\Fields\WpField::make()
        //             ->kind('input')
        //             ->type('text')
        //             ->name('___page_subtitle')
        //             ->label('Subtitle')
        //             ->attributes(['placeholder' => 'Enter subtitle'])
        //             ->adminColumn(true)
        //             ->default('This is default subtitle'),
        //         ]
        //     )->register();

        // \WpDatabaseHelperV2\Meta\WpMeta::make()
        //     ->post_type('page')
        //     ->label('Complex Settings 3')
        //     ->fields(
        //         [
        //         // Field cơ bản
        //         \WpDatabaseHelperV2\Fields\WpField::make()
        //             ->kind('input')
        //             ->type('text')
        //             ->name('___page_subtitle')
        //             ->label('Subtitle')
        //             ->attributes(['placeholder' => 'Enter subtitle'])
        //             ->adminColumn(true)
        //             ->default('This is default subtitle'),
        //         ]
        //     )->register();

        // 
        \WpDatabaseHelperV2\Meta\WpMeta::make()
            ->post_type('page')
            ->label('Complex Settings')
            ->fields([

                // // tab navs
                // \WpDatabaseHelperV2\Fields\WpField::make()
                //     ->kind('tab')
                //     ->type('nav')
                //     ->tabNavs(['General', 'FAQ', 'Advanced',]),

                // // tab start
                // \WpDatabaseHelperV2\Fields\WpField::make()
                //     ->kind('tab')
                //     ->type('start')
                //     ->label('General'),

                // Field cơ bản
                \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name('___page_subtitle')
                    ->label('Subtitle')
                    ->attributes(['placeholder' => 'Enter subtitle'])
                    ->adminColumn(true)
                    ->default('This is default subtitle'),

                // Repeater cấp 1: FAQ
                \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name('___faq_list')
                    ->label('FAQ List')
                    ->direction('vertical')
                    ->adminColumn(true)
                    ->fields([

                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('___question')
                            ->label('Question')
                            ->default('Question'),

                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('___answer')
                            ->label('Answer')
                            ->default('Answer'),

                        // Repeater cấp 2: Related links (default riêng)
                        \WpDatabaseHelperV2\Fields\WpRepeater::make()
                            ->name('___related_links')
                            ->label('Related Links')
                            ->childDirection('horizontal')
                            ->fields([

                                \WpDatabaseHelperV2\Fields\WpField::make()
                                    ->kind('input')
                                    ->type('text')
                                    ->name('___link_title')
                                    ->label('Link Title')
                                    ->default('Link Title'),

                                \WpDatabaseHelperV2\Fields\WpField::make()
                                    ->kind('input')
                                    ->type('text')
                                    ->name('___link_url')
                                    ->label('Link URL')
                                    ->default('Link URL'),
                            ])
                            ->default([
                                [
                                    '___link_title' => 'Link Title',
                                    '___link_url'   => 'Link URL',
                                ]
                            ]),
                    ])
                    ->default([
                        [
                            '___question' => 'How to contact us?',
                            '___answer'   => 'Use the form below.',
                            '___related_links' => [
                                [
                                    '___link_title' => 'Docs',
                                    '___link_url' => 'https://docs.local',
                                ],
                                [
                                    '___link_title' => 'Docs 2',
                                    '___link_url' => 'https://docs.local2',
                                ],
                            ],
                        ],
                        [
                            '___question' => 'What is this site?',
                            '___answer'   => 'This is a demo.',
                        ],
                    ]),

                // // end tab
                // \WpDatabaseHelperV2\Fields\WpField::make()
                //     ->kind('tab')
                //     ->type('end'),

                // // tab start
                // \WpDatabaseHelperV2\Fields\WpField::make()
                //     ->kind('tab')
                //     ->type('start')
                //     ->label('FAQ'),

                // // Field cơ bản
                // \WpDatabaseHelperV2\Fields\WpField::make()
                //     ->kind('input')
                //     ->type('text')
                //     ->name('___page_subtitle_2')
                //     ->label('Subtitle 2')
                //     ->attributes(['placeholder' => 'Enter subtitle 2'])
                //     ->adminColumn(true)
                //     ->default('This is default subtitle'),

                // // end tab
                // \WpDatabaseHelperV2\Fields\WpField::make()
                //     ->kind('tab')
                //     ->type('end'),

                // // tab start
                // \WpDatabaseHelperV2\Fields\WpField::make()
                //     ->kind('tab')
                //     ->type('start')
                //     ->label('Advanced'),

                // // Field cơ bản
                // \WpDatabaseHelperV2\Fields\WpField::make()
                //     ->kind('input')
                //     ->type('text')
                //     ->name('___page_subtitle_3')
                //     ->label('Subtitle 3')
                //     ->attributes(['placeholder' => 'Enter subtitle 3'])
                //     ->adminColumn(true)
                //     ->default('This is default subtitle'),

                // // end tab
                // \WpDatabaseHelperV2\Fields\WpField::make()
                //     ->kind('tab')
                //     ->type('end'),

            ])->register();


        // Field text bình thường
        // add_action('wpdh_meta_box_after', function ($post, $wpmeta) {
        //     if($wpmeta->getLabel() != 'Complex Settings') return;

        //     echo '<pre>';
        //     print_r('---------- TEST INPUT without save data ----------');
        //     echo '</pre>';
        //     echo \WpDatabaseHelperV2\Fields\WpField::make()
        //         ->kind('input')
        //         ->type('text')
        //         ->value($this->settings['test_input'] ?? false) // giá trị đã lưu
        //         ->name('adminz_admin[test_input]')
        //         ->label('Test Input')
        //         ->attributes(['placeholder' => 'Enter test'])
        //         ->default('This is default test')
        //         ->render();

        //     echo '<pre>';
        //     print_r('---------- TEST Repeater without save data ----------');
        //     echo '</pre>';
        //     echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
        //         ->name('adminz_admin[test_repeater]')
        //         ->value($this->settings['test_repeater'] ?? false) // giá trị đã lưu
        //         ->label('Test Repeater')
        //         ->fields([
        //             \WpDatabaseHelperV2\Fields\WpField::make()
        //                 ->kind('input')
        //                 ->type('text')
        //                 ->name('___test_name_')
        //                 ->label('Test')
        //                 ->default('This is default test')
        //                 ->attributes(['placeholder' => 'Enter test']),
        //             \WpDatabaseHelperV2\Fields\WpField::make()
        //                 ->kind('input')
        //                 ->type('text')
        //                 ->name('___test_name_2')
        //                 ->label('Test 2')
        //                 ->default('This is default test 2')
        //                 ->attributes(['placeholder' => 'Enter test 2']),
        //         ])
        //         ->default([
        //             [
        //                 '___test_name_' => 'This is default test',
        //                 '___test_name_2' => 'This is default test 2',
        //             ],
        //             [
        //                 '___test_name_' => 'This is default test x',
        //                 '___test_name_2' => 'This is default test 2 x',
        //             ],
        //         ])
        //         ->render();
        // }, 10, 2);

        // create database
        // \WpDatabaseHelperV2\Database\DbTable::make()
        //     ->name('wpdh_table_example')
        //     ->title('WPDH table Example')
        //     ->fields([
        //         // required
        //         \WpDatabaseHelperV2\Database\DbColumn::make()
        //             ->name('id')
        //             ->type('INT(11)')
        //             ->notNull()
        //             ->autoIncrement()
        //             ->primary(),

        //         \WpDatabaseHelperV2\Database\DbColumn::make()
        //             ->name('post_id')
        //             ->type('INT(11)')
        //             ->notNull(),

        //         \WpDatabaseHelperV2\Database\DbColumn::make()
        //             ->name('title')
        //             ->type('VARCHAR(255)')
        //             ->notNull(),

        //         \WpDatabaseHelperV2\Database\DbColumn::make()
        //             ->name('created_at')
        //             ->type('DATETIME')
        //             ->default('CURRENT_TIMESTAMP')
        //             ->timestamp(),

        //         \WpDatabaseHelperV2\Database\DbColumn::make()
        //             ->name('updated_at')
        //             ->type('DATETIME')
        //             ->default('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
        //             ->timestamp(),
        //     ])
        //     ->registerAdminPage()
        //     ->seedDemoData()
        //     ->create();
        // \WpDatabaseHelperV2\Database\DbTable::getByName('wpdh_table_example')->empty();
    }
}


// add_action('init', function () {
//     $bootstrap = new \WpDatabaseHelperV2\Bootstrap();
//     $bootstrap->init();
// });