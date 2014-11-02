<?php

namespace THCFrame\Filesystem;

use THCFrame\Core\Base;
use THCFrame\Filesystem\Exception;
use THCFrame\Registry\Registry;
use THCFrame\Filesystem\Image;
use THCFrame\Filesystem\File;
use THCFrame\Core\StringMethods;

/**
 * FileManager class
 */
class FileManager extends Base
{

    const DIR_CHMOD = 0755;
    const FILE_CHMOD = 0644;
    const MAX_FILE_UPLOAD_SIZE = 5000000;

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
     * @readwrite
     */
    protected $_uploadedFiles = array();

    /**
     * @readwrite
     */
    protected $_uploadErrors = array();

    /**
     * @read
     */
    protected $_imageExtensions = array('gif', 'jpg', 'png', 'jpeg');

    /**
     * @read
     */
    protected $_fileExtensions = array('rtf', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'pdf', 'ppt', 'pptx', 'zip', 'rar');

    /**
     * Object constructor
     * 
     * @param array $options
     * @throws \Exception
     */
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
     * Check for default directory structure. 
     * Creates it if needed
     */
    private function checkDirectories()
    {
        if (!is_dir(APP_PATH . '/' . $this->_pathToDocs)) {
            mkdir(APP_PATH . '/' . $this->_pathToDocs, self::DIR_CHMOD, true);
        }

        if (!is_dir(APP_PATH . '/' . $this->_pathToImages)) {
            mkdir(APP_PATH . '/' . $this->_pathToImages, self::DIR_CHMOD, true);
        }

        if (!is_dir(APP_PATH . '/' . $this->_pathToThumbs)) {
            mkdir(APP_PATH . '/' . $this->_pathToThumbs, self::DIR_CHMOD, true);
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
     * Create backup for specific file
     * 
     * @param string $file
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
    }

    /**
     * Copy file
     * 
     * @param string $originFile
     * @param string $targetFile
     * @param boolean $override
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
     * Remove files
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
     * Rename file
     * 
     * @param string $origin
     * @param string $target
     * @param boolean $overwrite
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
     * Create directories
     * 
     * @param mixed $dirs
     * @param umask $mode
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
     * Set permissions for file
     * 
     * @param mixed $files
     * @param mixed $mode
     * @param mixed $umask
     * @param boolean $recursive
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
     * Get file extension
     * 
     * @param string $path
     * @return null|string
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
     * Get file size
     * 
     * @param string $path
     * @return null|integer
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
     * Get file name
     * 
     * @param string $path
     * @return null|string
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
     * Get cleaned file name
     * 
     * @param string $path
     * @return null|string
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
     * Get path to the image folder
     * 
     * @return string
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
     * Get path to the image thumbs folder
     * 
     * @return string
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
     * Get path to the documents folder
     * 
     * @return string
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
     * Upload file
     * 
     * @param string $postField
     * @param string $uploadto
     * @param string $namePrefix
     * @param boolean $createThumb
     * @return self
     */
    public function upload($postField, $uploadto, $namePrefix = '', $createThumb = true)
    {
        $pathToImages = $this->getPathToImages() . '/' . $uploadto . '/';
        $pathToThumbs = $this->getPathToThumbs() . '/' . $uploadto . '/';
        $pathToDocs = $this->getPathToDocuments() . '/' . $uploadto . '/';

        //directory structure check
        if (!is_dir($pathToImages)) {
            $this->mkdir($pathToImages, self::DIR_CHMOD);
        }

        if (!is_dir($pathToThumbs)) {
            $this->mkdir($pathToThumbs, self::DIR_CHMOD);
        }

        if (!is_dir($pathToDocs)) {
            $this->mkdir($pathToDocs, self::DIR_CHMOD);
        }

        //check if its multiple file upload
        if (is_array($_FILES[$postField]['tmp_name'])) {
            foreach ($_FILES[$postField]['name'] as $i => $name) {
                if (!empty($_FILES[$postField]['error'][$i])) {
                    $error = $_FILES[$postField]['error'][$i];
                    $name = $_FILES[$postField]['name'][$i];

                    //check for upload errors
                    switch ($error) {
                        case UPLOAD_ERR_INI_SIZE:
                            $this->_uploadErrors[] = sprintf('The uploaded file %s exceeds the upload_max_filesize directive in php.ini', $name);
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            $this->_uploadErrors[] = sprintf('The uploaded file %s exceeds the MAX_FILE_SIZE directive that was specified in the HTML form', $name);
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $this->_uploadErrors[] = sprintf('The uploaded file %s was only partially uploaded', $name);
                            break;
                        case UPLOAD_ERR_NO_FILE:
                            $this->_uploadErrors[] = "No file was uploaded";
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $this->_uploadErrors[] = "Missing a temporary folder";
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $this->_uploadErrors[] = sprintf('Failed to write file %s to disk', $name);
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $this->_uploadErrors[] = "File upload stopped by extension";
                            break;

                        default:
                            $this->_uploadErrors[] = sprintf('Unknown upload error occured while uploading file %s', $name);
                            break;
                    }
                    continue;
                }

                if (is_uploaded_file($_FILES[$postField]['tmp_name'][$i])) {
                    $size = $_FILES[$postField]['size'][$i];
                    $extension = $this->getExtension($_FILES[$postField]['name'][$i]);
                    $filename = $this->getNormalizedFileName($_FILES[$postField]['name'][$i]);

                    if (strlen($filename) > 50) {
                        $filename = substr($filename, 0, 50);
                    }

                    if ($size > self::MAX_FILE_UPLOAD_SIZE) {
                        $this->_uploadErrors[] = sprintf('Your file %s size exceeds the maximum size limit', $filename);
                        continue;
                    } else {
                        if (in_array($extension, $this->_imageExtensions)) {
                            $imageName = $filename . '.' . $extension;
                            $thumbName = $filename . '_thumb.' . $extension;
                            $imageLocName = $pathToImages . $namePrefix . $imageName;
                            $thumbLocName = $pathToThumbs . $namePrefix . $thumbName;

                            if (file_exists($imageLocName)) {
                                $this->backup($imageLocName);
                            }

                            if (file_exists($thumbLocName)) {
                                $this->backup($thumbLocName);
                            }

                            $copy = move_uploaded_file($_FILES[$postField]['tmp_name'][$i], $imageLocName);

                            if (!$copy) {
                                $this->_uploadErrors[] = sprintf('Error while uploading image %s. Try again.', $filename);
                                continue;
                            } else {
                                $img = new Image($imageLocName);
                                $img->setSize($size);

                                if ($img->getWidth() > $this->getMaxImageWidth() || $img->getHeight() > $this->getMaxImageHeight()) {
                                    $img->bestFit($this->getMaxImageWidth(), $this->getMaxImageHeight())->save();
                                }

                                if ($createThumb) {
                                    $img->setThumbname($thumbLocName);
                                    $this->_uploadedFiles[] = $img;

                                    $thumb = clone $img;

                                    switch ($this->thumbResizeBy) {
                                        case 'height':
                                            $thumb->resizeToHeight($this->thumbHeight)->save($thumbLocName);
                                            break;
                                        case 'width':
                                            $thumb->resizeToWidth($this->thumbWidth)->save($thumbLocName);
                                            break;
                                        default:
                                            $thumb->thumbnail($this->thumbWidth, $this->thumbHeight)->save($thumbLocName);
                                            break;
                                    }

                                    unset($img);
                                    unset($thumb);
                                    continue;
                                } else {
                                    $this->_uploadedFiles[] = $img;
                                    continue;
                                }
                            }
                        } elseif (in_array($extension, $this->_fileExtensions)) {
                            $fileNameExt = $filename . '.' . $extension;
                            $fileLocName = $pathToDocs . $namePrefix . $fileNameExt;

                            if (file_exists($fileLocName)) {
                                $this->backup($fileLocName);
                            }

                            $copy = move_uploaded_file($_FILES[$postField]['tmp_name'][$i], $fileLocName);

                            if (!$copy) {
                                $this->_uploadErrors[] = sprintf('Error while uploading image %s. Try again.', $filename);
                                continue;
                            } else {
                                $file = new File($fileLocName);

                                $this->_uploadedFiles[] = $file;
                                unset($file);
                                continue;
                            }
                        } else {
                            $this->_uploadErrors[] = sprintf('File has unsupported extension. Images: %s | Files: %s', join(', ', $this->_imageExtensions), join(', ', $this->_fileExtensions));
                            continue;
                        }
                    }
                } else {
                    $this->_uploadErrors[] = sprintf("Source %s cannot be empty", $i);
                    continue;
                }
            }
        } else {
            if (!empty($_FILES[$postField]['error'])) {
                $error = $_FILES[$postField]['error'];
                $name = $_FILES[$postField]['name'];

                switch ($error) {
                    case UPLOAD_ERR_INI_SIZE:
                        $this->_uploadErrors[] = sprintf('The uploaded file %s exceeds the upload_max_filesize directive in php.ini', $name);
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $this->_uploadErrors[] = sprintf('The uploaded file %s exceeds the MAX_FILE_SIZE directive that was specified in the HTML form', $name);
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $this->_uploadErrors[] = sprintf('The uploaded file %s was only partially uploaded', $name);
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $this->_uploadErrors[] = "No file was uploaded";
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $this->_uploadErrors[] = "Missing a temporary folder";
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $this->_uploadErrors[] = sprintf('Failed to write file %s to disk', $name);
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $this->_uploadErrors[] = "File upload stopped by extension";
                        break;

                    default:
                        $this->_uploadErrors[] = sprintf('Unknown upload error occured while uploading file %s', $name);
                        break;
                }
            }

            if (is_uploaded_file($_FILES[$postField]['tmp_name'])) {
                $size = $_FILES[$postField]['size'];
                $extension = $this->getExtension($_FILES[$postField]['name']);
                $filename = $this->getNormalizedFileName($_FILES[$postField]['name']);

                if ($size > self::MAX_FILE_UPLOAD_SIZE) {
                    $this->_uploadErrors[] = sprintf('Your file %s size exceeds the maximum size limit', $filename);
                } else {
                    if (in_array($extension, $this->_imageExtensions)) {
                        $imageName = $filename . '.' . $extension;
                        $thumbName = $filename . '_thumb.' . $extension;
                        $imageLocName = $pathToImages . $namePrefix . $imageName;
                        $thumbLocName = $pathToThumbs . $namePrefix . $thumbName;

                        if (file_exists($imageLocName)) {
                            $this->backup($imageLocName);
                        }

                        if (file_exists($thumbLocName)) {
                            $this->backup($thumbLocName);
                        }

                        $copy = move_uploaded_file($_FILES[$postField]['tmp_name'], $imageLocName);

                        if (!$copy) {
                            $this->_uploadErrors[] = sprintf('Error while uploading image %s. Try again.', $filename);
                        } else {
                            $img = new Image($imageLocName);
                            $img->setSize($size);

                            if ($img->getWidth() > $this->getMaxImageWidth() || $img->getHeight() > $this->getMaxImageHeight()) {
                                $img->bestFit($this->getMaxImageWidth(), $this->getMaxImageHeight())->save();
                            }

                            if ($createThumb) {
                                $img->setThumbname($thumbLocName);
                                $this->_uploadedFiles[] = $img;

                                $thumb = clone $img;

                                switch ($this->thumbResizeBy) {
                                    case 'height':
                                        $thumb->resizeToHeight($this->thumbHeight)->save($thumbLocName);
                                        break;
                                    case 'width':
                                        $thumb->resizeToWidth($this->thumbWidth)->save($thumbLocName);
                                        break;
                                    default:
                                        $thumb->thumbnail($this->thumbWidth, $this->thumbHeight)->save($thumbLocName);
                                        break;
                                }

                                unset($img);
                                unset($thumb);
                            } else {
                                $this->_uploadedFiles[] = $img;
                            }
                        }
                    } elseif (in_array($extension, $this->_fileExtensions)) {
                        $fileNameExt = $filename . '.' . $extension;
                        $fileLocName = $pathToDocs . $namePrefix . $fileNameExt;

                        if (file_exists($fileLocName)) {
                            $this->backup($fileLocName);
                        }

                        $copy = move_uploaded_file($_FILES[$postField]['tmp_name'], $fileLocName);

                        if (!$copy) {
                            $this->_uploadErrors[] = sprintf('Error while uploading image %s. Try again.', $filename);
                        } else {
                            $file = new File($fileLocName);

                            $this->_uploadedFiles[] = $file;
                            unset($file);
                        }
                    } else {
                        $this->_uploadErrors[] = sprintf('File has unsupported extension. Images: %s | Files: %s', join(', ', $this->_imageExtensions), join(', ', $this->_fileExtensions));
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Upload image via base64 string
     * 
     * @param string $base64string
     * @param string $filename
     * @param string $uploadTo
     * @param string $namePrefix
     * @param boolean $createThumb
     * @return \THCFrame\Filesystem\FileManager
     */
    public function uploadBase64Image($base64string, $filename, $uploadTo, $namePrefix = '', $createThumb = true)
    {
        $img = new Image(null, $base64string);

        $pathToImages = $this->getPathToImages() . '/' . $uploadTo . '/';
        $pathToThumbs = $this->getPathToThumbs() . '/' . $uploadTo . '/';

        if (!is_dir($pathToImages)) {
            $this->mkdir($pathToImages, self::DIR_CHMOD);
        }

        if (!is_dir($pathToThumbs)) {
            $this->mkdir($pathToThumbs, self::DIR_CHMOD);
        }

        $fileinfo = $img->getOriginalInfo();
        $extension = $fileinfo['format'];

        if (in_array($extension, $this->_imageExtensions)) {
            $imageName = $filename . '.' . $extension;
            $thumbName = $filename . '_thumb.' . $extension;
            $imageLocName = $pathToImages . $namePrefix . $imageName;
            $thumbLocName = $pathToThumbs . $namePrefix . $thumbName;

            if (file_exists($imageLocName)) {
                $this->backup($imageLocName);
            }

            if (file_exists($thumbLocName)) {
                $this->backup($thumbLocName);
            }

            if ($img->getWidth() > $this->getMaxImageWidth() || $img->getHeight() > $this->getMaxImageHeight()) {
                $img->bestFit($this->getMaxImageWidth(), $this->getMaxImageHeight())->save();
            }

            $img->save($imageLocName);

            if ($createThumb) {
                $img->setThumbname($thumbLocName);
                $this->_uploadedFiles[] = $img;

                $thumb = clone $img;

                switch ($this->thumbResizeBy) {
                    case 'height':
                        $thumb->resizeToHeight($this->thumbHeight)->save($thumbLocName);
                        break;
                    case 'width':
                        $thumb->resizeToWidth($this->thumbWidth)->save($thumbLocName);
                        break;
                    default:
                        $thumb->thumbnail($this->thumbWidth, $this->thumbHeight)->save($thumbLocName);
                        break;
                }
            } else {
                $this->_uploadedFiles[] = $img;
            }
        } else {
            $this->_uploadErrors[] = sprintf('File has unsupported extension. Images: %s | Files: %s', join(', ', $this->_imageExtensions), join(', ', $this->_fileExtensions));
        }

        return $this;
    }

    /**
     * Clear upload arrays
     * @return \THCFrame\Filesystem\FileManager
     */
    public function newUpload()
    {
        $this->_uploadedFiles = array();
        $this->_uploadErrors = array();
        
        return $this;
    }
}
