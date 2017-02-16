<?php

namespace South634\MassMediaBundle\Tests\Twig;

use South634\MassMediaBundle\Twig\South634MassMediaExtension;
use South634\MassMediaBundle\Util\MassMediaManager;

class South634MassMediaExtensionTest extends \PHPUnit_Framework_TestCase
{

    protected $settings;

    public static function setUpBeforeClass()
    {
        // Create folders
        $folderNames = array('app', 'web');
        foreach ($folderNames as $folderName) {
            $folderPath = __DIR__ . '/../' . $folderName;
            if (!file_exists($folderPath)) {
                mkdir($folderPath);
            }
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
    }

    public function testGetWebPath()
    {
        $mmm = new MassMediaManager($this->settings);

        $south634MassMediaExtension = new South634MassMediaExtension($mmm);

        $fileName = 'abcdefghijklmnopqrstuvwxyz.jpg';
        $expectedWebPath = 'media/ab/cd/abcdefghijklmnopqrstuvwxyz.jpg';

        $webPath = $south634MassMediaExtension->getWebPath($fileName);

        $this->assertEquals($expectedWebPath, $webPath);
    }

    public static function tearDownAfterClass()
    {
        // Remove test files and folders
        $removeFolders = array(
            __DIR__ . '/../app',
            __DIR__ . '/../web',
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
