<?php

namespace WpDatabaseHelperV2\Fields;

class WpSimpleRepeaterItem {
    protected $index = 0;
    protected $direction = '';
    protected $field = null;

    public static function make() {
        return new self();
    }

    public function index($index) {
        $this->index = (int) $index;

        return $this;
    }

    public function direction($direction) {
        $this->direction = (string) $direction;

        return $this;
    }

    public function field($field) {
        $this->field = $field;

        return $this;
    }

    public function render() {
        // nếu chưa có field thì không render
        if (!$this->field) {
            return '';
        }

        // build class cho item
        $classes = [
            $this->getFileClass(),
            'wpdh-repeater-item',
            $this->direction,
        ];
        $classes = array_filter($classes);
        $classAttr = implode(' ', $classes);

        //
        ob_start();
        echo "<div class=\"{$classAttr}\" data-index=\"{$this->index}\">";
        echo '<div class="wpdh-repeater-item-wrap">';

        if ($this->field instanceof WpField) {
            echo $this->field->render();
        }
        echo '</div> <!-- wpdh-repeater-item-wrap -->'; // wrap

        //
        echo '<div class="wpdh-repeater-item-actions">';
        echo "<button type='button' class='button button-link wpdh-clone'>" . __('Clone') . "</button>";
        echo "<button type='button' class='button button-link wpdh-up'>" . __('Up') . "</button>";
        echo "<button type='button' class='button button-link wpdh-down'>" . __('Down') . "</button>";
        echo "<button type='button' class='button button-link wpdh-remove'>" . __('Remove') . "</button>";
        echo "<button type='button' class='button button-link wpdh-extend-view hidden'>" . __('Extended view') . "</button>";
        echo "</div> <!-- wpdh-repeater-item-actions -->"; // 
        echo "</div> <!-- wpdh-repeater-item -->"; // 
        return ob_get_clean();
    }

    public function getFileClass() {
        // lấy full class name (có namespace)
        $className = static::class;

        // nếu không có class name thì return rỗng
        if (empty($className)) {
            return '';
        }

        // tách namespace để lấy tên class cuối cùng
        $parts = explode('\\', $className);

        // lấy short class name
        $shortClass = end($parts);

        // sanitize để dùng an toàn cho HTML class
        $shortClass = sanitize_html_class($shortClass);

        return $shortClass;
    }
}
