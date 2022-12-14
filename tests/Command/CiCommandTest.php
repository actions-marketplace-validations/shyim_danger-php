<?php
declare(strict_types=1);

namespace Danger\Tests\Command;

use Danger\Command\CiCommand;
use Danger\ConfigLoader;
use Danger\Platform\Github\Github;
use Danger\Platform\PlatformDetector;
use Danger\Renderer\HTMLRenderer;
use Danger\Runner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @internal
 */
class CiCommandTest extends TestCase
{
    public function testValid(): void
    {
        $platform = $this->createMock(Github::class);
        $platform->expects(static::once())->method('removePost');

        $detector = $this->createMock(PlatformDetector::class);
        $detector->method('detect')->willReturn($platform);

        $output = new BufferedOutput();

        $cmd = new CiCommand($detector, new ConfigLoader(), new Runner(), new HTMLRenderer());
        $returnCode = $cmd->run(new ArgvInput(['danger', '--config=' . \dirname(__DIR__) . '/configs/empty.php']), $output);

        static::assertSame(Command::SUCCESS, $returnCode);
        static::assertStringContainsString('Looks good!', $output->fetch());
    }

    public function testNotValid(): void
    {
        $platform = $this->createMock(Github::class);
        $platform->method('post')->willReturn('https://danger.local/test');

        $detector = $this->createMock(PlatformDetector::class);
        $detector->method('detect')->willReturn($platform);
        $output = new BufferedOutput();

        $cmd = new CiCommand($detector, new ConfigLoader(), new Runner(), new HTMLRenderer());
        $returnCode = $cmd->run(new ArgvInput(['danger', '--config=' . \dirname(__DIR__) . '/configs/all.php']), $output);

        static::assertSame(Command::FAILURE, $returnCode);
        static::assertStringContainsString('The comment has been created at https://danger.local/test', $output->fetch());
    }

    public function testNotValidWarning(): void
    {
        $platform = $this->createMock(Github::class);
        $platform->method('post')->willReturn('https://danger.local/test');

        $detector = $this->createMock(PlatformDetector::class);
        $detector->method('detect')->willReturn($platform);
        $output = new BufferedOutput();

        $cmd = new CiCommand($detector, new ConfigLoader(), new Runner(), new HTMLRenderer());
        $returnCode = $cmd->run(new ArgvInput(['danger', '--config=' . \dirname(__DIR__) . '/configs/warning.php']), $output);

        static::assertSame(0, $returnCode);
        static::assertStringContainsString('The comment has been created at https://danger.local/test', $output->fetch());
    }

    public function testInvalidConfig(): void
    {
        $platform = $this->createMock(Github::class);
        $platform->method('post')->willReturn('https://danger.local/test');

        $detector = $this->createMock(PlatformDetector::class);
        $detector->method('detect')->willReturn($platform);

        $cmd = new CiCommand($detector, new ConfigLoader(), new Runner(), new HTMLRenderer());

        static::expectException(\InvalidArgumentException::class);
        static::expectExceptionMessage('Invalid config option given');

        $input = new ArgvInput([]);
        $input->bind($cmd->getDefinition());
        $input->setOption('config', []);

        $cmd->execute($input, new NullOutput());
    }
}
