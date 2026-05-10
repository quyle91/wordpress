<?php

namespace WpDatabaseHelperV2\Example;

final class DbBuilder {

    function __construct() {
        //
    }

    private string $option_name = '';
    private mixed $dbValue = false;
    public static function make($option_name = '', $dbValue = false): self {
        $instance = new self();
        $instance->option_name = $option_name;
        $instance->dbValue = $dbValue;
        return $instance;
    }

    public function render() {
        $settings = \WpDatabaseHelperV2\Fields\WpRepeater::make()
            ->name($this->option_name)
            ->value($this->dbValue)
            ->label('Tables')
            ->fields([
                //
                \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name('table_name')
                    ->label('Table name')
                    ->default('')
                    ->attributes(['placeholder' => 'wpdh_table_example']),
                //
                \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name('table_title')
                    ->label('Table title')
                    ->default('')
                    ->attributes(['placeholder' => 'WPDH table Example']),

                // registerAdminPage
                \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name('registerAdminPage')
                    ->label('Register Admin Page'),
                //
                \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name('table_columns')
                    ->label('Columns')
                    ->hasHiddenFields()
                    ->fields([

                        // Tên cột
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('column_name')
                            ->label('Column name')
                            ->default('')
                            ->attributes(['placeholder' => 'column_name']),

                        // Kiểu dữ liệu
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('column_type')
                            ->label('Column type')
                            ->visible('hidden')
                            ->default('VARCHAR(255)')
                            ->options(
                                ['' => __('Select'), 'TINYINT(1)' => 'TINYINT(1)', 'INT(11)' => 'INT(11)', 'BIGINT(20)' => 'BIGINT(20)', 'DECIMAL(10,2)' => 'DECIMAL(10,2)', 'DATE' => 'DATE', 'DATETIME' => 'DATETIME', 'TIMESTAMP' => 'TIMESTAMP', 'VARCHAR(255)' => 'VARCHAR(255)', 'TEXT' => 'TEXT', 'LONGTEXT' => 'LONGTEXT',]
                            ),

                        // Không cho null
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('checkbox')
                            ->name('not_null')
                            ->label('NOT NULL')
                            ->visible('hidden'),

                        // AUTO_INCREMENT
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('checkbox')
                            ->name('auto_increment')
                            ->label('AUTO_INCREMENT')
                            ->visible('hidden'),

                        // UNSIGNED
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('checkbox')
                            ->name('unsigned')
                            ->label('UNSIGNED')
                            ->visible('hidden'),

                        // Giá trị mặc định
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('default_value')
                            ->label('Default value')
                            ->visible('hidden')
                            ->default('')
                            ->attributes(['placeholder' => 'NULL or CURRENT_TIMESTAMP']),

                        // ON UPDATE CURRENT_TIMESTAMP
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('checkbox')
                            ->name('on_update')
                            ->label('ON UPDATE CURRENT_TIMESTAMP')
                            ->visible('hidden'),

                        // PRIMARY KEY
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('checkbox')
                            ->name('primary')
                            ->label('PRIMARY KEY')
                            ->visible('hidden'),
                    ])
            ])
            ->default([
                [
                    'table_name' => '',
                    'table_title' => '',
                    'registerAdminPage' => 'on',
                    'table_columns' => [
                        [
                            'column_name' => '',
                            'column_type' => 'VARCHAR(255)',
                            'not_null' => '',
                            'auto_increment' => '',
                            'unsigned' => '',
                            'default_value' => '',
                            'on_update' => '',
                            'primary' => '',
                        ]
                    ]
                ],
            ]);

        ob_start();
        echo $settings->render();
        $note = __('Notes');
        echo <<<HTML
        <small><strong>$note: </strong>Click the "Drop table" button if you change the table structure (only show when url params has <strong>?debug</strong>).</small>
        HTML;
        return ob_get_clean();
    }

    public function read() {

        //
        if (!$this->dbValue) {
            return;
        }

        add_action('init', function () {
            foreach ($this->dbValue as $table) {

                //
                if (!($table['table_name'] ?? '')) {
                    continue;
                }

                //
                $dbTable = \WpDatabaseHelperV2\Database\DbTable::make();
                $dbTable->name($table['table_name'] ?? '');
                $dbTable->title($table['table_title'] ?? '');

                $fields = [];
                foreach ($table['table_columns'] ?? [] as $column) {

                    $column_name = trim($column['column_name'] ?? '');
                    if ($column_name === '') {
                        continue; // Skip empty column
                    }

                    $dbColumn = \WpDatabaseHelperV2\Database\DbColumn::make();
                    $dbColumn->name($column['column_name'] ?? '');
                    $dbColumn->type($column['column_type'] ?? '');
                    $dbColumn->notNull($column['not_null'] ?? '');
                    $dbColumn->autoIncrement($column['auto_increment'] ?? '');
                    $dbColumn->unsigned($column['unsigned'] ?? '');
                    $dbColumn->default($column['default_value'] ?? '');
                    $dbColumn->onUpdateCurrentTimestamp($column['on_update'] ?? '');
                    $dbColumn->primary($column['primary'] ?? '');
                    $fields[] = $dbColumn;
                }

                $dbTable->fields($fields);
                $dbTable->registerAdminPage();
                $dbTable->create();
            }
        });
    }
}
