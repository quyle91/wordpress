<?php

namespace WpDatabaseHelperV2\Fields;

class WpRepeater {
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
    public static function make(): self {
        \WpDatabaseHelperV2\Services\Assets::get_instance();
        return new self();
    }

    // base name of the repeater
    protected string $name;
    public function name(string $name): self {
        $this->name = $name;

        // Lưu vào registry ngay khi name được set
        self::$registry[$this->name] = $this;

        return $this;
    }
    public function getName(): string {
        return $this->name ?? '';
    }

    //
    protected static array $registry = [];
    public static function getByName(string $name): self {
        // Nếu đã có trong registry → trả về object cũ
        if (isset(self::$registry[$name])) {
            return self::$registry[$name];
        }

        // Nếu chưa có → tạo object mới chỉ với name
        $instance = new self();
        $instance->name = $name;

        return $instance;
    }

    // Tiền tố của group, 
    // Level root thì để empty, chỉ sử dụng cho level 2, 
    protected string $namePrefix = '';
    public function namePrefix(string $v): self {
        $this->namePrefix = $v;
        return $this;
    }
    public function getNamePrefix() {
        return $this->namePrefix ?? '';
    }

    //
    protected string $label = '';
    public function label(string $label): self {
        $this->label = $label;
        return $this;
    }
    public function getLabel(): string {
        return $this->label ?? '';
    }

    // 
    protected array $notes = [];
    public function notes(mixed $notes): self {
        $notes = (array)$notes;
        $this->notes = $notes;
        return $this;
    }
    public function addNote(string $note): self {
        if (!$note) {
            return $this;
        }
        $this->notes[] = $note;
        return $this;
    }

    //
    protected array $default = [];
    public function default($data): self {
        $data = (array) $data; // force array
        $this->default = $data;
        return $this;
    }
    public function getDefault() {
        return $this->default ?? '';
    }

    //
    protected string $direction = 'vertical'; // vertical | horizontal | wrap
    public function direction(string $direction): self {
        $this->direction = $direction;
        return $this;
    }

    // WpRepeaterItem
    protected string $childDirection = 'vertical'; // vertical | horizontal | wrap
    public function childDirection(string $childDirection): self {
        $this->childDirection = $childDirection;
        return $this;
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

    // chỉ dùng để ghi đè default
    protected ?object $parentRepeater = null;
    public function parentRepeater(?object $v): self {
        $this->parentRepeater = $v;
        return $this;
    }
    public function getParentRepeater() {
        return $this->parentRepeater ?? null;
    }

    // dùng trong wpmeta
    protected bool $adminColumn = false;
    public function adminColumn(bool|string $enable = true): self {
        if ($enable === 'false') $enable = false;
        $this->adminColumn = $enable;
        return $this;
    }
    public function getAdminColumn(): bool {
        return $this->adminColumn ?? false;
    }

    // dùng trong wpmeta
    protected bool $showInMetaBox = true;
    public function showInMetaBox(bool|string $enable = true): self {
        if ($enable === 'false') $enable = false;
        $this->showInMetaBox = $enable;
        return $this;
    }
    public function getShowInMetaBox(): bool {
        return $this->showInMetaBox ?? false;
    }

    // dùng trong wpmeta
    protected bool $sortable = true;
    public function sortable(bool|string $enable = true): self {
        if ($enable === 'false') $enable = false;
        $this->sortable = $enable;
        return $this;
    }
    public function getSortable(): bool {
        return $this->sortable ?? true;
    }

    //
    protected bool $hasHiddenFields = false;
    public function hasHiddenFields(bool|string $v = true): self {
        if ($v === 'false') $v = false;
        $this->hasHiddenFields = $v;
        return $this;
    }

    // 
    protected array $classes = ['wpdh-repeater-items'];
    public function classes(mixed $classes): self {
        $classes = (array) $classes;
        $this->classes = $classes;
        return $this;
    }
    public function addClass(string $class): self {
        $this->classes[] = $class;
        return $this;
    }

    protected bool $hideIfDbValueNotSet = false;
    public function hideIfDbValueNotSet(bool|string $enable = true): self {
        if ($enable === 'false') $enable = false;
        $this->hideIfDbValueNotSet = $enable;
        return $this;
    }
    public function isHideIfDbValueNotSet(): bool {
        return $this->hideIfDbValueNotSet ?? false;
    }

    //
    protected string $visible = 'show';
    public function visible(string $v): self {
        $this->visible = $v;
        return $this;
    }
    public function getvisible(): string {
        return $this->visible ?? '';
    }
    public function show(): self {
        return $this->visible('show');
    }
    public function hidden(): self {
        return $this->visible('hidden');
    }

    // width: string
    protected string $width = '';
    public function width(string $width): self {
        $this->width = $width;
        return $this;
    }
    public function getWidth(): string {
        return $this->width ?? '';
    }

    // width: gridColumn
    protected string $gridColumn = '';
    public function gridColumn(string $gridColumn): self {
        $this->gridColumn = $gridColumn;
        return $this;
    }
    public function getGridColumn(): string {
        return $this->gridColumn ?? '';
    }

    // SimpleRepeater sẽ override method này
    public function field(\WpDatabaseHelperV2\Fields\WpField $field): self {
        // class cha không dùng field đơn
        // để trống để đảm bảo chain không fatal
        return $this;
    }

    // Repeater items phải có cấu trúc giống hệt nhau và được khai báo từ trước.
    protected array $fields = [];
    public function fields(mixed $fields): self {
        $fields = (array) $fields;
        $this->fields = $fields;
        return $this;
    }
    public function getFields(): array {
        return $this->fields ?? [];
    }

    //
    protected string $renderId = '';
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

        $classes = $this->getClassNameArray();

        ob_start();
        echo "<div class='" . implode(' ', $classes) . "' data-base='" . esc_attr($fullBase) . "' data-name='" . esc_attr($this->name) . "' id='{$this->renderId}'>";

        // label
        echo $this->getLabelString();

        // wpdh-repeater-items
        $classes = implode(' ', $this->classes);
        echo "<div class='{$classes}'>";

        // lặp qua các item trong ___values, mỗi $___value__ là 1 mảng (1 hoặc 2 chiều)
        // Mỗi value item tương ứng với một repeater item.
        // repeater item là 1 mảng, trong mảng này sẽ loop để show ra field.
        foreach ($___values as $index => $___value__) {

            $repeaterItemPrefix = $namePrefix
                ? "{$namePrefix}[{$this->name}][{$index}]"
                : "{$this->name}[{$index}]";

            echo \WpDatabaseHelperV2\Fields\WpRepeaterItem::make()
                ->namePrefix($repeaterItemPrefix)
                ->fields($this->fields)
                ->parentRepeater($this->parentRepeater)
                ->value($___value__)
                ->index($index)
                ->direction($this->childDirection)
                ->render();
        }

        echo "</div>"; // .wpdh-repeater-items
        echo $this->getDebugButton();
        echo $this->getNotesHtml();
        echo "</div>"; // wpdh-repeater
        return ob_get_clean();
    }

