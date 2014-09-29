<?php

namespace THCFrame\Filesystem;

use THCFrame\Core\Base;
use THCFrame\Filesystem\Exception;
use THCFrame\Registry\Registry;
use THCFrame\Filesystem\Image;
use THCFrame\Filesystem\File;
use THCFrame\Core\StringMethods;

/**
 * 
 */
class FileManager extends Base
{

    /**
     * @readwrite
     */
    protected $_pathToDocs;

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
     * @read
     */
    protected $_fileExtensions = array('rtf', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'pdf', 'ppt', 'pptx', 'zip', 'rar');

    public function __construct($options = array())
    {
        parent::__construct($options);

        $configuration = Registry::get('configuration');

        if (!empty($configuration->files)) {
            $this->_pathToDocs = trim($configuration->files->pathToDocuments, '/');
            $this->_pathToImages = trim($configuration->files->pathToImages, '/');
            $this->_pathToThumbs = trim($configuration->files->pathToThumbs, '/');
            
            $this->checkDirectories();
        } else {
            throw new \Exception('Error in configuration file');
        }
    }

    /**
     * 
     */
    private function checkDirectories()
    {
        if (!is_dir(APP_PATH . '/' . $this->_pathToDocs)) {
            mkdir(APP_PATH . '/' . $this->_pathToDocs, 0755, true);
        }
        
        if (!is_dir(APP_PATH . '/' . $this->_pathToImages)) {
            mkdir(APP_PATH . '/' . $this->_pathToImages, 0755, true);
        }
        
        if (!is_dir(APP_PATH . '/' . $this->_pathToThumbs)) {
            mkdir(APP_PATH . '/' . $this->_pathToThumbs, 0755, true);
        }
    }

    /**
     * 
     * @param type $files
     * @return \ArrayObject
     */
    private function toIterator($files)
    {
        if (!$files instanceof \Traversable) {
            $files = new \ArrayObject(is_array($files) ? $files : array($files));
        }

        return $files;
    }

    /**
     * 
     * @param type $originFile
     * @param type $targetFile
     * @param type $override
     * @throws IOException
     */
    public function copy($originFile, $targetFile, $override = false)
    {
        if (stream_is_local($originFile) && !is_file($originFile)) {
            throw new Exception\IO(sprintf('Failed to copy %s because file not exists', $originFile));
        }

        $this->mkdir(dirname($targetFile));

        if (!$override && is_file($targetFile)) {
            $doCopy = filemtime($originFile) > filemtime($targetFile);
        } else {
            $doCopy = true;
        }

        if ($doCopy) {
            $source = fopen($originFile, 'r');
            $target = fopen($targetFile, 'w+');
            stream_copy_to_stream($source, $target);
            fclose($source);
            fclose($target);
            unset($source, $target);

            if (!is_file($targetFile)) {
                throw new Exception\IO(sprintf('Failed to copy %s to %s', $originFile, $targetFile));
            }
        }

        return true;
    }

    /**
     * 
     * @param type $files
     * @throws IOException
     */
    public function remove($files)
    {
        $files = iterator_to_array($this->toIterator($files));
        $files = array_reverse($files);
        foreach ($files as $file) {
            if (!file_exists($file) && !is_link($file)) {
                continue;
            }

            if (is_dir($file) && !is_link($file)) {
                $this->remove(new \FilesystemIterator($file));

                if (true !== @rmdir($file)) {
                    throw new Exception\IO(sprintf('Failed to remove directory %s', $file));
                }
            } else {
                if (is_dir($file)) {
                    if (true !== @rmdir($file)) {
                        throw new Exception\IO(sprintf('Failed to remove file %s', $file));
                    }
                } else {
                    if (true !== @unlink($file)) {
                        throw new Exception\IO(sprintf('Failed to remove file %s', $file));
                    }
                }
            }
        }

        return true;
    }

    /**
     * 
     * @param type $origin
     * @param type $target
     * @param type $overwrite
     * @throws IOException
     */
    public function rename($origin, $target, $overwrite = false)
    {
        if (!$overwrite && is_readable($target)) {
            throw new Exception\IO(sprintf('Cannot rename because the target "%s" already exist.', $target));
        }

        if (true !== @rename($origin, $target)) {
            throw new Exception\IO(sprintf('Cannot rename "%s" to "%s".', $origin, $target));
        }

        return true;
    }

