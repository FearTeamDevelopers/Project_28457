<?php

namespace THCFrame\Filesystem;

use THCFrame\Core\Base as Base;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Filesystem\Exception as Exception;
use THCFrame\Core\StringMethods as StringMethods;
use THCFrame\Filesystem\FileManager as FileManager;
use THCFrame\Filesystem\Image as Image;

/**
 * 
 */
class ImageManager extends Base
{

    /**
     * @read
     */
    protected $_fileManager;

    /**
     * @read
     */
    protected $_pathToImages;

    /**
     * @read
     */
    protected $_pathToThumbs;

    /**
     * @readwrite
     */
    protected $_maxImageHeight = 1080;

    /**
     * @readwrite
     */
    protected $_maxImageWidth = 1920;

    /**
     * @readwrite
     */
    protected $_thumbWidth;

    /**
     * @readwrite
     */
    protected $_thumbHeight;

    /**
     * @readwrite
     */
    protected $_thumbResizeBy;

    /**
     * @read
     */
    protected $_imageExtensions = array('gif', 'jpg', 'png', 'jpeg');

    /**
     * 
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
        $configuration = Registry::get('config');

        if (!empty($configuration->files)) {
            $this->_pathToImages = trim($configuration->files->pathToImages, '/');
            $this->_pathToThumbs = trim($configuration->files->pathToThumbs, '/');
        } else {
            throw new \Exception('Error in configuration file');
        }

        $this->_fileManager = new FileManager();
    }

    /**
     * 
     * @return type
     */
    private function getPathToImages()
    {
        if (is_dir('/' . $this->_pathToImages)) {
            return '/' . $this->_pathToImages;
        } elseif (is_dir('./' . $this->_pathToImages)) {
            return './' . $this->_pathToImages;
        } elseif (is_dir(APP_PATH . '/' . $this->_pathToImages)) {
            return APP_PATH . '/' . $this->_pathToImages;
        }
    }

    /**
     * 
     * @return type
     */
    private function getPathToThumbs()
    {
        if (is_dir('/' . $this->_pathToThumbs)) {
            return '/' . $this->_pathToThumbs;
        } elseif (is_dir('./' . $this->_pathToThumbs)) {
            return './' . $this->_pathToThumbs;
        } elseif (is_dir(APP_PATH . '/' . $this->_pathToThumbs)) {
            return APP_PATH . '/' . $this->_pathToThumbs;
        }
    }

