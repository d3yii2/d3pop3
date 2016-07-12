# d3pop3 


## Features

* read emails from diferent pop3 and attach to model records


## Installation
```bash
php composer.phar require d3yii2/d3pop3 dev-master
```

 * add to config/web.php
```php
    'modules' => [
        'D3Pop3' => [
            'class' => 'd3yii2\d3pop3\d3pop3',
            'ConfigEmailContainerData' => [
                [
                    'model' => 'app\models\test',
                    'host' => 'pop.gmail.com',
                    'user' => '[...user..]@gmail.com',
                    'password' => '.........',
                    'ssl' => 'SSL',
                ],
            ],
            'EmailContainers' => [
                'd3yii2\d3pop3\components\ConfigEmailContainer',
            ]
        ],
    ],
```

* migration configuration. Add to console parameters migration path
```php
    'yii.migrations' => [
        '@vendor/d3yii2/d3pop3/migrations',
    ],
```

* do migration
```bash
yii migrate
```

## Usage
### By Config
In configuration under 'ConfigEmailContainerData' set:
* POP3 connection data, 
* model with namespace for attaching emails
* model field name, where search email to field value for ataching email