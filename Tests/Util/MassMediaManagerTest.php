<?php

namespace South634\MassMediaBundle\Tests\Util;

use South634\MassMediaBundle\Util\MassMediaManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MassMediaManagerTest extends \PHPUnit_Framework_TestCase
{

    public static $tempFileName;
    public static $tempFilePath;
    public static $uploadFiles;
    protected $unique;
    protected $settings;
    protected $testFileName;
    protected $expectedFileName;
    protected $expectedFileNameUnique;

    public static function setUpBeforeClass()
    {
        $folderNames = array('Fixtures', 'app', 'web');
        foreach ($folderNames as $folderName) {
            $folderPath = __DIR__ . '/../' . $folderName;
            if (!file_exists($folderPath)) {
                mkdir($folderPath);
            }
        }

        // Create temp file to be used in testing hash_file
        self::$tempFileName = 'test.jpg';
        self::$tempFilePath = __DIR__ . '/../Fixtures/' . self::$tempFileName;
        imagejpeg(imagecreatetruecolor(1, 1), self::$tempFilePath);

        // Generates expected path for uploaded file
        $getExpectedFilePath = function ($expectedFileName) {
            // Create expected path string
            $filePath = $expectedFileName;
            // Split 2 characters each folder name
            $filePathArr = str_split($filePath, 2);
            // Loop 2 folders deep
            $i = 1;
            while ($i >= 0) {
                $filePath = $filePathArr[$i] . '/' . $filePath;
                $i--;
            }
            return __DIR__ . '/../web/media/' . $filePath;
        };

        // Create files for testing uploads
        self::$uploadFiles = array();

        $uploadFileNames = array(
            'testUpload1.jpg',
            'testUpload2.jpg',
        );

        // Unique used for file upload with unique test
        $unique = 2;
        // Initial pixel width for file
        $pixelWidth = 1;
        foreach ($uploadFileNames as $uploadFileName) {
            $uploadFilePath = __DIR__ . '/../Fixtures/' . $uploadFileName;
            imagejpeg(imagecreatetruecolor($pixelWidth, 1), $uploadFilePath);

            $expectedFileName = hash('sha1', hash_file('sha1', $uploadFilePath)) . '.jpg';
            $expectedFileNameUnique = hash('sha1', $unique . hash_file('sha1', $uploadFilePath)) . '.jpg';

            self::$uploadFiles[] = array(
                'fileName' => $uploadFileName,
                'filePath' => $uploadFilePath,
                'expectedFileName' => $expectedFileName,
                'expectedFilePath' => $getExpectedFilePath($expectedFileName),
                'expectedFilePathUnique' => $getExpectedFilePath($expectedFileNameUnique),
                'unique' => $unique,
            );

            // Increment width for each file
            $pixelWidth++;
        }
    }

    protected function setUp()
    {
        // Create valid settings
        $this->settings = array(
            'hash_algo' => 'sha1',
            'folder_depth' => 2,
            'folder_chars' => 2,
            'upload_dir' => 'media',
            'web_dir_name' => 'web',
            'root_dir' => __DIR__ . '/../app',
        );

        // Create filename used for testing paths
        $this->testFileName = 'abcdefghijklmnopqrstuvwxyz.jpg';

        // Create unique key
        $this->unique = 2;

        // Create expected hashed filename to be created each time
        $this->expectedFileName = hash($this->settings['hash_algo'], hash_file($this->settings['hash_algo'], self::$tempFilePath)) . '.jpg';

        // Create expected hashed filename to be created each time with unique
        $this->expectedFileNameUnique = hash($this->settings['hash_algo'], $this->unique . hash_file($this->settings['hash_algo'], self::$tempFilePath)) . '.jpg';
    }

    public function testConstructShouldPassWithValidSettings()
    {
        $mmm = new MassMediaManager($this->settings);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testConstructShouldThrowExceptionIfNoSettingsInjected()
    {
        $mmm = new MassMediaManager();
    }

    public function testGetWebPathReturnsCorrectPathBasedOnFileName()
    {
        $mmm = new MassMediaManager($this->settings);

        $this->assertEquals('media/ab/cd/abcdefghijklmnopqrstuvwxyz.jpg', $mmm->getWebPath($this->testFileName));
    }

    public function testGetWebPathReturnsCorrectPathBasedOnFileNameIfFolderDepthIsZero()
    {
        $this->settings['folder_depth'] = 0;

        $mmm = new MassMediaManager($this->settings);

        $this->assertEquals('media/abcdefghijklmnopqrstuvwxyz.jpg', $mmm->getWebPath($this->testFileName));
    }

    public function testGetWebPathReturnsNullIfFileNameIsNull()
    {
        $mmm = new MassMediaManager($this->settings);

        $this->assertNull($mmm->getWebPath(null));
    }

    public function testGetSubFolderPathReturnsCorrectPathBasedOnFileName()
    {
        $mmm = new MassMediaManager($this->settings);

        $this->assertEquals('ab/cd', $mmm->getSubFolderPath($this->testFileName));
    }

    public function testGetSubFolderPathReturnsCorrectPathBasedOnFileNameIfFolderCharsIsZero()
    {
        $this->settings['folder_chars'] = 0;

        $mmm = new MassMediaManager($this->settings);

        $this->assertEquals('', $mmm->getSubFolderPath($this->testFileName));
    }

    public function testGetSubFolderPathReturnsNullIfFileNameIsNull()
    {
        $mmm = new MassMediaManager($this->settings);

        $this->assertNull($mmm->getSubFolderPath(null));
    }

    public function testGetAbsoluteFolderPathReturnsCorrectPathBasedOnFileName()
    {
        $expectedPath = $this->settings['root_dir'] . '/../web/media/ab/cd';

        $mmm = new MassMediaManager($this->settings);

        $this->assertEquals($expectedPath, $mmm->getAbsoluteFolderPath($this->testFileName));
    }

    public function testGetAbsoluteFolderPathReturnsNullIfFileNameIsNull()
    {
        $mmm = new MassMediaManager($this->settings);

        $this->assertNull($mmm->getAbsoluteFolderPath(null));
    }

    public function testGetAbsoluteFilePathReturnsCorrectPathBasedOnFileName()
    {
        $expectedPath = $this->settings['root_dir'] . '/../web/media/ab/cd/abcdefghijklmnopqrstuvwxyz.jpg';

        $mmm = new MassMediaManager($this->settings);

        $this->assertEquals($expectedPath, $mmm->getAbsoluteFilePath($this->testFileName));
    }

    public function testGetAbsoluteFilePathReturnsNullIfFileNameIsNull()
    {
        $mmm = new MassMediaManager($this->settings);

        $this->assertNull($mmm->getAbsoluteFilePath(null));
    }

    public function testGetUploadRootDirReturnsCorrectPathToFolder()
    {
        $expectedPath = $this->settings['root_dir'] . '/../web/media';

        $mmm = new MassMediaManager($this->settings);

        $this->assertEquals($expectedPath, $mmm->getUploadRootDir());
    }

    public function testGetFileNameReturnsCorrectHashedFileName()
    {
        $file = new UploadedFile(self::$tempFilePath, self::$tempFileName);

        $mmm = new MassMediaManager($this->settings);

        $fileName = $mmm->getFileName($file);

        $this->assertEquals($this->expectedFileName, $fileName);
    }

    public function testGetFileNameReturnsCorrectHashedFileNameWithUnique()
    {
        $file = new UploadedFile(self::$tempFilePath, self::$tempFileName);

        $mmm = new MassMediaManager($this->settings);

        $fileName = $mmm->getFileName($file, $this->unique);

        $this->assertEquals($this->expectedFileNameUnique, $fileName);
    }

    public function testGetFileNameFromUrlReturnsCorrectHashedFileName()
    {
        $mmm = new MassMediaManager($this->settings);

        $fileName = $mmm->getFileNameFromUrl(self::$tempFilePath);

        $this->assertEquals($this->expectedFileName, $fileName);
    }

    public function testGetFileNameFromUrlReturnsCorrectHashedFileNameWithUnique()
    {
        $mmm = new MassMediaManager($this->settings);

        $fileName = $mmm->getFileNameFromUrl(self::$tempFilePath, $this->unique);

        $this->assertEquals($this->expectedFileNameUnique, $fileName);
    }

    public function testGetFileNamesReturnsArrayWithCorrectHashedFileNames()
    {
        $expectedFileNames = array($this->expectedFileName, $this->expectedFileName);

        $mmm = new MassMediaManager($this->settings);

        $file = new UploadedFile(self::$tempFilePath, self::$tempFileName);
        $files = array($file, $file);
        $fileNames = $mmm->getFileNames($files);

        $this->assertEquals($expectedFileNames, $fileNames);
    }

    public function testGetFileNamesReturnsArrayWithCorrectHashedFileNamesWithUnique()
    {
        $expectedFileNames = array($this->expectedFileNameUnique, $this->expectedFileNameUnique);

        $mmm = new MassMediaManager($this->settings);

        $file = new UploadedFile(self::$tempFilePath, self::$tempFileName);
        $files = array($file, $file);
        $fileNames = $mmm->getFileNames($files, $this->unique);

        $this->assertEquals($expectedFileNames, $fileNames);
    }

    public function testGetFileNamesFromUrlsReturnsArrayWithCorrectHashedFileNames()
    {
        $expectedFileNames = array($this->expectedFileName, $this->expectedFileName);

        $mmm = new MassMediaManager($this->settings);

        $files = array(self::$tempFilePath, self::$tempFilePath);
        $fileNames = $mmm->getFileNamesFromUrls($files);

        $this->assertEquals($expectedFileNames, $fileNames);
    }

    public function testGetFileNamesFromUrlsReturnsArrayWithCorrectHashedFileNamesWithUnique()
    {
        $expectedFileNames = array($this->expectedFileNameUnique, $this->expectedFileNameUnique);

        $mmm = new MassMediaManager($this->settings);

        $files = array(self::$tempFilePath, self::$tempFilePath);
        $fileNames = $mmm->getFileNamesFromUrls($files, $this->unique);

        $this->assertEquals($expectedFileNames, $fileNames);
    }

    public function testUploadFileRenamesAndUploadsFileToCorrectPath()
    {
        $uploadFileName = self::$uploadFiles[0]['fileName'];
        $uploadFilePath = self::$uploadFiles[0]['filePath'];
        $expectedFilePath = self::$uploadFiles[0]['expectedFilePath'];

        // Set 'test' param to true
        $file = new UploadedFile($uploadFilePath, $uploadFileName, null, null, null, true);

        $mmm = new MassMediaManager($this->settings);
        $mmm->uploadFile($file);

        $this->assertFileExists($expectedFilePath);

        // Rename and move upload file back to original path
        rename($expectedFilePath, $uploadFilePath);
    }

    public function testUploadFileShouldThrowExceptionIfNotInstanceOfUploadedFile()
    {
        $mmm = new MassMediaManager($this->settings);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Parameter 1 must be instance of UploadedFile');

        $mmm->uploadFile('/some/filePath.jpg');
    }

    public function testUploadFileFromUrlRenamesAndUploadsFileToCorrectPath()
    {
        $uploadFilePath = self::$uploadFiles[0]['filePath'];
        $expectedFilePath = self::$uploadFiles[0]['expectedFilePath'];

        $mmm = new MassMediaManager($this->settings);
        $mmm->uploadFileFromUrl($uploadFilePath);

        $this->assertFileExists($expectedFilePath);

        rename($expectedFilePath, $uploadFilePath);
    }

    public function testUploadFileFromUrlShouldThrowExceptionIfNotString()
    {
        $mmm = new MassMediaManager($this->settings);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Parameter 1 must be a string');

        $mmm->uploadFileFromUrl(123);
    }

    public function testUploadFilesRenamesAndUploadsFilesToCorrectPath()
    {
        $mmm = new MassMediaManager($this->settings);

        $files = array();
        $expectedResults = array();

        foreach (self::$uploadFiles as $uploadFile) {
            $files[] = new UploadedFile($uploadFile['filePath'], $uploadFile['fileName'], null, null, null, true);

            // Add expected file path to check for, and original path to rename file back to
            $expectedResults[] = array(
                'expectedPath' => $uploadFile['expectedFilePath'],
                'originalPath' => $uploadFile['filePath'],
            );
        }

        $mmm->uploadFiles($files);

        foreach ($expectedResults as $expectedResult) {
            $this->assertFileExists($expectedResult['expectedPath']);
            rename($expectedResult['expectedPath'], $expectedResult['originalPath']);
        }
    }

    public function testUploadFilesFromUrlsRenamesAndUploadsFilesToCorrectPath()
    {
        $mmm = new MassMediaManager($this->settings);

        $urls = array();
        $expectedResults = array();

        foreach (self::$uploadFiles as $uploadFile) {
            $urls[] = $uploadFile['filePath'];

            // Add expected file path to check for, and original path to rename file back to
            $expectedResults[] = array(
                'expectedPath' => $uploadFile['expectedFilePath'],
                'originalPath' => $uploadFile['filePath'],
            );
        }

        $mmm->uploadFilesFromUrls($urls);

        foreach ($expectedResults as $expectedResult) {
            $this->assertFileExists($expectedResult['expectedPath']);
            rename($expectedResult['expectedPath'], $expectedResult['originalPath']);
        }
    }

    public function testRemoveFileWithRemoveFoldersFalseShouldRemoveFileButKeepSubFolders()
    {
        $fileName = $this->testFileName;

        // Create subfolders for test file
        $currentDir = $this->settings['root_dir'] . '/../web/media';

        $subFolders = array('ab', 'cd');
        foreach ($subFolders as $subFolder) {
            $subFolderPath = $currentDir . '/' . $subFolder;
            if (!file_exists($subFolderPath)) {
                mkdir($subFolderPath);
            }
            $currentDir = $subFolderPath;
        }

        $filePath = $currentDir . '/' . $fileName;

        // Create test file to remove
        imagejpeg(imagecreatetruecolor(1, 1), $filePath);

        // Assert test file was created
        $this->assertFileExists($filePath);

        $mmm = new MassMediaManager($this->settings);
        $mmm->removeFile($fileName, false);

        // Assert test file was deleted
        $this->assertFileNotExists($filePath);

        // Assert subfolders still exist
        $this->assertFileExists($this->settings['root_dir'] . '/../web/media/ab');
        $this->assertFileExists($this->settings['root_dir'] . '/../web/media/ab/cd');
    }

    public function testRemoveFileShouldRemoveFileAndRemoveSubFolders()
    {
        $fileName = $this->testFileName;

        // Create subfolders for test file
        $currentDir = $this->settings['root_dir'] . '/../web/media';

        $subFolders = array('ab', 'cd');
        foreach ($subFolders as $subFolder) {
            $subFolderPath = $currentDir . '/' . $subFolder;
            if (!file_exists($subFolderPath)) {
                mkdir($subFolderPath);
            }
            $currentDir = $subFolderPath;
        }

        $filePath = $currentDir . '/' . $fileName;

        // Create test file to remove
        imagejpeg(imagecreatetruecolor(1, 1), $filePath);

        // Assert test file was created
        $this->assertFileExists($filePath);

        $mmm = new MassMediaManager($this->settings);
        $mmm->removeFile($fileName);

        // Assert test file was deleted
        $this->assertFileNotExists($filePath);

        // Assert subfolders were deleted
        $this->assertFileNotExists($this->settings['root_dir'] . '/../web/media/ab');
        $this->assertFileNotExists($this->settings['root_dir'] . '/../web/media/ab/cd');
    }

    public static function tearDownAfterClass()
    {
        // Remove test files and folders
        $removeFolders = array(
            __DIR__ . '/../app',
            __DIR__ . '/../web',
            __DIR__ . '/../Fixtures',
        );

        foreach ($removeFolders as $removeFolder) {
            
            if (file_exists($removeFolder)) {
                $files = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($removeFolder, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST
                );

                foreach ($files as $fileInfo) {
                    $action = ($fileInfo->isDir() ? 'rmdir' : 'unlink');
                    $action($fileInfo->getRealPath());
                }

                rmdir($removeFolder);
            }
        }
    }

}
