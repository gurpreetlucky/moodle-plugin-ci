<?php

/*
 * This file is part of the Moodle Plugin CI package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Copyright (c) 2017 Blackboard Inc. (http://www.blackboard.com)
 * License http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace Moodlerooms\MoodlePluginCI\Tests\Command;

use Moodlerooms\MoodlePluginCI\Command\CodeCheckerCommand;
use Moodlerooms\MoodlePluginCI\Tests\Fake\Bridge\DummyMoodlePlugin;
use Moodlerooms\MoodlePluginCI\Tests\MoodleTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

class CodeCheckerCommandTest extends MoodleTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $config = ['filter' => ['notNames' => ['ignore_name.php'], 'notPaths' => ['ignore']]];
        $this->fs->dumpFile($this->pluginDir.'/.moodle-plugin-ci.yml', Yaml::dump($config));
    }

    protected function executeCommand($pluginDir = null)
    {
        if ($pluginDir === null) {
            $pluginDir = $this->pluginDir;
        }

        $command         = new CodeCheckerCommand();
        $command->plugin = new DummyMoodlePlugin($pluginDir);

        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('codechecker'));
        $commandTester->execute([
            'plugin' => $pluginDir,
        ]);

        return $commandTester;
    }

    public function testExecute()
    {
        $this->expectOutputRegex('/\.+/');
        $commandTester = $this->executeCommand();
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testExecuteNoFiles()
    {
        // Just random directory with no PHP files.
        $commandTester = $this->executeCommand($this->pluginDir.'/tests/behat');
        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertRegExp('/No relevant files found to process, free pass!/', $commandTester->getDisplay());
    }

    public function testExecuteNoPlugin()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->executeCommand('/path/to/no/plugin');
    }
}
