<?php

/*
 * This file is part of the Behat Testwork.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Testwork\Tester\Cli;

use Behat\Testwork\Cli\Controller;
use Behat\Testwork\Subject\Subjects;
use Behat\Testwork\Subject\SubjectsLocator;
use Behat\Testwork\Suite\Suite;
use Behat\Testwork\Suite\SuiteRepository;
use Behat\Testwork\Tester\ExerciseTester;
use Behat\Testwork\Tester\Result\TestResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Testwork exercise controller.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ExerciseController implements Controller
{
    /**
     * @var SuiteRepository
     */
    private $suites;
    /**
     * @var SubjectsLocator
     */
    private $locator;
    /**
     * @var ExerciseTester
     */
    private $tester;
    /**
     * @var Boolean
     */
    private $strict;
    /**
     * @var Boolean
     */
    private $skip;

    /**
     * Initializes controller.
     *
     * @param SuiteRepository $suites
     * @param SubjectsLocator $locator
     * @param ExerciseTester  $tester
     * @param Boolean         $strict
     * @param Boolean         $skip
     */
    public function __construct(
        SuiteRepository $suites,
        SubjectsLocator $locator,
        ExerciseTester $tester,
        $strict = false,
        $skip = false
    ) {
        $this->suites = $suites;
        $this->locator = $locator;
        $this->tester = $tester;
        $this->strict = $strict;
        $this->skip = $skip;
    }

    /**
     * Configures command to be executable by the controller.
     *
     * @param Command $command
     */
    public function configure(Command $command)
    {
        $command
            ->addArgument(
                'locator', InputArgument::OPTIONAL,
                'Optional path to a specific test.'
            )
            ->addOption(
                '--strict', null, InputOption::VALUE_NONE,
                'Passes only if all tests are explicitly passing.'
            )
            ->addOption(
                '--dry-run', null, InputOption::VALUE_NONE,
                'Invokes formatters without executing the steps & hooks.'
            );
    }

    /**
     * Executes controller.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return null|integer
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $subjects = $this->getSubjects($input);
        $result = $this->testSubjects($input, $subjects);

        return $this->interpretResult($input, $result);
    }

    /**
     * Loads exercise subjects.
     *
     * @param InputInterface $input
     *
     * @return Subjects[]
     */
    protected function getSubjects(InputInterface $input)
    {
        return $this->locateSubjects($this->getAvailableSuites(), $input->getArgument('locator'));
    }

    /**
     * Tests exercise subjects.
     *
     * @param InputInterface $input
     * @param Subjects[]     $subjects
     *
     * @return TestResult
     */
    protected function testSubjects(InputInterface $input, $subjects)
    {
        return $this->tester->test($subjects, $input->getOption('dry-run') || $this->skip);
    }

    /**
     * Transforms test result object into CLI code.
     *
     * @param InputInterface $input
     * @param TestResult     $result
     *
     * @return integer
     */
    protected function interpretResult(InputInterface $input, TestResult $result)
    {
        if ($this->strict || $input->getOption('strict')) {
            return intval(TestResult::PASSED < $result->getResultCode());
        }

        return intval(TestResult::FAILED === $result->getResultCode());
    }

    /**
     * Returns all currently available suites.
     *
     * @return Suite[]
     */
    protected function getAvailableSuites()
    {
        return $this->suites->getSuites();
    }

    /**
     * Locates all subjects for all suites at locator.
     *
     * @param Suite[]     $suites
     * @param null|string $locator
     *
     * @return Subjects[]
     */
    protected function locateSubjects($suites, $locator)
    {
        return $this->locator->locateSubjects($suites, $locator);
    }
}
