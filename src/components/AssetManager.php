<?php
declare(strict_types=1);

namespace JCIT\components;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\helpers\FileHelper;
use yii\web\AssetManager as YiiAssetManager;

class AssetManager extends YiiAssetManager
{
    public $basePath = '/';
    public Filesystem|string|array $filesystem;

    public function init(): void
    {
        if (!isset($this->filesystem)) {
            $this->filesystem = new Filesystem(new LocalFilesystemAdapter(\Yii::getAlias('@webroot/assets')));
        }

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
            $this->filesystem->createDirectory($dstDir, []);
        }

        if (
            !$this->filesystem->fileExists($dstFile)
            || $this->filesystem->lastModified($dstFile) < @filemtime($src)
        ) {
            $this->filesystem->writeStream($dstFile, fopen($src, 'r'));
        }

        if ($this->appendTimestamp && ($timestamp = $this->filesystem->lastModified($dstFile)) > 0) {
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

        if (!empty($options['forceCopy']) || ($this->forceCopy && !isset($options['forceCopy'])) || !$this->filesystem->directoryExists($dstDir)) {
            if ($this->filesystem->directoryExists($dstDir)) {
                $this->filesystem->deleteDirectory($dstDir);
            }

            $folders = FileHelper::findDirectories($src);
            foreach ($folders as $folder) {
                $folder = substr($folder, $currentLength);
                $this->filesystem->createDirectory($dstDir . $folder);
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
