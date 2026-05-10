0. Install 
composer require quyle91/wp-database-helper-v2:dev-main
composer update quyle91/wp-database-helper-v2 --prefer-source --no-cache

1. Folder structure
wp-database-helper-v2/
├─ composer.json
├─ README.md
├─ assets/
│  ├─ css/
│  │  └─ repeater.css
│  │  └─ meta.css
│  │  └─ field.css
│  │  └─ dbtable.css
│  ├─ js/
│  │  └─ repeater.js
│  │  └─ meta.js
│  │  └─ field.js
│  │  └─ dbtable.js
├─ src/
│  ├─ Ajax/
│  │  └─ HandleAppendRepeater.php
│  ├─ Database/
│  │  └─ DbColumn.php
│  │  └─ DbTable.php
│  ├─ Example/
│  │  └─ DbBuilder.php
│  │  └─ MetaBuilder.php
│  ├─ Fields/
│  │  ├─ WpField.php
│  │  └─ WpRepeater.php
│  └─ Helpers/
│  │  └─ Arr.php
│  ├─ Meta/
│  │  └─ WpMeta.php
│  ├─ Services/
│  │  └─ Renderer.php
│  │  └─ Assets.php
│  └─ Bootstrap.php          # init composer bindings, service container (simple)
├─ views/
│  └─ fields/
│  │  └─ field-text.php
│  │  └─ field-repeater.php
│  └─ database/
└─ │─ └─ table-view.php


2. Use example. For more details, please see src/Bootstrap.php
```php
add_action('init', function () {
    $bootstrap = new \WpDatabaseHelperV2\Bootstrap();
    $bootstrap->init();
});
```

xxx
