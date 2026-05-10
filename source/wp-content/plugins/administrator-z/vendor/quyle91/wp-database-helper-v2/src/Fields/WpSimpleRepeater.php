<?php

namespace WpDatabaseHelperV2\Fields;
// Repeater với field dụng cho mảng 1 chiều
// ví dụ [a,b,c]
// name set là null
class WpSimpleRepeater extends WpRepeater {
    // override make để return đúng class con
    public static function make(): self {
        \WpDatabaseHelperV2\Services\Assets::get_instance();

        // new static để late static binding
        return new static();
    }

    /**
     * Field duy nhất cho mỗi item
     */
    protected ?WpField $field = null;

    /**
     * Gán field cho simple repeater
     */
    public function field(\WpDatabaseHelperV2\Fields\WpField $field): self {
        $this->field = $field;
        return $this;
    }

    /**
     * Render override hoàn toàn để support mảng 1 chiều
     */
    public function render(): string {
        $namePrefix = $this->namePrefix;
        $this->renderId = $this->id . "_" . rand();

        // pass namePrefix: namePrefix[name]
        // do not pass namePrefix: name
        $fullBase = $namePrefix ? "{$namePrefix}[{$this->name}]" : $this->name;

        // load value từ dbValue hoặc default
        $___values = $this->getRepeaterValueToRender();

        // check if empty
        if (empty($___values)) {
            return '';
        }

        // wrapper classes (reuse logic cũ)
        $classes = $this->getClassNameArray();

        ob_start();
        echo "<div class='" . implode(' ', $classes) . "' data-base='" . esc_attr($fullBase) . "' data-name='" . esc_attr($this->name) . "' id='{$this->renderId}'>";

        // label
        echo $this->getLabelString();

        // wpdh-repeater-items
        $classes = implode(' ', $this->classes);
        echo "<div class='{$classes}'>";

        foreach ($___values as $index => $itemValue) {

            if (!$this->field) {
                continue;
            }

            // name luôn là meta_key[]
            $inputName = $namePrefix
                ? "{$namePrefix}[{$this->name}][]"
                : "{$this->name}[]";

            $field = clone $this->field;
            $field->name($inputName);
            $field->value($itemValue);
            // echo "<div class='wpdh-repeater-item {$this->childDirection}'>";
            // echo $field->render();
            // echo "</div>";

            echo \WpDatabaseHelperV2\Fields\WpSimpleRepeaterItem::make()
                ->index($index)
                ->direction($this->childDirection)
                ->field($field)
                ->render();

        }

        echo "</div>"; // items
        echo $this->getDebugButton();
        echo $this->getNotesHtml();
        echo "</div>"; // wrapper
        return ob_get_clean();
    }
}
