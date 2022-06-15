# Asset manager that uses flysystem for Yii2

This extension provides an asset manager that uses [flysystem](https://flysystem.thephpleague.com/v1/docs/) for Yii2.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
$ composer require jc-it/yii2-flysystem-asset-manager
```

or add

```
"jc-it/yii2-flysystem-asset-manager": "^<latest version>"
```

to the `require` section of your `composer.json` file.

## Configuration
```php
...
'components' => [
    'assetFilesystem' => function() {
        return new \League\Flysystem\Filesystem(new \League\Flysystem\Local\LocalFilesystemAdapter(\Yii::getAlias('@webroot/assets')));
    },
    'assetManager' => [
        'class' => \JCIT\components\AssetManager::class,
        'filesystem' => 'assetFilesystem',
    ],
],
```

## TODO
- Add tests

## Credits
- [Joey Claessen](https://github.com/joester89)
