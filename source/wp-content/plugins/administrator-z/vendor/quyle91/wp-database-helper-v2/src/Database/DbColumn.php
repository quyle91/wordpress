<?php

namespace WpDatabaseHelperV2\Database;

class DbColumn {
    protected string $id;
    public function getId() {
        return $this->id;
    }

    public function __construct() {
        $this->id = 'id_' . rand();
        $this->version = $this->getVersion();
    }

    private $version;
    private function getVersion() {
        // __DIR__ = .../src/Database
        $composerFile = dirname(__DIR__, 2) . '/composer.json'; // đi lên 2 cấp để tới root của package

        if (file_exists($composerFile)) {
            $composerData = json_decode(file_get_contents($composerFile), true);
            return $composerData['version'] ?? '0.0.0';
        }

        return '0.0.0';
    }

    public static function make(): self {
        return new self();
    }

    protected string $name;
    public function name(string $name): self {
        $this->name = $name;

        // Lưu vào registry ngay khi name được set
        self::$registry[$this->name] = $this;

        return $this;
    }
    public function getName(): string {
        return $this->name;
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

    protected string $type = 'VARCHAR(255)';
    public function type(string $type): self {
        $this->type = strtoupper($type);
        return $this;
    }
    public function getType(): string {
        return $this->type;
    }

    protected bool $notNull = false;
    public function notNull(bool $state = true): self {
        $this->notNull = $state;
        return $this;
    }
    public function nullable(bool $state = true): self {
        $this->notNull = !$state;
        return $this;
    }
    public function isNotNull(): bool {
        return $this->notNull;
    }

    protected bool $autoIncrement = false;
    public function autoIncrement(bool $state = true): self {
        $this->autoIncrement = $state;
        return $this;
    }
    public function isAutoIncrement(): bool {
        return $this->autoIncrement;
    }

    protected bool $isTimestamp = false;
    public function timestamp(bool $enable = true): self {
        $this->isTimestamp = $enable;
        return $this;
    }
    public function isTimestamp(): bool {
        return $this->isTimestamp;
    }

    protected bool $primary = false;
    public function primary(bool $state = true): self {
        $this->primary = $state;
        return $this;
    }
    public function isPrimary(): bool {
        return $this->primary;
    }

    protected bool $unsigned = false;
    public function unsigned(bool $state = true): self {
        $this->unsigned = $state;
        return $this;
    }
    public function isUnsigned(): bool {
        return $this->unsigned;
    }

    protected bool $onUpdate = false;
    public function onUpdateCurrentTimestamp(bool $enable = true): self {
        $this->onUpdate = $enable;
        return $this;
    }
    public function isOnUpdateCurrentTimestamp(): bool {
        return $this->onUpdate;
    }

    protected ?string $default = null;
    public function default(string $value): self {
        $this->default = $value;
        return $this;
    }
    public function getDefault(): ?string {
        return $this->default;
    }

    protected $parts = [];
    public function build(): self {

        // Reset SQL parts before building
        $this->parts = [];

        $this->buildNameAndType();
        $this->appendUnsigned();
        $this->appendNotNull();
        $this->appendDefault();
        $this->appendAutoIncrement();
        $this->appendOnUpdate();

        return $this;
    }

    public function getSql(): string {
        return implode(' ', $this->parts);
    }

    private function buildNameAndType(): self {
        $this->parts[] = "`{$this->name}` {$this->type}";
        return $this;
    }

    private function appendUnsigned():self {
        if ($this->unsigned && preg_match('/(INT|FLOAT|DOUBLE|DECIMAL)/i', $this->type)) {
            $this->parts[] = "UNSIGNED";
        }
        return $this;
    }

    private function appendNotNull():self {
        if ($this->notNull) {
            $this->parts[] = "NOT NULL";
        }
        return $this;
    }

    private function appendDefault(): self {
        // Skip if auto increment
        if ($this->autoIncrement) {
            return $this;
        }

        if ($this->default === null) {
            return $this;
        }

        $default = strtoupper($this->default);

        // CURRENT_TIMESTAMP is valid only for DATETIME / TIMESTAMP
        if ($default === 'CURRENT_TIMESTAMP') {

            if (!$this->isDateTimeType()) {
                // Invalid default for this type → skip silently
                return $this;
            }

            $this->parts[] = 'DEFAULT CURRENT_TIMESTAMP';
            return $this;
        }

        // $escaped = addslashes($this->default);
        $escaped = str_replace("'", "''", $this->default);
        $this->parts[] = "DEFAULT '{$escaped}'";

        return $this;
    }

    private function isDateTimeType(): bool {
        return in_array($this->type, ['TIMESTAMP', 'DATETIME'], true);
    }

    private function appendAutoIncrement(): self {
        if ($this->autoIncrement) {
            $this->parts[] = "AUTO_INCREMENT";
        }
        return $this;
    }

    private function appendOnUpdate(): self {
        if ($this->onUpdate && $this->isDateTimeType()) {
            $this->parts[] = 'ON UPDATE CURRENT_TIMESTAMP';
        }

        return $this;
    }
}
