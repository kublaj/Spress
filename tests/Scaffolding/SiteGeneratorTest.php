<?php

/*
 * This file is part of the Yosymfony\Spress.
 *
 * (c) YoSymfony <http://github.com/yosymfony>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Yosymfony\Spress\tests\Scaffolding;

use Symfony\Component\Filesystem\Filesystem;
use Yosymfony\EmbeddedComposer\EmbeddedComposerBuilder;
use Yosymfony\Spress\IO\BufferIO;
use Yosymfony\Spress\PackageManager\PackageManager;
use Yosymfony\Spress\Scaffolding\SiteGenerator;

class SiteGeneratorTest extends \PHPUnit_Framework_TestCase
{
    protected $skeletonDir;
    protected $tmpDir;

    public function setUp()
    {
        $this->tmpDir = sys_get_temp_dir().'/spress-tests';
        $this->skeletonDir = [__DIR__.'/../../app/skeletons'];
    }

    public function tearDown()
    {
        $fs = new FileSystem();
        $fs->remove($this->tmpDir);
    }

    public function testNewSiteBlank()
    {
        $generator = new SiteGenerator();
        $generator->setSkeletonDirs($this->skeletonDir);
        $generator->generate($this->tmpDir, 'blank');

        $this->assertFileExists($this->tmpDir.'/config.yml');
        $this->assertFileExists($this->tmpDir.'/composer.json');
        $this->assertFileExists($this->tmpDir.'/src/content/index.html');
        $this->assertFileExists($this->tmpDir.'/src/content/assets');
        $this->assertFileExists($this->tmpDir.'/src/content/posts');
        $this->assertFileExists($this->tmpDir.'/src/layouts');
        $this->assertFileExists($this->tmpDir.'/src/includes');
        $this->assertFileExists($this->tmpDir.'/src/plugins');
    }

    public function testNewSiteExistsEmptyDir()
    {
        $fs = new FileSystem();
        $fs->mkdir($this->tmpDir);

        $this->assertFileExists($this->tmpDir);

        $generator = new SiteGenerator();
        $generator->setSkeletonDirs($this->skeletonDir);
        $generator->generate($this->tmpDir, 'blank');

        $this->assertFileExists($this->tmpDir.'/config.yml');
        $this->assertFileExists($this->tmpDir.'/composer.json');
        $this->assertFileExists($this->tmpDir.'/src/content/index.html');
        $this->assertFileExists($this->tmpDir.'/src/content/assets');
        $this->assertFileExists($this->tmpDir.'/src/content/posts');
        $this->assertFileExists($this->tmpDir.'/src/layouts');
        $this->assertFileExists($this->tmpDir.'/src/includes');
        $this->assertFileExists($this->tmpDir.'/src/plugins');
    }

    public function testNewSiteBlankForce()
    {
        $generator = new SiteGenerator();
        $generator->setSkeletonDirs($this->skeletonDir);
        $generator->generate($this->tmpDir, 'blank');
        $generator->generate($this->tmpDir, 'blank', true);

        $this->assertFileExists($this->tmpDir.'/config.yml');
        $this->assertFileExists($this->tmpDir.'/composer.json');
        $this->assertFileExists($this->tmpDir.'/src/content/index.html');
        $this->assertFileExists($this->tmpDir.'/src/content/assets');
        $this->assertFileExists($this->tmpDir.'/src/content/posts');
        $this->assertFileExists($this->tmpDir.'/src/layouts');
        $this->assertFileExists($this->tmpDir.'/src/includes');
        $this->assertFileExists($this->tmpDir.'/src/plugins');
    }

    /**
     * @group net
     * @large
     */
    public function testSpressoTheme()
    {
        $autoloaders = spl_autoload_functions();
        $composerClassloader = $autoloaders[0][0];
        $builder = new EmbeddedComposerBuilder($composerClassloader, $this->tmpDir);
        $embeddedComposer = $builder->setComposerFilename('composer.json')
            ->setVendorDirectory('vendor')
            ->build();
        $embeddedComposer->processAdditionalAutoloads();

        $io = new BufferIO();
        $packageManager = new PackageManager($embeddedComposer, $io);

        $generator = new SiteGenerator($packageManager);
        $generator->setSkeletonDirs($this->skeletonDir);
        $generator->generate($this->tmpDir, 'spress/spress-theme-spresso:2.1.*-dev');

        $this->assertFileExists($this->tmpDir.'/config.yml');
        $this->assertFileExists($this->tmpDir.'/composer.json');
        $this->assertFileExists($this->tmpDir.'/src/themes/spress/spress-theme-spresso/config.yml');
        $this->assertFileExists($this->tmpDir.'/src/content/index.html');
        $this->assertFileExists($this->tmpDir.'/src/content/assets');
        $this->assertFileExists($this->tmpDir.'/src/content/posts');
        $this->assertFileExists($this->tmpDir.'/src/layouts');
        $this->assertFileExists($this->tmpDir.'/src/includes');
        $this->assertFileExists($this->tmpDir.'/src/plugins');
        $this->assertRegExp(
            '/name: spress\/spress-theme-spresso/',
            file_get_contents($this->tmpDir.'/config.yml')
        );
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The name of the theme cannot be empty.
     */
    public function testEmptyTheme()
    {
        $generator = new SiteGenerator();
        $generator->setSkeletonDirs($this->skeletonDir);
        $generator->generate($this->tmpDir, '');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The theme: "vendor-name/foo" does not exist at registered repositories.
     */
    public function testNotFoundTheme()
    {
        $stubPackageManager = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubPackageManager->method('existPackage')
            ->willReturn(false);

        $generator = new SiteGenerator($stubPackageManager);
        $generator->setSkeletonDirs($this->skeletonDir);
        $generator->generate($this->tmpDir, 'vendor-name/foo');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The theme: "vendor-name/foo" is not a Spress theme.
     */
    public function testNotSpressTheme()
    {
        $stubPackageManager = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stubPackageManager->method('isThemePackage')
            ->willReturn(false);

        $generator = new SiteGenerator($stubPackageManager);
        $generator->setSkeletonDirs($this->skeletonDir);
        $generator->generate($this->tmpDir, 'vendor-name/foo');
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage You must set the PackageManager at the constructor in order to create non-blank themes.
     */
    public function testNewSiteWithTemplateAndNoPackageManagerTest()
    {
        $generator = new SiteGenerator();
        $generator->setSkeletonDirs($this->skeletonDir);
        $generator->generate($this->tmpDir, 'template-test');

        $this->assertFileExists($this->tmpDir.'/config.yml');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testNewSiteBlankNoForce()
    {
        $generator = new SiteGenerator();
        $generator->setSkeletonDirs($this->skeletonDir);
        $generator->generate($this->tmpDir, 'blank');
        $generator->generate($this->tmpDir, 'blank', false);
    }
}
