<?php

namespace South634\MassMediaBundle\Util;

/**
 * Creates hashed filenames and spreads file uploads over multiple subfolders
 * 
 * The MassMediaManager is used to hash file uploads using your chosen hash 
 * algorithm, and create a new file name for it. It can then create
 * subdirectories as many levels deep as you desire based on this hashed file 
 * name. A side benefit is that each file has its own unique hash, and therefore
 * can prevent duplicate uploads of the same exact file, as the exact file will 
 * always hash to the exact filename.
 */
class MassMediaManager
{

    /**
     * @var string
     */
    private $hash_algo;

    /**
     * @var integer
     */
    private $folder_depth;

    /**
     * @var integer
     */
    private $folder_chars;

    /**
     * @var string
     */
    private $upload_dir;

    /**
     * @var string
     */
    private $web_dir_name;

    /**
     * @var string
     */
    private $root_dir;

    /**
     * @param array $settings
     */
    public function __construct($settings)
    {
        $this->hash_algo = $settings['hash_algo'];
        $this->folder_depth = $settings['folder_depth'];
        $this->folder_chars = $settings['folder_chars'];
        $this->upload_dir = $settings['upload_dir'];
        $this->web_dir_name = $settings['web_dir_name'];
        $this->root_dir = $settings['root_dir'];

        // Create upload directory if does not exist yet
        if ($this->upload_dir && !file_exists($this->getUploadRootDir())) {
            mkdir($this->getUploadRootDir());
        }
    }

    /**
     * Helper function to return web path of file
     * 
     * @param string $fileName
     * @return string|null
     */
    public function getWebPath($fileName)
    {
        if ($fileName !== null) {
            // If folder depth is greater than 0, include subfolder path
            if ($this->folder_depth > 0) {
                $webPath = $this->upload_dir . '/' . $this->getSubFolderPath($fileName) . '/' . $fileName;
            }
            // Else just filename
            else {
                $webPath = $this->upload_dir . '/' . $fileName;
            }
            return $webPath;
        }
        else {
            return null;
        }
    }

    /**
     * Returns subfolder path under uploads folder to file
     * 
     * @param string $fileName
     * @return string|null
     */
    public function getSubFolderPath($fileName)
    {
        if ($this->folder_chars > 0 && $fileName !== null) {
            // Filename without extension
            $fileNameNoExt = pathinfo($fileName, PATHINFO_FILENAME);

            // Split filename into array every x chars
            $filename_arr = str_split($fileNameNoExt, $this->folder_chars);

            // Slice off items in filename_arr array up to folder_depth limit
            // and implode results back together with a folder backslash 
            return implode('/', array_slice($filename_arr, 0, $this->folder_depth));
        }
        else {
            return null;
        }
    }

    /**
     * Returns the absolute path to a folder based on filename
     * 
     * @param string $fileName
     * @return string|null
     */
    public function getAbsoluteFolderPath($fileName)
    {
        return $fileName === null ? null : $this->getUploadRootDir() . '/' . $this->getSubFolderPath($fileName);
    }

    /**
     * Returns the absolute path to a file based on filename
     * 
     * @param string $fileName
     * @return string|null
     */
    public function getAbsoluteFilePath($fileName)
    {
        return $fileName === null ? null : $this->getAbsoluteFolderPath($fileName) . '/' . $fileName;
    }

    /**
     * Returns the absolute path to the upload directory
     * 
     * @return string
     */
    public function getUploadRootDir()
    {
        return $this->root_dir . '/../' . $this->web_dir_name . '/' . $this->upload_dir;
    }