    /**
     * 
     * @param type $postField
     * @param type $namePrefix
     */
    public function upload($postField, $uploadto, $namePrefix = '')
    {
        $path = $this->getPathToImages() . '/' . $uploadto . '/';
        $pathToThumbs = $this->getPathToThumbs() . '/' . $uploadto . '/';

        if (!is_dir($path)) {
            $this->fileManager->mkdir($path, 0755);
        }

        if (!is_dir($pathToThumbs)) {
            $this->fileManager->mkdir($pathToThumbs, 0755);
        }

        if (is_array($_FILES[$postField]['tmp_name'])) {
            $returnArray = array('photos' => array(), 'errors' => array());

            foreach ($_FILES[$postField]['name'] as $i => $name) {
                if (is_uploaded_file($_FILES[$postField]['tmp_name'][$i])) {
                    $size = $_FILES[$postField]['size'][$i];
                    $extension = pathinfo($_FILES[$postField]['name'][$i], PATHINFO_EXTENSION);
                    $filename = StringMethods::removeDiacriticalMarks(
                                    str_replace(' ', '_', pathinfo($_FILES[$postField]['name'][$i], PATHINFO_FILENAME)
                                    )
                    );

                    if ($size > 5000000) {
                        $returnArray['errors'][] = sprintf('Your file %s size exceeds the maximum size limit', $filename);
                        continue;
                    } else {
                        if (!in_array($extension, $this->_imageExtensions)) {
                            $returnArray['errors'][] = sprintf('%s Images can only be with jpg, jpeg, png or gif extension', $filename);
                            continue;
                        } else {
                            if (strlen($filename) > 50) {
                                $filename = substr($filename, 0, 50);
                            }

                            $imageName = $filename . '.' . $extension;
                            $thumbName = $filename . '_thumb.' . $extension;
                            $imageLocName = $path . $namePrefix . $imageName;
                            $thumbLocName = $pathToThumbs . $namePrefix . $thumbName;

                            if (file_exists($imageLocName)) {
                                $this->backup($imageLocName);
                            }

                            if (file_exists($thumbLocName)) {
                                $this->backup($thumbLocName);
                            }

                            $copy = move_uploaded_file($_FILES[$postField]['tmp_name'][$i], $imageLocName);

                            if (!$copy) {
                                $returnArray['errors'][] = sprintf('Error while uploading image %s. Try again.', $filename);
                                continue;
                            } else {
                                $img = new Image($imageLocName);

                                if ($img->getWidth() > $this->getMaxImageWidth() || $img->getHeight() > $this->getMaxImageHeight()) {
                                    $img->bestFit($this->getMaxImageWidth(), $this->getMaxImageHeight())->save();
                                }

                                $returnArray['photos'][$i]['photo'] = $img->getDataForDb();

                                switch ($this->thumbResizeBy) {
                                    case 'height':
                                        $img->resizeToHeight($this->thumbHeight)->save($thumbLocName);
                                        $returnArray['photos'][$i]['thumb'] = $img->getDataForDb();
                                        break;
                                    case 'width':
                                        $img->resizeToWidth($this->thumbWidth)->save($thumbLocName);
                                        $returnArray['photos'][$i]['thumb'] = $img->getDataForDb();
                                        break;
                                    default:
                                        $img->thumbnail($this->thumbWidth, $this->thumbHeight)->save($thumbLocName);
                                        $returnArray['photos'][$i]['thumb'] = $img->getDataForDb();
                                        break;
                                }
                                unset($img);
                            }
                        }
                    }
                } else {
                    $i += 1;
                    $returnArray['errors'][] = sprintf("Source %s cannot be empty", $i);
                    continue;
                }
            }

            return $returnArray;
        } else {
            if (is_uploaded_file($_FILES[$postField]['tmp_name'])) {
                $size = $_FILES[$postField]['size'];
                $extension = pathinfo($_FILES[$postField]['name'], PATHINFO_EXTENSION);
                $filename = StringMethods::removeDiacriticalMarks(
                                str_replace(' ', '_', pathinfo($_FILES[$postField]['name'], PATHINFO_FILENAME)
                                )
                );

                if ($size > 5000000) {
                    throw new Exception(sprintf('Your file %s size exceeds the maximum size limit', $filename));
                } else {
                    if (!in_array($extension, $this->_imageExtensions)) {
                        throw new Exception(sprintf('%s Images can only be with jpg, jpeg, png or gif extension', $filename));
                    } else {
                        if (strlen($filename) > 50) {
                            $filename = substr($filename, 0, 50);
                        }

                        $imageName = $filename . '.' . $extension;
                        $thumbName = $filename . '_thumb.' . $extension;
                        $imageLocName = $path . $namePrefix . $imageName;
                        $thumbLocName = $pathToThumbs . $namePrefix . $thumbName;

                        if (file_exists($imageLocName)) {
                            $this->backup($imageLocName);
                        }

                        if (file_exists($thumbLocName)) {
                            $this->backup($thumbLocName);
                        }

                        $copy = move_uploaded_file($_FILES[$postField]['tmp_name'], $imageLocName);

                        if (!$copy) {
                            throw new Exception(sprintf('Error while uploading image %s. Try again.', $filename));
                        } else {
                            $img = new Image($imageLocName);

                            if ($img->getWidth() > $this->getMaxImageWidth() || $img->getHeight() > $this->getMaxImageHeight()) {
                                $img->bestFit($this->getMaxImageWidth(), $this->getMaxImageHeight())->save();
                            }

                            $returnArray['photo'] = $img->getDataForDb();

                            switch ($this->thumbResizeBy) {
                                case 'height':
                                    $img->resizeToHeight($this->thumbHeight)->save($thumbLocName);
                                    $returnArray['thumb'] = $img->getDataForDb();
                                    break;
                                case 'width':
                                    $img->resizeToWidth($this->thumbWidth)->save($thumbLocName);
                                    $returnArray['thumb'] = $img->getDataForDb();
                                    break;
                                default:
                                    $img->thumbnail($this->thumbWidth, $this->thumbHeight)->save($thumbLocName);
                                    $returnArray['thumb'] = $img->getDataForDb();
                                    break;
                            }
                            unset($img);
                            return $returnArray;
                        }
                    }
                }
            } else {
                throw new Exception('Source cannot be empty');
            }
        }
    }

