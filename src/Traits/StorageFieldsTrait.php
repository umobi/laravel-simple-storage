<?php

namespace Umobi\LaravelSimpleStorage\Traits;

use Umobi\LaravelSimpleStorage\Contracts\StorageFieldsContract;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use League\Flysystem\Util\MimeType;
use function GuzzleHttp\Psr7\mimetype_from_extension;

trait StorageFieldsTrait
{
    private array $detaultOptions = [
        'path' => '',
        'type' => 'image',
        'extension' => 'jpg',
        'size' => [1024, 1024],
        'default' => '',
        'disk' => 'local',
        'quality' => 90,
        'allow_url_fopen' => true
    ];

    public function setAttribute($key, $value)
    {
        if (isset($this->files) && isset($this->files[$key]) && !is_null($value)) {
            $this->setInternalFile($key, $value, $this->files[$key]);
        } else {
            /** @noinspection PhpUndefinedClassInspection */
            parent::setAttribute($key, $value);
        }
    }

    protected function setInternalFile($key, $value, $options)
    {
        if ($this instanceof StorageFieldsContract) {
            $options = array_merge($this->detaultOptions, $options);

            try {
                /** @var string $type */
                /** @var string $extension */
                /** @var string $path */
                /** @var array $size */
                /** @var string $disk */
                /** @var int $quality */
                /** @var boolean $allow_url_fopen */
                extract($options);
                $oldValue = @$this->attributes[$key];
                if(@strpos($value, $oldValue) > 0) {
                    return;
                }

                if (($array = @json_decode($oldValue, true)) && is_array($array) && @strpos($value, $array['url']) > 0) {
                    return;
                }

                $contents = null;
                $mimeType = null;
                $fileSize = 0;
                $fileExtension = null;

                $isImage = false;
                if ($value instanceof File ||
                    $value instanceof UploadedFile) {
                    $fileExtension = Str::lower($value->getClientOriginalExtension());
                    $mimeType = $value->getMimeType();
                    $fileSize = $value->getSize();
                    $isImage = in_array($fileExtension, ['png', 'jpg', 'gif', 'webp']);
                    $filename = str_replace($fileExtension, "", Str::slug(Str::lower($value->getClientOriginalName()))) . "." . $fileExtension;
                }

                $isUrl = false;
                if (is_string($value)) {
                    $isUrl = strpos($value, 'http') === 0;
                }

                if (($type == 'image' || $isImage) && !($isUrl && !$allow_url_fopen) && class_exists(ImageManager::class)) {
                    $manager = new ImageManager(array('driver' => env('IMAGE_DRIVER', 'gd')));
                    $image = $manager->make($value);
                    if ($size[0] > 0 && $size[1] > 0) {
                        $image->fit($size[0], $size[1]);
                    } elseif ($size[0] <= 0 && $size[1] > 0) {
                        $image->heighten($size[1]);
                    } elseif ($size[1] <= 0 && $size[0] > 0) {
                        $image->widen($size[0]);
                    }

                    if (!$extension && isset($fileExtension)) {
                        $extension = Str::lower($fileExtension);
                    }

                    $contents = $image->stream($extension, $quality ?? 90)->__toString();
                    $mimeType = mimetype_from_extension($extension) ?? MimeType::detectByContent($contents);
                } else {
                    $extension = strtolower($fileExtension);
                    $contents = $value;
                }

                $filename = Str::random(40) . "." . $extension;

                if (isset($contents)) {
                    /** @var FilesystemAdapter $storage */
                    $storage = Storage::disk($disk);
                    $resultPath = $contents;

                    $e = null;
                    if ($contents instanceof File ||
                            $contents instanceof UploadedFile) {

                        if (isset($filename)) {
                            $resultPath = $storage->putFileAs($path, $contents, $filename, [
                                'ContentType' => $mimeType,
                                'ACL' => 'public-read'
                            ]);
                        } else {
                            $resultPath = $storage->putFile($path, $contents, [
                                'ContentType' => $mimeType,
                                'ACL' => 'public-read'
                            ]);
                        }

                    } else if (isset($filename)) {
                        $resultPath = $path . "/" . $filename;
                        $e = $storage->put($resultPath, $contents, [
                            'ContentType' => $mimeType,
                            'ACL' => 'public-read'
                        ]);
                    }

                    $data = [
                        'disk' => $disk,
                        'url' => $resultPath,
                        'file_size' => $fileSize,
                        'mime_type' => $mimeType
                    ];

                    parent::setAttribute($key, json_encode($data));

                    if (($array = json_decode($oldValue, true)) && is_array($array)) {
                        Storage::disk($array['disk'])
                            ->delete($array['url']);
                    } elseif ($oldValue) {
                        Storage::disk($disk)
                            ->delete($oldValue);
                    }
                }

            } catch (\Exception $e) {
                throw $e;
            }
        } else {
            throw new \Exception("This Class must be implements [StorageFieldsContract]");
        }
    }

    public function getAttributeValue($key)
    {
        if (isset($this->files) && isset($this->files[$key])) {

            return $this->getFileAttributeValue($key, $this->files[$key]);

        } else {
            /** @noinspection PhpUndefinedMethodInspection */
            /** @noinspection PhpUndefinedClassInspection */
            return parent::getAttributeValue($key);
        }
    }

    public function getFileAttributeValue($key, $options) {

        $options = array_merge($this->detaultOptions, $options);

        /** @var string $type */
        /** @var string $extension */
        /** @var string $path */
        /** @var array $size */
        /** @var string $disk */
        extract($options);

        $value = $this->getAttributeFromArray($key);


        if (empty($value)) {
            if (!empty($default) && !empty($path)) {
                $value = $path . "/" . $default;
            } else {
                return null;
            }
        } elseif (is_string($value) && strpos($value, 'http') === 0) {
            return $value;
        }

        if ($value && ($array = @json_decode($value, true)) && is_array($array)) {
            return Storage::disk($array['disk'])
                ->url($array['url']);
        }

        return Storage::disk($disk)
            ->url($value);
    }

    public function toArray()
    {
        $original = parent::toArray();
        if (isset($this->files) && is_array($this->files)) {
            array_map(function($key) use (&$original) {
                $original[$key] = $this->{$key};
            }, array_keys($this->files));
        }

        return $original;
    }
}
