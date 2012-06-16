<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Filesystem\Tests;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Test class for Filesystem.
 */
class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string $workspace
     */
    private $workspace = null;

    /**
     * @var \Symfony\Component\Filesystem\Filesystem $filesystem
     */
    private $filesystem = null;

    public function setUp()
    {
        $this->filesystem = new Filesystem();
        $this->workspace = sys_get_temp_dir().DIRECTORY_SEPARATOR.time().rand(0, 1000);
        mkdir($this->workspace, 0777, true);
    }

    public function tearDown()
    {
        $this->clean($this->workspace);
    }

    /**
     * @param string $file
     */
    private function clean($file)
    {
        if (is_dir($file) && !is_link($file)) {
            $dir = new \FilesystemIterator($file);
            foreach ($dir as $childFile) {
                $this->clean($childFile);
            }

            rmdir($file);
        } else {
            unlink($file);
        }
    }

    public function testCopyCreatesNewFile()
    {
        $sourceFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_source_file';
        $targetFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_target_file';

        file_put_contents($sourceFilePath, 'SOURCE FILE');

        $this->filesystem->copy($sourceFilePath, $targetFilePath);

        $this->assertFileExists($targetFilePath);
        $this->assertEquals('SOURCE FILE', file_get_contents($targetFilePath));
    }

    public function testCopyOverridesExistingFileIfModified()
    {
        $sourceFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_source_file';
        $targetFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_target_file';

        file_put_contents($sourceFilePath, 'SOURCE FILE');
        file_put_contents($targetFilePath, 'TARGET FILE');
        touch($targetFilePath, time() - 1000);

        $this->filesystem->copy($sourceFilePath, $targetFilePath);

        $this->assertFileExists($targetFilePath);
        $this->assertEquals('SOURCE FILE', file_get_contents($targetFilePath));
    }

    public function testCopyDoesNotOverrideExistingFileByDefault()
    {
        $sourceFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_source_file';
        $targetFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_target_file';

        file_put_contents($sourceFilePath, 'SOURCE FILE');
        file_put_contents($targetFilePath, 'TARGET FILE');

        // make sure both files have the same modification time
        $modificationTime = time() - 1000;
        touch($sourceFilePath, $modificationTime);
        touch($targetFilePath, $modificationTime);

        $this->filesystem->copy($sourceFilePath, $targetFilePath);

        $this->assertFileExists($targetFilePath);
        $this->assertEquals('TARGET FILE', file_get_contents($targetFilePath));
    }

    public function testCopyOverridesExistingFileIfForced()
    {
        $sourceFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_source_file';
        $targetFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_target_file';

        file_put_contents($sourceFilePath, 'SOURCE FILE');
        file_put_contents($targetFilePath, 'TARGET FILE');

        // make sure both files have the same modification time
        $modificationTime = time() - 1000;
        touch($sourceFilePath, $modificationTime);
        touch($targetFilePath, $modificationTime);

        $this->filesystem->copy($sourceFilePath, $targetFilePath, true);

        $this->assertFileExists($targetFilePath);
        $this->assertEquals('SOURCE FILE', file_get_contents($targetFilePath));
    }

    public function testCopyCreatesTargetDirectoryIfItDoesNotExist()
    {
        $sourceFilePath = $this->workspace.DIRECTORY_SEPARATOR.'copy_source_file';
        $targetFileDirectory = $this->workspace.DIRECTORY_SEPARATOR.'directory';
        $targetFilePath = $targetFileDirectory.DIRECTORY_SEPARATOR.'copy_target_file';

        file_put_contents($sourceFilePath, 'SOURCE FILE');

        $this->filesystem->copy($sourceFilePath, $targetFilePath);

        $this->assertTrue(is_dir($targetFileDirectory));
        $this->assertFileExists($targetFilePath);
        $this->assertEquals('SOURCE FILE', file_get_contents($targetFilePath));
    }

    public function testMkdirCreatesDirectoriesRecursively()
    {
        $directory = $this->workspace
            .DIRECTORY_SEPARATOR.'directory'
            .DIRECTORY_SEPARATOR.'sub_directory';

        $result = $this->filesystem->mkdir($directory);

        $this->assertTrue($result);
        $this->assertTrue(is_dir($directory));
    }

    public function testMkdirCreatesDirectoriesFromArray()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR;
        $directories = array(
            $basePath.'1', $basePath.'2', $basePath.'3'
        );

        $result = $this->filesystem->mkdir($directories);

        $this->assertTrue($result);
        $this->assertTrue(is_dir($basePath.'1'));
        $this->assertTrue(is_dir($basePath.'2'));
        $this->assertTrue(is_dir($basePath.'3'));
    }

    public function testMkdirCreatesDirectoriesFromTraversableObject()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR;
        $directories = new \ArrayObject(array(
            $basePath.'1', $basePath.'2', $basePath.'3'
        ));

        $result = $this->filesystem->mkdir($directories);

        $this->assertTrue($result);
        $this->assertTrue(is_dir($basePath.'1'));
        $this->assertTrue(is_dir($basePath.'2'));
        $this->assertTrue(is_dir($basePath.'3'));
    }

    public function testMkdirCreatesDirectoriesEvenIfItFailsToCreateOneOfThem()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR;
        $directories = array(
            $basePath.'1', $basePath.'2', $basePath.'3'
        );

        // create a file to make that directory cannot be created
        file_put_contents($basePath.'2', '');

        $result = $this->filesystem->mkdir($directories);

        $this->assertFalse($result);
        $this->assertTrue(is_dir($basePath.'1'));
        $this->assertFalse(is_dir($basePath.'2'));
        $this->assertTrue(is_dir($basePath.'3'));
    }

    public function testTouchCreatesEmptyFile()
    {
        $file = $this->workspace.DIRECTORY_SEPARATOR.'1';

        $this->filesystem->touch($file);

        $this->assertFileExists($file);
    }

    public function testTouchCreatesEmptyFilesFromArray()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR;
        $files = array(
            $basePath.'1', $basePath.'2', $basePath.'3'
        );

        $this->filesystem->touch($files);

        $this->assertFileExists($basePath.'1');
        $this->assertFileExists($basePath.'2');
        $this->assertFileExists($basePath.'3');
    }

    public function testTouchCreatesEmptyFilesFromTraversableObject()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR;
        $files = new \ArrayObject(array(
            $basePath.'1', $basePath.'2', $basePath.'3'
        ));

        $this->filesystem->touch($files);

        $this->assertFileExists($basePath.'1');
        $this->assertFileExists($basePath.'2');
        $this->assertFileExists($basePath.'3');
    }

    public function testRemoveCleansFilesAndDirectoriesIteratively()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR.'directory'.DIRECTORY_SEPARATOR;

        mkdir($basePath);
        mkdir($basePath.'dir');
        touch($basePath.'file');

        $this->filesystem->remove($basePath);

        $this->assertTrue(!is_dir($basePath));
    }

    public function testRemoveCleansArrayOfFilesAndDirectories()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR;

        mkdir($basePath.'dir');
        touch($basePath.'file');

        $files = array(
            $basePath.'dir', $basePath.'file'
        );

        $this->filesystem->remove($files);

        $this->assertTrue(!is_dir($basePath.'dir'));
        $this->assertTrue(!is_file($basePath.'file'));
    }

    public function testRemoveCleansTraversableObjectOfFilesAndDirectories()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR;

        mkdir($basePath.'dir');
        touch($basePath.'file');

        $files = new \ArrayObject(array(
            $basePath.'dir', $basePath.'file'
        ));

        $this->filesystem->remove($files);

        $this->assertTrue(!is_dir($basePath.'dir'));
        $this->assertTrue(!is_file($basePath.'file'));
    }

    public function testRemoveIgnoresNonExistingFiles()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR;

        mkdir($basePath.'dir');

        $files = array(
            $basePath.'dir', $basePath.'file'
        );

        $this->filesystem->remove($files);

        $this->assertTrue(!is_dir($basePath.'dir'));
    }

    public function testRemoveCleansInvalidLinks()
    {
        $this->markAsSkippedIfSymlinkIsMissing();

        $basePath = $this->workspace.DIRECTORY_SEPARATOR.'directory'.DIRECTORY_SEPARATOR;

        mkdir($basePath);
        mkdir($basePath.'dir');
        // create symlink to unexisting file
        symlink($basePath.'file', $basePath.'link');

        $this->filesystem->remove($basePath);

        $this->assertTrue(!is_dir($basePath));
    }

    public function testFilesExists()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR.'directory'.DIRECTORY_SEPARATOR;

        mkdir($basePath);
        touch($basePath.'file1');
        mkdir($basePath.'folder');

        $this->assertTrue($this->filesystem->exists($basePath.'file1'));
        $this->assertTrue($this->filesystem->exists($basePath.'folder'));
    }

    public function testFilesExistsTraversableObjectOfFilesAndDirectories()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR;

        mkdir($basePath.'dir');
        touch($basePath.'file');

        $files = new \ArrayObject(array(
            $basePath.'dir', $basePath.'file'
        ));

        $this->assertTrue($this->filesystem->exists($files));
    }

    public function testFilesNotExistsTraversableObjectOfFilesAndDirectories()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR;

        mkdir($basePath.'dir');
        touch($basePath.'file');
        touch($basePath.'file2');

        $files = new \ArrayObject(array(
            $basePath.'dir', $basePath.'file', $basePath.'file2'
        ));

        unlink($basePath.'file');

        $this->assertFalse($this->filesystem->exists($files));
    }

    public function testInvalidFileNotExists()
    {
        $basePath = $this->workspace.DIRECTORY_SEPARATOR.'directory'.DIRECTORY_SEPARATOR;

        $this->assertFalse($this->filesystem->exists($basePath.time()));
    }

    public function testChmodChangesFileMode()
    {
        $this->markAsSkippedIfChmodIsMissing();

        $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
        touch($file);

        $this->filesystem->chmod($file, 0753);

        $this->assertEquals(753, $this->getFilePermisions($file));
    }

    public function testChmodAppliesUmask()
    {
        $this->markAsSkippedIfChmodIsMissing();

        $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
        touch($file);

        $this->filesystem->chmod($file, 0770, 0022);
        $this->assertEquals(750, $this->getFilePermisions($file));
    }

    public function testChmodChangesModeOfArrayOfFiles()
    {
        $this->markAsSkippedIfChmodIsMissing();

        $directory = $this->workspace.DIRECTORY_SEPARATOR.'directory';
        $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
        $files = array($directory, $file);

        mkdir($directory);
        touch($file);

        $this->filesystem->chmod($files, 0753);

        $this->assertEquals(753, $this->getFilePermisions($file));
        $this->assertEquals(753, $this->getFilePermisions($directory));
    }

    public function testChmodChangesModeOfTraversableFileObject()
    {
        $this->markAsSkippedIfChmodIsMissing();

        $directory = $this->workspace.DIRECTORY_SEPARATOR.'directory';
        $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
        $files = new \ArrayObject(array($directory, $file));

        mkdir($directory);
        touch($file);

        $this->filesystem->chmod($files, 0753);

        $this->assertEquals(753, $this->getFilePermisions($file));
        $this->assertEquals(753, $this->getFilePermisions($directory));
    }

    public function testRename()
    {
        $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
        $newPath = $this->workspace.DIRECTORY_SEPARATOR.'new_file';
        touch($file);

        $this->filesystem->rename($file, $newPath);

        $this->assertFileNotExists($file);
        $this->assertFileExists($newPath);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRenameThrowsExceptionIfTargetAlreadyExists()
    {
        $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
        $newPath = $this->workspace.DIRECTORY_SEPARATOR.'new_file';

        touch($file);
        touch($newPath);

        $this->filesystem->rename($file, $newPath);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRenameThrowsExceptionOnError()
    {
        $file = $this->workspace.DIRECTORY_SEPARATOR.uniqid();
        $newPath = $this->workspace.DIRECTORY_SEPARATOR.'new_file';

        $this->filesystem->rename($file, $newPath);
    }

    public function testSymlink()
    {
        $this->markAsSkippedIfSymlinkIsMissing();

        $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
        $link = $this->workspace.DIRECTORY_SEPARATOR.'link';

        touch($file);

        $this->filesystem->symlink($file, $link);

        $this->assertTrue(is_link($link));
        $this->assertEquals($file, readlink($link));
    }

    /**
     * @depends testSymlink
     */
    public function testRemoveSymlink()
    {
        $this->markAsSkippedIfSymlinkIsMissing();

        $link = $this->workspace.DIRECTORY_SEPARATOR.'link';

        $this->filesystem->remove($link);

        $this->assertTrue(!is_link($link));
    }

    public function testSymlinkIsOverwrittenIfPointsToDifferentTarget()
    {
        $this->markAsSkippedIfSymlinkIsMissing();

        $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
        $link = $this->workspace.DIRECTORY_SEPARATOR.'link';

        touch($file);
        symlink($this->workspace, $link);

        $this->filesystem->symlink($file, $link);

        $this->assertTrue(is_link($link));
        $this->assertEquals($file, readlink($link));
    }

    public function testSymlinkIsNotOverwrittenIfAlreadyCreated()
    {
        $this->markAsSkippedIfSymlinkIsMissing();

        $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
        $link = $this->workspace.DIRECTORY_SEPARATOR.'link';

        touch($file);
        symlink($file, $link);

        $this->filesystem->symlink($file, $link);

        $this->assertTrue(is_link($link));
        $this->assertEquals($file, readlink($link));
    }

    public function testSymlinkCreatesTargetDirectoryIfItDoesNotExist()
    {
        $this->markAsSkippedIfSymlinkIsMissing();

        $file = $this->workspace.DIRECTORY_SEPARATOR.'file';
        $link1 = $this->workspace.DIRECTORY_SEPARATOR.'dir'.DIRECTORY_SEPARATOR.'link';
        $link2 = $this->workspace.DIRECTORY_SEPARATOR.'dir'.DIRECTORY_SEPARATOR.'subdir'.DIRECTORY_SEPARATOR.'link';

        $this->filesystem->symlink($file, $link1);
        $this->filesystem->symlink($file, $link2);

        $this->assertTrue(is_link($link1));
        $this->assertEquals($file, readlink($link1));
        $this->assertTrue(is_link($link2));
        $this->assertEquals($file, readlink($link2));
    }

    /**
     * @dataProvider providePathsForMakePathRelative
     */
    public function testMakePathRelative($endPath, $startPath, $expectedPath)
    {
        $path = $this->filesystem->makePathRelative($endPath, $startPath);

        $this->assertEquals($expectedPath, $path);
    }

    /**
     * @return array
     */
    public function providePathsForMakePathRelative()
    {
        $paths = array(
            array('/var/lib/symfony/src/Symfony/', '/var/lib/symfony/src/Symfony/Component', '../'),
            array('/var/lib/symfony/src/Symfony/', '/var/lib/symfony/src/Symfony/Component/', '../'),
            array('/var/lib/symfony/src/Symfony', '/var/lib/symfony/src/Symfony/Component', '../'),
            array('/var/lib/symfony/src/Symfony', '/var/lib/symfony/src/Symfony/Component/', '../'),
            array('var/lib/symfony/', 'var/lib/symfony/src/Symfony/Component', '../../../'),
            array('/usr/lib/symfony/', '/var/lib/symfony/src/Symfony/Component', '../../../../../../usr/lib/symfony/'),
            array('/var/lib/symfony/src/Symfony/', '/var/lib/symfony/', 'src/Symfony/'),
        );

        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $paths[] = array('c:\var\lib/symfony/src/Symfony/', 'c:/var/lib/symfony/', 'src/Symfony/');
        }

        return $paths;
    }

    public function testMirrorCopiesFilesAndDirectoriesRecursively()
    {
        $sourcePath = $this->workspace.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR;
        $directory = $sourcePath.'directory'.DIRECTORY_SEPARATOR;
        $file1 = $directory.'file1';
        $file2 = $sourcePath.'file2';

        mkdir($sourcePath);
        mkdir($directory);
        file_put_contents($file1, 'FILE1');
        file_put_contents($file2, 'FILE2');

        $targetPath = $this->workspace.DIRECTORY_SEPARATOR.'target'.DIRECTORY_SEPARATOR;

        $this->filesystem->mirror($sourcePath, $targetPath);

        $this->assertTrue(is_dir($targetPath));
        $this->assertTrue(is_dir($targetPath.'directory'));
        $this->assertFileEquals($file1, $targetPath.'directory'.DIRECTORY_SEPARATOR.'file1');
        $this->assertFileEquals($file2, $targetPath.'file2');
    }

    public function testMirrorCopiesLinks()
    {
        $this->markAsSkippedIfSymlinkIsMissing();

        $sourcePath = $this->workspace.DIRECTORY_SEPARATOR.'source'.DIRECTORY_SEPARATOR;

        mkdir($sourcePath);
        file_put_contents($sourcePath.'file1', 'FILE1');
        symlink($sourcePath.'file1', $sourcePath.'link1');

        $targetPath = $this->workspace.DIRECTORY_SEPARATOR.'target'.DIRECTORY_SEPARATOR;

        $this->filesystem->mirror($sourcePath, $targetPath);

        $this->assertTrue(is_dir($targetPath));
        $this->assertFileEquals($sourcePath.'file1', $targetPath.DIRECTORY_SEPARATOR.'link1');
        $this->assertTrue(is_link($targetPath.DIRECTORY_SEPARATOR.'link1'));
    }

    /**
     * @dataProvider providePathsForIsAbsolutePath
     */
    public function testIsAbsolutePath($path, $expectedResult)
    {
        $result = $this->filesystem->isAbsolutePath($path);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function providePathsForIsAbsolutePath()
    {
        return array(
            array('/var/lib', true),
            array('c:\\\\var\\lib', true),
            array('\\var\\lib', true),
            array('var/lib', false),
            array('../var/lib', false),
            array('', false),
            array(null, false)
        );
    }

    /**
     * Returns file permissions as three digits (i.e. 755)
     *
     * @return integer
     */
    private function getFilePermisions($filePath)
    {
        return (int) substr(sprintf('%o', fileperms($filePath)), -3);
    }

    private function markAsSkippedIfSymlinkIsMissing()
    {
        if (!function_exists('symlink')) {
            $this->markTestSkipped('symlink is not supported');
        }
    }

    private function markAsSkippedIfChmodIsMissing()
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $this->markTestSkipped('chmod is not supported on windows');
        }
    }
}