    // others
    protected function getRepeaterValueToRender() {
        $___values = $this->value;
        // false: chưa được lưu
        if ($___values === false) {
            $___values = $this->default;
        }
        // '': đã được lưu và ko có giá trị => load default thay vì empty
        if ($___values == '') {
            $___values = $this->default;
        }
        // empty: 
        if (empty($___values)) {
            $___values = $this->default;
        }

        if (!is_array($___values)) {
            $___values = [];
        }

        return $___values;
    }

    protected function getClassNameArray() {
        $classes = [
            $this->getFileClass(),
            'wpdh-repeater',
            "wpdh-repeater-name-{$this->name}",
            "wpdh-repeater-namePrefix-{$this->namePrefix}",
            "wpdh-width-{$this->width}",
            "wpdh-gridColumn-{$this->gridColumn}",
            $this->visible,
            $this->direction,
            $this->hasHiddenFields ? 'has-hidden-fields' : '',
        ];
        if ($this->classes) {
            $classes = array_merge($classes, $this->classes);
        }
        return $classes;
    }

    protected function getLabelString() {
        $return = '';
        if ($this->label) {
            $return .= "<div class='wpdh-repeater-label'>";
            $return .= "<span><span class='dashicons dashicons-editor-ol'></span>{$this->label}</span>";
            $return .= "</div>"; // .wpdh-repeater-label
        }
        return $return;
    }

    protected function getNotesHtml() {
        $return = '';
        if ($this->notes) {
            $return .= "<div class='wpdh-repeater-notes'>";
            foreach ((array)$this->notes as $key => $note) {
                // add star 
                $star_html = '';
                for ($i = 0; $i < ($key + 1); $i++) {
                    $star_html .= '*';
                }
                $return .= "<div class='wpdh-repeater-note'><small>$star_html" . __('Note') . ": {$note}</small></div>";
            }
            $return .= "</div>";
        }
        return $return;
    }

    protected function getDebugButton() {
        $return = '';
        if ($this->isLocalHost()) {
            $return .= "<button type='button' class='button wpdh-debug'>Debug</button>";
        }
        return $return;
    }

    protected function isLocalHost() {
        return;
        return get_site_url() == 'https://flatsome.local';
        // return ($_SERVER['SERVER_ADDR'] ?? '') === '127.0.0.1' || ($_SERVER['HTTP_HOST'] ?? '') === 'localhost';
    }

    public function toArray() {
        // init result array
        $result = [];

        // init reflection
        $reflect = new \ReflectionClass($this);

        // get all properties
        $properties = $reflect->getProperties();

        // loop through each property
        foreach ($properties as $prop) {
            // make property accessible
            $prop->setAccessible(true);

            // get property name
            $key = $prop->getName();

            // get property value
            $value = $prop->getValue($this);

            // assign to result
            $result[$key] = $value;
        }

        // return final array
        return $result;
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
