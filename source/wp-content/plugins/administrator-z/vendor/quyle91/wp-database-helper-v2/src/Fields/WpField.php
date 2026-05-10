<?php

namespace WpDatabaseHelperV2\Fields;

class WpField {
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
    public static function make(): self {
        \WpDatabaseHelperV2\Services\Assets::get_instance();
        return new self();
    }

    // 
    protected string $kind = 'input';
    public function kind($kind): self {
        $this->kind = $kind;
        return $this;
    }
    public function getKind(): string {
        return $this->kind ?? '';
    }

    //
    protected string $type = 'text';
    public function type(string $type): self {
        $this->type = $type;
        return $this;
    }
    public function getType(): string {
        return $this->type ?? '';
    }

    //
    protected ?string $name = null;
    public function name($name): self {
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
    protected array $attributes = [];
    public function attributes(mixed $attrs): self {
        $attrs = (array) $attrs;
        // error_log(__CLASS__ . '::' . __FUNCTION__ . '() $this->name: ' . var_export($this->name, true));
        // error_log(__CLASS__ . '::' . __FUNCTION__ . '() $attrs: ' . var_export($attrs, true));
        $this->attributes = array_merge($this->attributes, $attrs);
        return $this;
    }
    public function getAttributes(): array {
        return $this->attributes ?? [];
    }
    public function removeAttribute($att_name) {
        if (isset($this->attributes[$att_name])) {
            unset($this->attributes[$att_name]);
        }
        return $this;
    }
    public function setAttribute($att_name, $value) {
        $this->attributes[$att_name] = $value;
        return $this;
    }
    public function renderAttributes(): string {

        // Fix class
        $this->attributes['class'] = $this->attributes['class'] ?? '';
        if (is_array($this->attributes['class'])) {
            $this->attributes['class'] = implode(' ', $this->attributes['class']);
        }
        $this->attributes['class'] .= ' wpdh-control';
        $this->attributes['class'] = str_replace('  ', ' ', $this->attributes['class']);
        $this->attributes['class'] = explode(' ', $this->attributes['class']);
        $this->attributes['class'] = array_unique($this->attributes['class']);
        $this->attributes['class'] = implode(' ', $this->attributes['class']);

        // fix attributes if is textarea
        if ($this->kind == 'textarea') {
            $rows = $this->attributes['rows'] ?? 4;
            $cols = $this->attributes['cols'] ?? 65;
            $this->attributes = array_merge($this->attributes, ['rows' => $rows, 'cols' => $cols]);
        }

        $attrs = '';
        foreach ($this->attributes as $k => $v) {
            $attrs .= ' ' . esc_attr($k) . '="' . esc_attr($v) . '"';
        }
        return $attrs;
    }

    //
    protected string $childDirection = '';
    public function childDirection(string $childDirection): self {
        $this->childDirection = $childDirection;
        return $this;
    }

    //
    protected mixed $default = '';
    public function default(mixed $v): self {
        $this->default = $v;
        return $this;
    }
    public function getDefault() {
        return $this->default ?? '';
    }

    //
    protected string $direction = 'vertical';
    public function direction(string $direction): self {
        $this->direction = $direction;
        return $this;
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
    protected mixed $value = false;
    public function value(mixed $v): self {
        $this->value = $v;
        return $this;
    }
    public function getValue() {
        return $this->value ?? '';
    }

    //
    protected string $namePrefix = '';
    public function namePrefix(string $v): self {
        $this->namePrefix = $v;
        return $this;
    }
    public function getNamePrefix() {
        return $this->namePrefix ?? '';
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

    //
    protected array $tabNavs = [];
    public function tabNavs(mixed $items = []): self {
        $items = (array) $items;
        $this->tabNavs = $items;
        return $this;
    }
    public function getTabNavs(): array {
        return $this->tabNavs ?? [];
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

    // inlineCss
    protected string $inlineCss = '';
    public function inlineCss(string $inlineCss): self {
        $this->inlineCss = $inlineCss;
        return $this;
    }
    public function getInlineCss(): string {
        return $this->inlineCss ?? '';
    }
    public function renderInlineCss() {
        $return = '';

        // inlineCss
        if (!empty($this->getInlineCss())) {
            $return .= $this->getInlineCss();
        }

        // only return if has strings
        if ($return) {
            return " style=\"" . $return . "\"";
        }

        //
        return;
    }

    // 
    protected mixed $options = [];
    public function options(mixed $items = []): self {
        // make sure is array
        $items = (array) $items;

        // Pretty up options if is select dropdown
        $has_first_select = array_key_exists('', $items) || array_key_exists(0, $items);
        $is_select_dropdown = $this->kind == 'select';
        $is_input_radio = $this->kind == 'input' && $this->type == 'radio';
        //
        if (!$has_first_select) {
            if ($is_select_dropdown or $is_input_radio) {
                $items = ['' => __('Select')] + $items;
            }
        }

        $this->options = $items;
        return $this;
    }
    public function getOptions(): array {
        return $this->options ?? [];
    }

    //
    protected string $optionsTemplate = '';
    protected array $optionsTemplateArgs = [];

    public function optionsTemplate(string $template, mixed $optionsTemplateArgs = []): self {

        $items = [];
        if ($template == 'user_roles') {
            $items = array_combine(array_keys(get_editable_roles()), array_keys(get_editable_roles()));
        }

        if ($template == 'post_types') {
            $items = array_combine(array_keys(get_post_types()), array_keys(get_post_types()));
        }

        if ($template == 'post_select') {
            // get list of post post ID-> post title 
            $post_type = $optionsTemplateArgs['post_type'] ?? 'any';
            $posts = get_posts(
                array(
                    'post_type' => $post_type,
                    'posts_per_page' => -1,
                )
            );
            foreach ($posts as $post) {
                $items[$post->ID] = "[ID: $post->ID] $post->post_title";
            }
        }

        if ($template == 'taxonomies') {
            $items = get_taxonomies();
        }

        //
        $this->optionsTemplate = $template;
        $this->optionsTemplateArgs = $optionsTemplateArgs;
        $this->options($items);
        return $this;
    }

    //
    protected array $classes = [];
    public function classes(mixed $classes): self {
        $classes = (array) $classes;
        $this->classes = $classes;
        return $this;
    }
    public function addClass(string $class): self {
        $this->classes[] = $class;
        return $this;
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

    // 
    protected bool $includeHiddenInput = false;
    public function includeHiddenInput(bool|string $enable = true): self {
        if ($enable === 'false') $enable = false;
        $this->includeHiddenInput = $enable;
        return $this;
    }

    // 
    protected bool $copyButton = true;
    public function copyButton(bool|string $v): self {
        if ($v === 'false') $v = false;
        $this->copyButton = $v;
        return $this;
    }
    public function showCopyButton(): self {
        $this->copyButton = true;
        return $this;
    }

    // 
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
    protected string $renderId = '';
    public function render(): string {
        $this->renderId = $this->id . "_" . rand();
        $dbValue = $this->value;
        $namePrefix = $this->namePrefix;

        // chưa được lưu thì load default
        // '' : được lưu rồi thì ko làm gì cả, tôn trọng admin
        if ($dbValue === false) {
            $dbValue = $this->default;
        }

        $fullName = $namePrefix ? "{$namePrefix}[{$this->name}]" : $this->name;
        $validate = $this->validate_field_name($fullName);
        $validate_status = $validate['status'];
        if (!$validate_status) {
            return $validate['message'];
        }

        // prepare classes
        $classes = [
            $this->getFileClass(),
            'wpdh-field',
            "wpdh-field-kind-{$this->kind}",
            "wpdh-field-type-{$this->type}",
            "wpdh-field-name-{$this->name}",
            "wpdh-field-visible-{$this->visible}",
            "wpdh-width-{$this->width}",
            "wpdh-gridColumn-{$this->gridColumn}",
            $this->visible,
            $this->direction,
            !empty($this->notes) ? 'has-notes' : '',
        ];
        if ($this->classes) {
            $classes = array_merge($classes, $this->classes);
        }
        $classes = trim(implode(' ', $classes));
        // echo '<pre>'; print_r($classes); echo '</pre>';

        // only for tabs
        if ($this->kind == 'tab') {
            ob_start();

            // echo '<pre>'; print_r($fullName); echo '</pre>';
            switch ($this->type ?? '') {
                case 'nav':
                    $tabs = $this->tabNavs ?? [];
                    echo "<ul class='$classes' " . $this->renderAttributes() . $this->renderInlineCss() . ">";
                    foreach ($tabs as $i => $label) {
                        $slug = sanitize_title($label);
                        $active = $i == 0 ? 'active' : '';
                        echo "<li class='{$active}' data-tab='{$slug}'>{$label}</li>";
                    }
                    // includeHiddenInput
                    if ($this->includeHiddenInput) {
                        $val = esc_attr($dbValue ?? '');
                        echo "<input type='hidden' name='{$fullName}' value='{$val}'>";
                    }
                    echo '</ul>';
                    break;

                case 'start':
                    $slug = sanitize_title($this->label);
                    // default is hidden, we use javascript to show first item later
                    echo "<div class='$classes hidden' data-tab='{$slug}' " . $this->renderAttributes() . $this->renderInlineCss() . ">";
                    // includeHiddenInput
                    if ($this->includeHiddenInput) {
                        $val = esc_attr($dbValue ?? '');
                        echo "<input type='hidden' name='{$fullName}' value='{$val}'>";
                    }
                    break;
                case 'end':
                    // includeHiddenInput
                    if ($this->includeHiddenInput) {
                        $val = esc_attr($dbValue ?? '');
                        echo "<input type='hidden' name='{$fullName}' value='{$val}'>";
                    }
                    echo "</div>";
                    break;
            }
            return ob_get_clean();
        }


        ob_start();

        echo "<div class='{$classes}' id='{$this->renderId}' " . $this->renderInlineCss() . ">";

        // Label
        echo $this->showLabel();

        // control
        echo "<div class='wpdh-field-control'>";
        switch ($this->kind ?? '') {

            // 🧩 SELECT
            case 'select':
                $options = $this->options ?? [];
                echo "<select id='for_{$this->renderId}' name='{$fullName}'" . $this->renderAttributes() . ">";
                foreach ($options as $k => $v) {
                    $selected = $dbValue == $k ? 'selected' : '';
                    echo "<option value='{$k}' {$selected}>{$v}</option>";
                }
                echo "</select>";
                break;

            // 🧩 TEXTAREA
            case 'textarea':

                switch ($this->type ?? '') {
                    case 'wp_editor':
                        $content = $dbValue ?? '';
                        $editor_id = "for_{$this->renderId}";
                        $settings = [
                            'textarea_name' => $fullName,
                            'textarea_rows' => 10,
                            'media_buttons' => false,
                            'teeny' => false,
                            'quicktags' => true
                        ];

                        // use wp_editor instead
                        wp_editor($content, $editor_id, $settings);
                        break;
                    default:
                        $dbValue = $this->force_to_string($dbValue);
                        $val = esc_textarea($dbValue ?? '');
                        echo "<textarea id='for_{$this->renderId}' name='{$fullName}'" . $this->renderAttributes() . ">{$val}</textarea>";
                        break;
                }
                break;

            // 🧩 INPUT
            case 'input':

                switch ($this->type ?? '') {
                    case 'text':
                    case 'number':
                    case 'email':
                    case 'password':
                    case 'date':
                    case 'time':
                    case 'color':
                    case 'button':
                    case 'file':
                    case 'url':
                        $dbValue = $this->force_to_string($dbValue);
                        $val = esc_attr($dbValue ?? '');
                        echo "<input id='for_{$this->renderId}' type='{$this->type}' name='{$fullName}' value='{$val}'" . $this->renderAttributes() . ">";
                        break;

                    // 🔘 RADIO
                    case 'radio':
                        $options = $this->options ?? [];
                        $value = $dbValue ?? '';
                        foreach ($options as $optValue => $optLabel) {
                            $checked = ($value == $optValue) ? 'checked' : '';
                            echo "<label>";
                            echo "<input type='radio' name='{$fullName}' value='{$optValue}' {$checked}" . $this->renderAttributes() . ">";
                            echo "<span>{$optLabel}</span>";
                            echo "</label>";
                        }
                        break;

                    case 'checkbox':
                        // Nếu chưa có options, mặc định checkbox đơn với value = 'on' và label 'On'
                        $options = $this->options ?: ['on' => 'On'];
                        $dbValues = is_array($dbValue) ? $dbValue : [$dbValue];

                        // Nếu nhiều option, thêm [] vào name
                        $isMultiple = count($options) > 1;

                        // Thêm hidden input để luôn có dữ liệu gửi đi
                        echo "<input type='hidden' name='{$fullName}" . ($isMultiple ? '[]' : '') . "' value=''>";

                        foreach ($options as $optValue => $optLabel) {
                            $checked = in_array($optValue, $dbValues) ? 'checked' : '';
                            $labelText = is_string($optLabel) ? $optLabel : $optValue;
                            echo "<label>";
                            echo "<input type='checkbox' name='{$fullName}" . ($isMultiple ? '[]' : '') . "' value='{$optValue}' {$checked}" . $this->renderAttributes() . ">";
                            echo " <span>{$labelText}</span>";
                            echo "</label>";
                        }
                        break;

                    case 'hidden':
                        $val = esc_attr($dbValue ?? '');
                        echo "<input type='hidden' name='{$fullName}' value='{$val}'>";
                        break;

                    case 'wp_media':
                    case 'wp_multiple_media':

                        // Detect type
                        $isMultiple = ($this->type === 'wp_multiple_media');
                        $wrapperType = $isMultiple ? 'multiple' : 'single';

                        echo "<div class='wpdh-media-wrapper' data-type='{$wrapperType}'>";

                        //
                        echo "<input class='output' type='hidden' id='for_{$this->renderId}' name='{$fullName}' value='{$dbValue}'>";

                        //
                        $ids = maybe_unserialize($dbValue);
                        $ids = (array)$ids;

                        //
                        echo "<div class='wpdh-media-preview'>";
                        foreach ($ids as $id) {
                            echo "<div class='wpdh-media-item'>";
                            if ($id) {
                                $url = wp_get_attachment_image_url($id, 'thumbnail');
                                if (!$url) {
                                    $url = get_site_url() . '/wp-includes/images/media/default.svg';
                                }
                                echo "<img src='{$url}' data-id='{$id}' class='wpdh-media-thumb'>";
                            }
                            echo "</div>";
                        }

                        // ------------------------------------
                        echo "</div> <!-- .wpdh-media-preview -->";

                        //
                        echo "<button type='button' class='button wpdh-btn-media' data-target='{$this->renderId}'>" . __('Select') . "</button>";
                        echo "<button type='button' class='button wpdh-btn-remove-media' data-target='{$this->renderId}'>" . __('Remove') . "</button>";

                        //
                        echo "</div> <!-- wpdh-media-wrapper -->";
                        break;

                    default:
                        $type = esc_html($this->type ?: '(undefined)');
                        echo "<div class='wpdh-field-unknown'>⚠️ Unknown field type <code>{$type}</code>.</div>";
                        break;
                }
                break;

            // 🪫 FALLBACK (nếu chưa hỗ trợ kind này)
            default:
                $kind = esc_html($this->kind ?: '(undefined)');
                echo "<div class='wpdh-field-unknown'>⚠️ Unknown field kind <code>{$kind}</code>.</div>";
                break;
        }

        // copy 
        if ($this->copyButton) {
            if (
                !in_array($this->type ?? '', ['checkbox', 'radio', 'hidden', 'file', 'button', 'wp_media', 'wp_multiple_media'])
            ) {
                echo "<div class='wpdh-field-copy-button'>";
                echo "<button class='button button-link button-small' type='button'>" . __('Copy') . "</button>";
                echo "</div>"; // .wpdh-control
            }
        }

        echo "</div>"; // .wpdh-field-control

        // note
        if (!empty($this->notes)) {
            echo "<div class='wpdh-field-notes'>";
            foreach ((array)$this->notes as $key => $note) {
                if (!$note) {
                    continue;
                }
                // add star 
                $star_html = '';
                for ($i = 0; $i < ($key + 1); $i++) {
                    $star_html .= '*';
                }
                echo "<div class='wpdh-field-note'><small>$star_html" . __('Notes') . ": {$note}</small></div>";
            }
            echo "</div>";
        }

        echo "</div>"; // .wpdh-field
        return ob_get_clean();
    }

    private function showLabel() {
        ob_start();
        echo "<div class='wpdh-field-label'>";
        if (in_array($this->type, ['radio', 'checkbox'])) {
            $for = '';
            $tag = 'span';
        } else {
            $for = "for='for_{$this->renderId}'";
            $tag = 'label';
        }
        echo "<{$tag} {$for}>{$this->label}</{$tag}>";
        echo "</div>"; // .wpdh-field-la
        return ob_get_clean();
    }

    private function force_to_string(mixed $value): string {
        // --- Handle null
        if (is_null($value)) {
            return 'null';
        }

        // --- Handle boolean
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        // --- Handle scalar (int, float, string)
        if (is_scalar($value)) {
            return (string)$value;
        }

        // --- Handle array or object
        if (is_array($value) || is_object($value)) {
            // use serialize for stable export
            return serialize($value);
        }

        // --- Handle resource
        if (is_resource($value)) {
            return sprintf('resource(%s)', get_resource_type($value));
        }

        // --- Fallback for unknown types
        return print_r($value, true);
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

    public function validate_field_name($name) {
        $return = [
            'status' => true,
            'message' => ''
        ];

        // some kind not need validate
        if (!in_array($this->kind, ['input', 'select', 'textarea'])) {
            return $return;
        }

        // 
        if (in_array($this->type, ['submit', 'button', 'reset'])) {
            return $return;
        }

        // 
        $excluded = [
            [
                'name' => 'name',
                'message' => "Your field name: <strong>{$name}</strong> is conflict with html attribute <code>name</code>."
            ],
            [
                'name' => 'field_name',
                'message' => "Your field name: <strong>{$name}</strong> is conflict with wpdatabase helper js"
            ],
            [
                'name' => '',
                'message' => "Your field name: <strong>{$name}</strong> can not empty name"
            ]
        ];

        foreach ($excluded as $item) {
            if ($name == $item['name']) {
                $return['status'] = false;
                $return['message'] = $item['message'];
                break;
            }
        }

        return $return;
    }
}