    /**
     * 
     * @param type $dirs
     * @param type $mode
     * @throws IOException
     */
    public function mkdir($dirs, $mode = 0777)
    {
        foreach ($this->toIterator($dirs) as $dir) {
            if (is_dir($dir)) {
                continue;
            }

            if (true !== @mkdir($dir, $mode, true)) {
                throw new Exception\IO(sprintf('Failed to create %s', $dir));
            }
        }
        return true;
    }

    /**
     * 
     * @param type $files
     * @param type $mode
     * @param type $umask
     * @param type $recursive
     * @throws IOException
     */
    public function chmod($files, $mode, $umask = 0000, $recursive = false)
    {
        foreach ($this->toIterator($files) as $file) {
            if ($recursive && is_dir($file) && !is_link($file)) {
                $this->chmod(new \FilesystemIterator($file), $mode, $umask, true);
            }

            if (true !== @chmod($file, $mode & ~$umask)) {
                throw new Exception\IO(sprintf('Failed to chmod file %s', $file));
            }
        }

        return true;
    }

    /**
     * 
     * @param type $path
     * @return null
     */
    public function getExtension($path)
    {
        if ($path != '') {
            return strtolower(pathinfo($path, PATHINFO_EXTENSION));
        } else {
            return null;
        }
    }

    /**
     * 
     * @param type $path
     * @return null
     */
    public function getFileSize($path)
    {
        if ($path != '') {
            return filesize($path);
        } else {
            return null;
        }
    }

    /**
     * 
     * @param type $path
     * @return null
     */
    public function getFileName($path)
    {
        if ($path != '') {
            return pathinfo($path, PATHINFO_FILENAME);
        } else {
            return null;
        }
    }

    /**
     * 
     * @param type $path
     * @return null
     */
    public function getNormalizedFileName($path)
    {
        if ($path != '') {
            $name = pathinfo($path, PATHINFO_FILENAME);
            return StringMethods::removeDiacriticalMarks(
                            str_replace('.', '_', str_replace(' ', '_', $name))
            );
        } else {
            return null;
        }
    }

    /**
     * 
     * @param type $filename
     * @param type $content
     * @param type $mode
     * @throws IOException
     */
    public function dumpFile($filename, $content, $mode = 0666)
    {
        $dir = dirname($filename);

        if (!is_dir($dir)) {
            $this->mkdir($dir);
        } elseif (!is_writable($dir)) {
            throw new Exception\IO(sprintf('Unable to write in the %s directory\n', $dir));
        }

        $tmpFile = tempnam($dir, basename($filename));

        if (false === @file_put_contents($tmpFile, $content)) {
            throw new Exception\IO(sprintf('Failed to write file "%s".', $filename));
        }

        $this->rename($tmpFile, $filename, true);
        $this->chmod($filename, $mode);
    }

