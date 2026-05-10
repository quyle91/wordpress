<?php

namespace WpDatabaseHelperV2\Ajax;

class HandleAppendRepeater {
    /**
     * Register AJAX actions for repeater append
     */
    public static function register(): void {
        add_action('wp_ajax_HandleAppendRepeater', [self::class, 'handle']);
    }

    public static function createField_func($value, $namePrefix) {
        $object = $value['object'] ?? '';

        if ($object == 'WpField') {
            // error_log('createField_func');
            // error_log(__CLASS__ . '::' . __FUNCTION__ . '() $value: ' . var_export($value, true));

            $field = \WpDatabaseHelperV2\Fields\WpField::make();
            // error_log(__CLASS__ . '::' . __FUNCTION__ . '() $field: ' . var_export($field, true));

            foreach ((array)$value as $method => $method_param) {
                // call method if exist
                // error_log('Checking for method exists: ' . $method);
                if (method_exists($field, $method)) {

                    if ($method == 'attribute' && isset($method_param['data-append-config'])) {

                        // fix stripslashes for data-append-config
                        if (is_string($method_param['data-append-config'])) {
                            $json = stripslashes($method_param['data-append-config']);
                            $method_param['data-append-config'] = $json;
                        }

                        // case inherit
                        if ($method_param['data-append-config'] == 'inherit') {
                            $method_param['data-append-config'] = json_encode($_POST['dataAppendConfig'] ?? []);
                            // echo '<pre>'; print_r($method_param); echo '</pre>';die;
                        }
                    }

                    $method_param = ($method_param == 'false') ? false : $method_param;
                    // error_log(__CLASS__ . '::' . __FUNCTION__ . '() $method: ' . $method);
                    // error_log(__CLASS__ . '::' . __FUNCTION__ . '() $method_param: ' . var_export($method_param, true));
                    $field->{$method}($method_param);
                }
            }

        }

        // 
        else if ($object == 'WpRepeater') {
            $field = \WpDatabaseHelperV2\Fields\WpRepeater::make();

            // Prepare child fields recursively
            $children = $value['fields'] ?? [];
            $fieldsOutput = [];
            foreach ((array)$children as $childValue) {
                // Recursively create child field with updated name prefix
                $childField = self::createField_func($childValue, $namePrefix . '[' . $value['name'] . ']');
                $fieldsOutput[] = $childField;
            }
            $value['fields'] = $fieldsOutput;
            // echo '<pre>'; print_r($value); echo '</pre>';die;
            foreach ((array)$value as $method => $method_param) {
                // call method if exist
                if (method_exists($field, $method)) {
                    $method_param = ($method_param == 'false') ? false : $method_param;
                    // error_log(__CLASS__ . '::' . __FUNCTION__ . '() $method: ' . $method);
                    // error_log(__CLASS__ . '::' . __FUNCTION__ . '() $method_param: ' . var_export($method_param, true));
                    $field->{$method}($method_param);
                }
            }

            // override name, 
            // $newName = $namePrefix . '[' . $value['name'] . ']';
            // $field->name($newName);
            $field->namePrefix($namePrefix);
            // echo '<pre>'; print_r($field); echo '</pre>';
        }

        //
        else {
            // not exists
        }

        //
        return $field;
    }

    public static function handle(): void {
        check_ajax_referer('wpdh_nonce', 'nonce');
        // echo '<pre>'; print_r($_POST); echo '</pre>';die;

        ob_start();

        // echo '<pre>'; print_r($_POST); echo '</pre>';die;
        $namePrefix = $_POST['namePrefix'] ?? '';
        $dataAppendConfig = $_POST['dataAppendConfig'] ?? [];
        $appendFields = $dataAppendConfig['appendFields'] ?? [];
        $currentValue = $_POST['currentValue'] ?? '';
        $appendFieldsOnValue = $appendFields[$currentValue] ?? [];
        // echo '<pre>'; print_r($dataAppendConfig); echo '</pre>';die;
        // echo '<pre>'; print_r($appendFieldsOnValue); echo '</pre>';die;

        $fields = [];
        foreach ((array)$appendFieldsOnValue as $key => $value) {

            // render
            $field = self::createField_func($value, $namePrefix);

            // override visible, compatity with toggleVisible button
            $visible = $_POST['showOrHide'] ?? 'show';
            $field->visible($visible);

            // mark as added field
            $field->addClass('wpdh-append-added');

            // echo '<pre>'; print_r($field); echo '</pre>'; die;
            $fields[] = $field;
        }

        // echo '<pre>'; print_r($fields); echo '</pre>'; die;

        // render
        foreach ((array)$fields as $key => $field) {

            if ($field instanceof \WpDatabaseHelperV2\Fields\WpRepeater) {
                echo $field->render();
            } else if ($field instanceof \WpDatabaseHelperV2\Fields\WpField) {
                echo $field->render();
            }
        }

        wp_send_json_success(ob_get_clean());
        wp_die();
    }
}
