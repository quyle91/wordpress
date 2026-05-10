<?php

namespace WpDatabaseHelperV2\Fields;

class WpRepeaterItem {
    //
    protected string $id;
    public function getId() {
        return $this->id ?? '';
    }

    public function __construct() {
        $this->id = 'id_' . rand();
        $this->version = $this->getVersion();
    }

    //
    private $version;
    public static function getVersion() {
        // __DIR__ = .../src/Database
        $composerFile = dirname(__DIR__, 2) . '/composer.json'; // đi lên 2 cấp để tới root của package

        if (file_exists($composerFile)) {
            $composerData = json_decode(file_get_contents($composerFile), true);
            return $composerData['version'] ?? '0.0.0';
        }

        return '0.0.0';
    }

    // 
    protected array $classes = ['wpdh-repeater-item'];
    public function classes(array $classes): self {
        $this->classes = $classes;
        return $this;
    }
    public function addClass(string $class): self {
        $this->classes[] = $class;
        return $this;
    }

    // truyền cái này từ wprepeater 
    // ko show attr vào html để tránh lỗi js.
    protected string $namePrefix = '';
    public function namePrefix(string $v): self {
        $this->namePrefix = $v;
        return $this;
    }
    public function getNamePrefix() {
        return $this->namePrefix ?? '';
    }

    // chỉ dùng để ghi đè default
    protected ?object $parentRepeater = null;
    public function parentRepeater(?object $v): self {
        $this->parentRepeater = $v;
        return $this;
    }
    public function getParentRepeater() {
        return $this->parentRepeater ?? null;
    }

    //
    protected string $direction = 'vertical'; // vertical | horizontal | wrap
    public function direction(string $direction): self {
        $this->direction = $direction;
        return $this;
    }

    //
    protected array $fields = [];
    public function fields(array $fields): self {
        $this->fields = $fields;
        return $this;
    }
    public function getFields() {
        return $this->fields ?? '';
    }

    //
    protected mixed $value = false;
    public function value(mixed $v): self {
        $this->value = $v;
        return $this;
    }
    public function getValue() {
        return $this->value ?? '';
    }

    // index
    protected int $index = 0;
    public function index(int $index): self {
        $this->index = $index;
        return $this;
    }
    public function getIndex() {
        return $this->index ?? 0;
    }

    //
    public static function make(): self {
        return new self();
    }

    //
    protected string $renderId = '';
    public function render(): string {
        ob_start();

        $classes = [
            $this->getFileClass(),
            $this->direction,
        ];
        $classes = array_merge($this->classes, $classes );
        $classes = implode(' ', $classes);
        
        echo "<div class='{$classes}' data-index='{$this->index}'>";
        echo '<div class="wpdh-repeater-item-wrap">';

        // lặp fields cho phép loop theo schema mà ko phải là value.
        // repeater item là 1 mảng, trong mảng này sẽ loop để show ra field.
        // error_log(__CLASS__ . '::' . __FUNCTION__ . '() $this->namePrefix: ' . print_r($this->namePrefix, true));
        foreach ($this->fields as $field) {

            $originFieldName = $field->getName(); // name ko bao gồm prefix
            $___childValue = $this->value[$originFieldName] ?? false; // giá trị tương ứng trong root

            // nếu một field được khai báo trong schema nhưng ko có giá trị trong db thì ko gọi ra.
            // trừ khi nó dc skipCheckFlexibleItem
            if (
                $field->isHideIfDbValueNotSet() and
                !isset($this->value[$originFieldName])
            ) {
                // echo '<pre>'; print_r($field); echo '</pre>'; die;
                // skip error log
                if ($originFieldName !== 'fields') {
                    error_log('Field exists on schema but not found in db or default: ' . $originFieldName);
                }
                continue;
            }

            // Override từ parent (đúng index, không dùng $this->name)
            if ($this->parentRepeater instanceof WpRepeater) {
                $parentRepeaterDefault = $this->parentRepeater->getDefault();
                if ((empty($___childValue) || $___childValue === false) &&
                    isset($parentRepeaterDefault[$this->index][$originFieldName])
                ) {
                    $___childValue = $parentRepeaterDefault[$this->index][$originFieldName];
                }
            }

            // Repeater con
            if ($field instanceof WpRepeater) {
                echo $field
                    ->value($___childValue)
                    ->namePrefix($this->namePrefix)
                    ->parentRepeater($this)
                    ->render();
            }

            // Field thường
            if ($field instanceof WpField) {
                // var_dump($field->getName());
                echo $field
                    ->value($___childValue)
                    ->namePrefix($this->namePrefix)
                    ->render();
            }
        }

        echo '</div> <!-- wpdh-repeater-item-wrap -->'; // wrap

        //
        echo $this->getItemActions();
        echo "</div> <!-- wpdh-repeater-item -->"; // 
        return ob_get_clean();
    }

    function getItemActions() {
        ob_start();
        echo '<div class="wpdh-repeater-item-actions">';
        echo "<button type='button' class='button button-link wpdh-clone'>" . __('Clone') . "</button>";
        echo "<button type='button' class='button button-link wpdh-up'>" . __('Up') . "</button>";
        echo "<button type='button' class='button button-link wpdh-down'>" . __('Down') . "</button>";
        echo "<button type='button' class='button button-link wpdh-remove'>" . __('Remove') . "</button>";
        echo "<button type='button' class='button button-link wpdh-extend-view hidden'>" . __('Extended view') . "</button>";
        echo "</div> <!-- wpdh-repeater-item-actions -->"; // 
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