    /**
     * 
     * @return type
     */
    public function getPathToImages()
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
    public function getPathToThumbs()
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
     * @return type
     */
    public function getPathToDocuments()
    {
        if (is_dir('/' . $this->_pathToDocs)) {
            return '/' . $this->_pathToDocs;
        } elseif (is_dir('./' . $this->_pathToDocs)) {
            return './' . $this->_pathToDocs;
        } elseif (is_dir(APP_PATH . '/' . $this->_pathToDocs)) {
            return APP_PATH . '/' . $this->_pathToDocs;
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
        $pathToDocs = $this->getPathToDocuments() . '/' . $uploadto . '/';

        if (is_array($_FILES[$postField]['tmp_name'])) {
            $fileDataArray = array('files' => array(), 'errors' => array());

            foreach ($_FILES[$postField]['name'] as $i => $name) {
                if (is_uploaded_file($_FILES[$postField]['tmp_name'][$i])) {
                    $size = $_FILES[$postField]['size'][$i];
                    $extension = $this->getExtension($_FILES[$postField]['name'][$i]);
                    $filename = $this->getNormalizedFileName($_FILES[$postField]['name'][$i]);

                    if ($size > 5000000) {
                        $fileDataArray['errors'][] = sprintf('Your file %s size exceeds the maximum size limit', $filename);
                        continue;
                    } else {
                        if (in_array($extension, $this->_imageExtensions)) {
                            if (!is_dir($path)) {
                                $this->mkdir($path, 0755);
                            }

                            if (!is_dir($pathToThumbs)) {
                                $this->mkdir($pathToThumbs, 0755);
                            }

                            try {
                                $fileDataArray['files'][$i] = $this->uploadImage($_FILES[$postField]['tmp_name'][$i], $filename, $extension, $path, $pathToThumbs, $namePrefix);
                            } catch (Exception $ex) {
                                $fileDataArray['errors'][] = $ex->getMessage();
                            }
                        } elseif (in_array($extension, $this->_fileExtensions)) {
                            if (!is_dir($pathToDocs)) {
                                $this->mkdir($pathToDocs, 0755);
                            }

                            try {
                                $fileDataArray['files'][$i] = $this->uploadDocument($_FILES[$postField]['tmp_name'][$i], $filename, $extension, $pathToDocs, $namePrefix);
                            } catch (Exception $ex) {
                                $fileDataArray['errors'][] = $ex->getMessage();
                            }
                        } else {
                            $fileDataArray['errors'][] = sprintf('File has unsupported extension. Images: %s | Files: %s', join(', ', $this->_imageExtensions), join(', ', $this->_fileExtensions));
                            continue;
                        }
                    }
                } else {
                    $i += 1;
                    $fileDataArray['errors'][] = sprintf("Source %s cannot be empty", $i);
                    continue;
                }
            }

            return $fileDataArray;
        } else {
            if (is_uploaded_file($_FILES[$postField]['tmp_name'])) {
                $size = $_FILES[$postField]['size'];
                $extension = $this->getExtension($_FILES[$postField]['name']);
                $filename = $this->getNormalizedFileName($_FILES[$postField]['name']);

                if ($size > 5000000) {
                    throw new Exception(sprintf('Your file %s size exceeds the maximum size limit', $filename));
                } else {
                    if (in_array($extension, $this->_imageExtensions)) {
                        if (!is_dir($path)) {
                            $this->mkdir($path, 0755);
                        }

                        if (!is_dir($pathToThumbs)) {
                            $this->mkdir($pathToThumbs, 0755);
                        }

                        try {
                            $fileDataArray = $this->uploadImage($_FILES[$postField]['tmp_name'], $filename, $extension, $path, $pathToThumbs, $namePrefix);
                        } catch (Exception $ex) {
                            throw new Exception($ex->getMessage());
                        }

                        return $fileDataArray;
                    } elseif (in_array($extension, $this->_fileExtensions)) {
                        if (!is_dir($pathToDocs)) {
                            $this->mkdir($pathToDocs, 0755);
                        }

                        try {
                            $fileDataArray = $this->uploadDocument($_FILES[$postField]['tmp_name'], $filename, $extension, $pathToDocs, $namePrefix);
                        } catch (Exception $ex) {
                            throw new Exception($ex->getMessage());
                        }

                        return $fileDataArray;
                    } else {
                        throw new Exception(sprintf('File has unsupported extension. Images: %s | Files: %s', join(', ', $this->_imageExtensions), join(', ', $this->_fileExtensions)));
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

        if (is_array($_FILES[$postField]['tmp_name'])) {
            $returnArray = array('files' => array(), 'errors' => array());

            foreach ($_FILES[$postField]['name'] as $i => $name) {
                if (is_uploaded_file($_FILES[$postField]['tmp_name'][$i])) {
                    $size = $_FILES[$postField]['size'][$i];
                    $extension = $this->getExtension($_FILES[$postField]['name'][$i]);
                    $filename = $this->getNormalizedFileName($_FILES[$postField]['name'][$i]);

                    if ($size > 5000000) {
                        $returnArray['errors'][] = sprintf('Your file %s size exceeds the maximum size limit', $filename);
                        continue;
                    } else {
                        if (in_array($extension, $this->_imageExtensions)) {
                            if (!is_dir($path)) {
                                $this->mkdir($path, 0755);
                            }

                            try {
                                $fileDataArray['files'][$i] = $this->uploadImageWithoutThumb($_FILES[$postField]['tmp_name'][$i], $filename, $extension, $path, $namePrefix);
                            } catch (Exception $ex) {
                                $fileDataArray['errors'][] = $ex->getMessage();
                            }
                        } else {
                            $fileDataArray['errors'][] = sprintf('File has unsupported extension. Images: %s', join(', ', $this->_imageExtensions));
                            continue;
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
                $extension = $this->getExtension($_FILES[$postField]['name']);
                $filename = $this->getNormalizedFileName($_FILES[$postField]['name']);

                if ($size > 5000000) {
                    throw new Exception(sprintf('Your file %s size exceeds the maximum size limit', $filename));
                } else {
                    if (in_array($extension, $this->_imageExtensions)) {
                        if (!is_dir($path)) {
                            $this->mkdir($path, 0755);
                        }

                        try {
                            $fileDataArray = $this->uploadImageWithoutThumb($_FILES[$postField]['tmp_name'], $filename, $extension, $path, $namePrefix);
                        } catch (Exception $ex) {
                            throw new Exception($ex->getMessage());
                        }

                        return $fileDataArray;
                    } else {
                        throw new Exception(sprintf('File has unsupported extension. Images: %s', join(', ', $this->_imageExtensions)));
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
    private function backup($file)
    {
        $ext = $this->getExtension($file);
        $filename = $this->getFileName($file);
        $newFile = dirname($file) . '/' . $filename . '_' . time() . '.' . $ext;

        if (strlen($filename) > 50) {
            $newFile = dirname($file) . '/' . substr($filename, 0, 50) . '_' . time() . '.' . $ext;
        }

        $this->rename($file, $newFile);
        return;
    }

    /**
     * 
     * @param type $tmpFile
     * @param type $filename
     * @param type $extension
     * @param type $path
     * @param type $pathToThumbs
     * @param type $namePrefix
     * @return type
     * @throws Exception
     */
    private function uploadImage($tmpFile, $filename, $extension, $path, $pathToThumbs, $namePrefix)
    {
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

        $copy = move_uploaded_file($tmpFile, $imageLocName);

        if (!$copy) {
            throw new Exception(sprintf('Error while uploading image %s. Try again.', $filename));
        } else {
            $returnData = array();
            $img = new Image($imageLocName);

            if ($img->getWidth() > $this->getMaxImageWidth() || $img->getHeight() > $this->getMaxImageHeight()) {
                $img->bestFit($this->getMaxImageWidth(), $this->getMaxImageHeight())->save();
            }

            $returnData['file'] = $img->getDataForDb();

            switch ($this->thumbResizeBy) {
                case 'height':
                    $img->resizeToHeight($this->thumbHeight)->save($thumbLocName);
                    $returnData['thumb'] = $img->getDataForDb();
                    break;
                case 'width':
                    $img->resizeToWidth($this->thumbWidth)->save($thumbLocName);
                    $returnData['thumb'] = $img->getDataForDb();
                    break;
                default:
                    $img->thumbnail($this->thumbWidth, $this->thumbHeight)->save($thumbLocName);
                    $returnData['thumb'] = $img->getDataForDb();
                    break;
            }
            unset($img);
            return $returnData;
        }
    }

    /**
     * 
     * @param type $tmpFile
     * @param type $filename
     * @param type $extension
     * @param type $path
     * @param type $namePrefix
     * @return type
     * @throws Exception
     */
    private function uploadImageWithoutThumb($tmpFile, $filename, $extension, $path, $namePrefix)
    {
        if (strlen($filename) > 50) {
            $filename = substr($filename, 0, 50);
        }

        $imageName = $filename . '.' . $extension;
        $imageLocName = $path . $namePrefix . $imageName;

        if (file_exists($imageLocName)) {
            $this->backup($imageLocName);
        }

        $copy = move_uploaded_file($tmpFile, $imageLocName);

        if (!$copy) {
            throw new Exception(sprintf('Error while uploading image %s. Try again.', $filename));
        } else {
            $returnData = array();
            $img = new Image($imageLocName);

            if ($img->getWidth() > $this->getMaxImageWidth() || $img->getHeight() > $this->getMaxImageHeight()) {
                $img->bestFit($this->getMaxImageWidth(), $this->getMaxImageHeight())->save();
            }

            $returnData['file'] = $img->getDataForDb();

            unset($img);
            return $returnData;
        }
    }

    /**
     * 
     * @param type $tmpFile
     * @param type $filename
     * @param type $extension
     * @param type $path
     * @param type $namePrefix
     */
    private function uploadDocument($tmpFile, $filename, $extension, $path, $namePrefix)
    {
        if (strlen($filename) > 50) {
            $filename = substr($filename, 0, 50);
        }

        $fileNameExt = $filename . '.' . $extension;
        $fileLocName = $path . $namePrefix . $fileNameExt;

        if (file_exists($fileLocName)) {
            $this->backup($fileLocName);
        }

        $copy = move_uploaded_file($tmpFile, $fileLocName);

        if (!$copy) {
            throw new Exception(sprintf('Error while uploading image %s. Try again.', $filename));
        } else {
            $returnData = array();
            $file = new File($fileLocName);
            $returnData['file'] = $file->getDataForDb();
            unset($file);
            return $returnData;
        }
    }

}
