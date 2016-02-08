<?php

namespace Codeception\Extension;

use Symfony\Component\EventDispatcher\Event;
use Codeception\Event\FailEvent;
use Codeception\Event\StepEvent;
use Codeception\Event\SuiteEvent;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;
use Codeception\TestCase;

class TeamCity extends Extension
{
	const MESSAGE_SUITE_STARTED = 'testSuiteStarted';
	const MESSAGE_SUITE_FINISHED = 'testSuiteFinished';
	const MESSAGE_TEST_STARTED = 'testStarted';
	const MESSAGE_TEST_FAILED = 'testFailed';
	const MESSAGE_TEST_IGNORED = 'testIgnored';
	const MESSAGE_TEST_FINISHED = 'testFinished';
	const MESSAGE_COMPARISON_FAILURE = 'comparisonFailure';
	const MESSAGE = 'message';

	public static $events = [
		Events::SUITE_BEFORE => 'onSuiteStart',
		Events::SUITE_AFTER => 'onSuiteEnd',
		Events::TEST_START => 'onStart',
		Events::TEST_ERROR => 'onFail',
		Events::TEST_FAIL => 'onFail',
		Events::TEST_SKIPPED => 'onSkip',
		Events::TEST_INCOMPLETE => 'onSkip',
		Events::TEST_END => 'onEnd',
	];

	public function _initialize()
	{
		$this->options['silent'] = !$this->isCI();
	}

	protected function isCI()
	{
		// this is the only way to detect teamcity from php
		$message = "\n##teamcity[setParameter name='system.IS_THAT_U_TEAMCITY' value='yup!']";
		echo $message;
		if (getenv('IS_THAT_U_TEAMCITY') !== 'yup!') { // removing prev line in term
			echo "\r" . str_repeat(' ', strlen($message)) . "\r";
			return false;
		}
		echo "Teamcity detected!\n";
		return true;
	}

	public function onSuiteStart(SuiteEvent $e)
	{
		$this->writeTestMessage(self::MESSAGE_SUITE_STARTED, $e);
	}

	public function onStart(TestEvent $e)
	{
		$this->writeTestMessage(self::MESSAGE_TEST_STARTED, $e, [
			'captureStandardOutput' => 'true',
		]);
	}

	public function onFail(FailEvent $e)
	{
		$exception = $e->getFail();
		$params = [
			'message' => $exception->getMessage(),
			'details' => $exception->getTraceAsString(),
		];
		if ($exception instanceof \PHPUnit_Framework_ExpectationFailedException) {
			$comparisonFailure = $exception->getComparisonFailure();
			if ($comparisonFailure !== null) {
				$params += [
					'type' => self::MESSAGE_COMPARISON_FAILURE,
					'expected' => $comparisonFailure->getExpectedAsString(),
					'actual' => $comparisonFailure->getActualAsString()
				];
			}
		}
		$this->writeTestMessage(self::MESSAGE_TEST_FAILED, $e, $params);
	}

	public function onSkip(FailEvent $e)
	{
		$this->writeTestMessage(self::MESSAGE_TEST_IGNORED, $e, [
			'message' => $e->getFail()->getMessage(),
		]);
	}

	public function onEnd(TestEvent $e)
	{
		$this->writeTestMessage(self::MESSAGE_TEST_FINISHED, $e, [
			'duration' => floor($e->getTime() * 1000),
		]);
	}

	public function onSuiteEnd(SuiteEvent $e)
	{
		$this->writeTestMessage(self::MESSAGE_SUITE_FINISHED, $e);
	}

	protected function writeTestMessage($type, Event $e, array $params = [])
	{
		$params += [
			'name' => $this->getTestName($e),
			'timestamp' => $this->getTimestamp(),
			'flowId' => getenv('TEAMCITY_PROCESS_FLOW_ID')
		];
		$this->writeMessage($type, $params);
	}

	protected function writeMessage($type, $params)
	{
		$attrs = array_map(function ($k, $v) {
			return "{$k}='{$this->escapeValue($v)}'";
		}, array_keys($params), $params);
		$attrs = implode(' ', $attrs);
		$this->writeln("\n##teamcity[{$type} {$attrs}]");
	}

	protected function getTimestamp()
	{
		list($usec, $sec) = explode(' ', microtime());
		$msec = floor($usec * 1000);
		return date("Y-m-d\\TH:i:s.{$msec}O", $sec);
	}

	protected function getTestName(Event $e)
	{
		if ($e instanceof TestEvent || $e instanceof FailEvent) {
			return str_replace('/', '.', TestCase::getTestSignature($e->getTest()));
		} elseif ($e instanceof SuiteEvent) {
			return $e->getSuite()->getName();
		} else {
			return $e->getName();
		}
	}

	protected function escapeValue($string)
	{
		return strtr(trim($string), [
				'|' => '||', '\'' => '|\'',
				"\n" => '|n', "\r" => '|r',
				'[' => '|[', ']' => '|]'
			]
		);
	}
}
