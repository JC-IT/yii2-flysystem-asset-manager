<?php
declare(strict_types=1);

namespace JCIT\components;

use creocoder\flysystem\Filesystem;
use creocoder\flysystem\LocalFilesystem;
use League\Flysystem\FileNotFoundException;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\helpers\FileHelper;
use yii\web\AssetManager as YiiAssetManager;

class AssetManager extends YiiAssetManager
{
    public $basePath = '/';
    public Filesystem|string|array $filesystem = [
        'class' => LocalFilesystem::class,
        'path' => '@webroot/assets',
    ];

    public function init(): void
    {
        $this->filesystem = Instance::ensure(
            $this->filesystem,
            Filesystem::class
        );

        if ($this->linkAssets) {
            throw new InvalidConfigException('Linking assets is not supported.');
        }

        parent::init();
    }

    protected function publishFile($src)
    {
        \Yii::beginProfile('Publish file :' . $src);

        $dir = $this->hash($src);
        $fileName = basename($src);
        $dstDir = $this->basePath . DIRECTORY_SEPARATOR . $dir;
        $dstFile = $dstDir . DIRECTORY_SEPARATOR . $fileName;

        if (!$this->filesystem->has($dstDir)) {
            $this->filesystem->createDir($dstDir);
        }

        try {
            if ($this->filesystem->getTimestamp($dstFile) < @filemtime($src)) {
                $this->filesystem->updateStream($dstFile, fopen($src, 'r'));
            }
        } catch (FileNotFoundException $e) {
            $this->filesystem->writeStream($dstFile, fopen($src, 'r'));
        }

        if ($this->appendTimestamp && ($timestamp = $this->filesystem->getTimestamp($dstFile)) > 0) {
            $fileName = $fileName . "?v=$timestamp";
        }

        \Yii::endProfile('Publish file :' . $src);

        return [$dstFile, $this->baseUrl . "/$dir/$fileName"];
    }

    protected function publishDirectory($src, $options)
    {
        \Yii::beginProfile('Publish dir :' . $src);
        $dir = $this->hash($src);
        $dstDir = $this->basePath . DIRECTORY_SEPARATOR . $dir;

        $currentLength = strlen($src);

        if (!empty($options['forceCopy']) || ($this->forceCopy && !isset($options['forceCopy'])) || !$this->filesystem->has($dstDir)) {
            if ($this->filesystem->has($dstDir)) {
                $this->filesystem->deleteDir($dstDir);
            }

            $folders = FileHelper::findDirectories($src);
            foreach ($folders as $folder) {
                $folder = substr($folder, $currentLength);
                $this->filesystem->createDir($dstDir . $folder);
            }

            $files = FileHelper::findFiles($src);
            foreach ($files as $file) {
                $dstFile = substr($file, $currentLength);
                $this->filesystem->writeStream($dstDir . $dstFile, fopen($file, 'r'));
            }
        }
        \Yii::endProfile('Publish dir :' . $src);

        return [$dstDir, $this->baseUrl . '/' . $dir];
    }
}