    /**
     * 
     * @param type $postField
     * @param type $namePrefix
     */
    public function uploadWithoutThumb($postField, $uploadto, $namePrefix = '')
    {
        $path = $this->getPathToImages() . '/' . $uploadto . '/';

        if (!is_dir($path)) {
            $this->fileManager->mkdir($path, 0755);
        }

        if (is_array($_FILES[$postField]['tmp_name'])) {
            $returnArray = array('photos' => array(), 'errors' => array());

            foreach ($_FILES[$postField]['name'] as $i => $name) {
                if (is_uploaded_file($_FILES[$postField]['tmp_name'][$i])) {
                    $size = $_FILES[$postField]['size'][$i];
                    $extension = pathinfo($_FILES[$postField]['name'][$i], PATHINFO_EXTENSION);
                    $filename = StringMethods::removeDiacriticalMarks(
                                    str_replace(' ', '_', pathinfo($_FILES[$postField]['name'][$i], PATHINFO_FILENAME)
                                    )
                    );

                    if ($size > 5000000) {
                        $returnArray['errors'][] = sprintf('Your file %s size exceeds the maximum size limit', $filename);
                        continue;
                    } else {
                        if (!in_array($extension, $this->_imageExtensions)) {
                            $returnArray['errors'][] = sprintf('%s Images can only be with jpg, jpeg, png or gif extension', $filename);
                            continue;
                        } else {
                            if (strlen($filename) > 50) {
                                $filename = substr($filename, 0, 50);
                            }

                            $imageName = $filename . '.' . $extension;
                            $imageLocName = $path . $namePrefix . $imageName;

                            if (file_exists($imageLocName)) {
                                $this->backup($imageLocName);
                            }

                            $copy = move_uploaded_file($_FILES[$postField]['tmp_name'][$i], $imageLocName);

                            if (!$copy) {
                                $returnArray['errors'][] = sprintf('Error while uploading image %s. Try again.', $filename);
                                continue;
                            } else {
                                $img = new Image($imageLocName);
                                $returnArray['photos'][$i]['photo'] = $img->getDataForDb();

                                unset($img);
                            }
                        }
                    }
                } else {
                    $i += 1;
                    $returnArray['errors'][] = sprintf("Source %s cannot be empty", $i);
                    continue;
                }
            }

            return $returnArray;
        } else {
            if (is_uploaded_file($_FILES[$postField]['tmp_name'])) {
                $size = $_FILES[$postField]['size'];
                $extension = pathinfo($_FILES[$postField]['name'], PATHINFO_EXTENSION);
                $filename = StringMethods::removeDiacriticalMarks(
                                str_replace(' ', '_', pathinfo($_FILES[$postField]['name'], PATHINFO_FILENAME)
                                )
                );

                if ($size > 5000000) {
                    throw new Exception(sprintf('Your file %s size exceeds the maximum size limit', $filename));
                } else {
                    if (!in_array($extension, $this->_imageExtensions)) {
                        throw new Exception(sprintf('%s Images can only be with jpg, jpeg, png or gif extension', $filename));
                    } else {
                        if (strlen($filename) > 50) {
                            $filename = substr($filename, 0, 50);
                        }

                        $imageName = $filename . '.' . $extension;
                        $imageLocName = $path . $namePrefix . $imageName;

                        if (file_exists($imageLocName)) {
                            $this->backup($imageLocName);
                        }

                        $copy = move_uploaded_file($_FILES[$postField]['tmp_name'], $imageLocName);

                        if (!$copy) {
                            throw new Exception(sprintf('Error while uploading image %s. Try again.', $filename));
                        } else {
                            $img = new Image($imageLocName);
                            $returnArray['photo'] = $img->getDataForDb();

                            unset($img);
                            return $returnArray;
                        }
                    }
                }
            } else {
                throw new Exception('Source cannot be empty');
            }
        }
    }

    /**
     * 
     * @param type $file
     * @return type
     */
    public function backup($file)
    {
        $ext = $this->fileManager->getExtension($file);
        $filename = $this->fileManager->getFileName($file);
        $newFile = dirname($file) . '/' . $filename . '_backup.' . $ext;

        if (strlen($filename) > 50) {
            $newFile = dirname($file) . '/' . substr($filename, 0, 50) . '_backup.' . $ext;
        }

        $this->fileManager->rename($file, $newFile);
        return;
    }

}