    /**
     * Returns a filename based on user settings
     * 
     * Creates a filename using hash algorithm function. Includes ability to 
     * add a unique string to the hashed file which was uploaded. This can be 
     * useful when wanting to add a User id for example to the uploaded file, so
     * an identical file upload would get a unique filename for a different User.
     * 
     * @param UploadedFile $file
     * @param string|integer|null $unique
     * @return string
     */
    public function getFileName($file, $unique = null)
    {
        // Get filename extension and set to lowercase
        $file_ext = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));

        // Change jpeg to jpg
        $file_ext = $file_ext == 'jpeg' ? 'jpg' : $file_ext;

        // Return file name with chosen hash algo function
        return hash($this->hash_algo, $unique . hash_file($this->hash_algo, $file->getRealPath())) . '.' . $file_ext;
    }

    /**
     * Returns a filename from URL based on user settings
     * 
     * Creates a filename from a URL instead of from an uploaded file. Use this
     * method when you need to create a hashed filename from a file available
     * online, for example a YouTube video thumbnail image.
     * 
     * @param string $url
     * @param string|integer|null $unique
     * @return string
     */
    public function getFileNameFromUrl($url, $unique = null)
    {
        // Get filename extension and set to lowercase
        $file_ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));

        // Change jpeg to jpg
        $file_ext = $file_ext == 'jpeg' ? 'jpg' : $file_ext;

        // Return file name with chosen hash algo function
        return hash($this->hash_algo, $unique . hash_file($this->hash_algo, $url)) . '.' . $file_ext;
    }

    /**
     * Returns an array of filenames
     * 
     * Use this when handling an array of uploaded files.
     * 
     * @param array $files
     * @param string|integer|null $unique
     * @return array $fileNames
     */
    public function getFileNames($files, $unique = null)
    {
        $fileNames = array();

        foreach ($files as $file) {

            $fileNames[] = $this->getFileName($file, $unique);
        }

        return $fileNames;
    }

    /**
     * Returns an array of filenames from urls
     * 
     * Use this when handling an array of urls.
     * 
     * @param array $urls
     * @param string|integer|null $unique
     * @return array $fileNames
     */
    public function getFileNamesFromUrls($urls, $unique = null)
    {
        $fileNames = array();

        foreach ($urls as $url) {

            $fileNames[] = $this->getFileNameFromUrl($url, $unique);
        }

        return $fileNames;
    }

    /**
     * Uploads an UploadedFile object
     * 
     * @param UploadedFile $file
     * @param string|integer|null $unique Optional param for adding uniqueness to hash
     * @param string|null $fileName Optional param for setting custom filename
     */
    public function uploadFile($file, $unique = null)
    {
        if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            throw new \Exception('Parameter 1 must be instance of UploadedFile');
        }
        
        $fileName = $this->getFileName($file, $unique);
        
        // Check if directory to upload file does not exist
        if (!file_exists($this->getAbsoluteFolderPath($fileName))) {

            // Create directory to upload this file
            $this->createDir($fileName);
        }

        // Upload file to destination
        $file->move($this->getAbsoluteFolderPath($fileName), $fileName);
    }

    /**
     * Uploads a file from a URL
     * 
     * @param string $url
     * @param string|integer|null $unique Optional param for adding uniqueness to hash
     */
    public function uploadFileFromUrl($url, $unique = null)
    {
        if (!is_string($url)) {
            throw new \Exception('Parameter 1 must be a string');
        }
        
        $fileName = $this->getFileNameFromUrl($url, $unique);
        
        // Check if directory to upload file does not exist
        if (!file_exists($this->getAbsoluteFolderPath($fileName))) {

            // Create directory to upload this file
            $this->createDir($fileName);
        }

        // Upload file to destination
        file_put_contents($this->getAbsoluteFilePath($fileName), file_get_contents($url));
    }

    /**
     * Uploads an array of UploadedFiles
     * 
     * @param array $files
     * @param string|integer|null $unique Optional param for adding uniqueness to hash
     */
    public function uploadFiles($files, $unique = null)
    {
        foreach ($files as $file) {

            // Upload file
            $this->uploadFile($file, $unique);
        }
    }

    /**
     * Uploads an array of UploadedFiles
     * 
     * @param array $urls
     * @param string|integer|null $unique Optional param for adding uniqueness to hash
     */
    public function uploadFilesFromUrls($urls, $unique = null)
    {
        foreach ($urls as $url) {

            // Upload file
            $this->uploadFileFromUrl($url, $unique);
        }
    }

    /**
     * Makes directories needed to upload file to
     * 
     * @param string $fileName
     */
    private function createDir($fileName)
    {
        // Split filename into array every x chars
        $filename_arr = str_split($fileName, $this->folder_chars);

        $upload_root_dir = $this->getUploadRootDir();

        // Create any directories which don't exist yet
        $i = 0;
        while ($i < $this->folder_depth) {

            // Set current absolute dir to check for existence
            $dir = $upload_root_dir . '/' . $filename_arr[$i];

            // Create dir if none exists
            if (!file_exists($dir)) {
                mkdir($dir);
            }

            // Set current dir as new parent folder
            $upload_root_dir = $dir;

            $i++;
        }
    }

    /**
     * Removes file and any empty subfolders created for this file.
     * 
     * Set $removeFolders to false if you want to keep the subfolders.
     * 
     * @param string $fileName
     * @param boolean $removeFolders
     */
    public function removeFile($fileName, $removeFolders = true)
    {
        $filePath = $this->getAbsoluteFilePath($fileName);

        if (file_exists($filePath)) {
            // Remove file
            unlink($filePath);
        }

        if ($removeFolders) {

            // Delete empty subfolders created for this file

            $subFolders = explode('/', $this->getSubFolderPath($fileName));

            while (count($subFolders) > 0) {

                $subFoldersPath = implode('/', $subFolders);

                $subFolderAbsolutePath = $this->getUploadRootDir() . '/' . $subFoldersPath;

                // If folder is empty
                if (count(glob($subFolderAbsolutePath . '/*')) === 0) {
                    // Remove folder
                    rmdir($subFolderAbsolutePath);
                }

                array_pop($subFolders);
            }
        }
    }

}
