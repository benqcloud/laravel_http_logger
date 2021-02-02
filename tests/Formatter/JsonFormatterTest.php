<?php declare(strict_types=1);

namespace Benq\Logger\Formatter;

use Monolog\Test\TestCase;
use Monolog\Utils;
use PragmaRX\Random\Random;

class JsonFormatterTest extends TestCase
{
    public function testConstruct()
    {
        $formatter = new JsonFormatter();
        $this->assertEquals(JsonFormatter::BATCH_MODE_JSON, $formatter->getBatchMode());
        $this->assertEquals(true, $formatter->isAppendingNewlines());
        $formatter = new JsonFormatter(JsonFormatter::BATCH_MODE_NEWLINES, false);
        $this->assertEquals(JsonFormatter::BATCH_MODE_NEWLINES, $formatter->getBatchMode());
        $this->assertEquals(false, $formatter->isAppendingNewlines());
    }

    public function testFormat()
    {
        $formatter = new JsonFormatter();
        $record = $this->getRecord();
        $record['context'] = $record['extra'] = new \stdClass;
        $this->assertEquals(json_encode($record) . "\n", $formatter->format($record));

        $formatter = new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, false);
        $record = $this->getRecord();
        $this->assertEquals('{"message":"test","context":{},"level":300,"level_name":"WARNING","channel":"test","datetime":"' . $record['datetime']->format('Y-m-d\TH:i:s.uP') . '","extra":{}}', $formatter->format($record));
    }

    public function testArrayDepth()
    {
        $formatter = new JsonFormatter();
        // $formatter->setMaxNormalizeDepth(2);
        $record = $this->getRecord();

        $record['context'] = [
            'level1' => 'level1',
        ];

        $this->assertEquals('{"message":"test","context":{"level1":"level1"},"level":300,"level_name":"WARNING","channel":"test","datetime":"' . $record['datetime']->format('Y-m-d\TH:i:s.uP') . '","extra":{}}' . "\n", $formatter->format($record));

        $record['context'] = [
            'level1' => 'level1',
            'level1_obj' => [
                'level2' => 'level2'
            ]
        ];

        $this->assertEquals('{"message":"test","context":{"level1":"level1","level1_obj":{"level2":"level2"}},"level":300,"level_name":"WARNING","channel":"test","datetime":"' . $record['datetime']->format('Y-m-d\TH:i:s.uP') . '","extra":{}}' . "\n", $formatter->format($record));

        $record['context'] = [
            'level1' => 'level1',
            'level1_obj' => [
                'level2' => 'level2',
                'level2_obj' => [
                    'level3' => 'level3'
                ]
            ]
        ];

        $this->assertEquals('{"message":"test","context":{"level1":"level1","level1_obj":{"level2":"level2","level2_obj":"{\"level3\":\"level3\"}"}},"level":300,"level_name":"WARNING","channel":"test","datetime":"' . $record['datetime']->format('Y-m-d\TH:i:s.uP') . '","extra":{}}' . "\n", $formatter->format($record));
    }

    public function testMaxMessageLength()
    {
        $formatter = new JsonFormatter();
        $maxlength = 50;
        $formatter->setMaxMessageLength($maxlength);
        $record = $this->getRecord();

        $random = new Random();
        $strlen = 60;
        $string = $random->size($strlen)->get();

        $record['context'] = [
            'level1' => $string
        ];

        $abandonMessage = substr($string, 0, $maxlength) . ', Over ' . $maxlength . ' (' . $strlen . ' total), abandon message';

        $this->assertEquals('{"message":"test","context":{"level1":"' . $abandonMessage . '"},"level":300,"level_name":"WARNING","channel":"test","datetime":"' . $record['datetime']->format('Y-m-d\TH:i:s.uP') . '","extra":{}}' . "\n", $formatter->format($record));

        $formatter->setMaxNormalizeDepth(2);

        $record['context'] = [
            'level1' => $string,
            'level1_obj' => [
                'level2' => $string,
                'level2_obj' => [
                    'level3' => $string
                ]
            ]
        ];

        $abandonLevelStr = json_encode(['level3' => $string]);
        $abandonLevelStrlen = strlen($abandonLevelStr);
        $abandonLevelStr = json_encode(substr(json_encode(['level3' => $string]), 0, $maxlength));
        $abandonLevelMessage = substr($abandonLevelStr, 0, strlen($abandonLevelStr) - 1) . ', Over ' . $maxlength . ' (' . $abandonLevelStrlen . ' total), abandon message';

        $this->assertEquals('{"message":"test","context":{"level1":"' . $abandonMessage . '","level1_obj":{"level2":"' . $abandonMessage . '","level2_obj":' . $abandonLevelMessage . '"}},"level":300,"level_name":"WARNING","channel":"test","datetime":"' . $record['datetime']->format('Y-m-d\TH:i:s.uP') . '","extra":{}}' . "\n", $formatter->format($record));
    }

    public function testDefFormatWithException()
    {
        $formatter = new JsonFormatter();
        $exception = new \RuntimeException('Foo');
        $formattedException = $this->formatException($exception);

        $message = $this->formatRecordWithExceptionInContext($formatter, $exception);

        $this->assertContextContainsFormattedException(Utils::jsonEncode($formattedException, Utils::DEFAULT_JSON_FLAGS, true), $message);
    }

    public function testDefFormatWithPreviousException()
    {
        $formatter = new JsonFormatter();
        $exception = new \RuntimeException('Foo', 0, new \LogicException('Wut?'));
        $formattedPrevException = $this->formatException($exception->getPrevious());
        $formattedException = $this->formatException($exception, $formattedPrevException);

        $message = $this->formatRecordWithExceptionInContext($formatter, $exception);

        $this->assertContextContainsFormattedException(Utils::jsonEncode($formattedException, Utils::DEFAULT_JSON_FLAGS, true), $message);
    }

    public function testDefFormatWithThrowable()
    {
        $formatter = new JsonFormatter();
        $throwable = new \Error('Foo');
        $formattedThrowable = $this->formatException($throwable);

        $message = $this->formatRecordWithExceptionInContext($formatter, $throwable);

        $this->assertContextContainsFormattedException(Utils::jsonEncode($formattedThrowable, Utils::DEFAULT_JSON_FLAGS, true), $message);
    }

    public function testMaxNormalizeDepth()
    {
        $formatter = new JsonFormatter();
        $formatter->setMaxNormalizeDepth(3);
        $throwable = new \Error('Foo');
        $formattedThrowable = $this->formatException($throwable);

        $message = $this->formatRecordWithExceptionInContext($formatter, $throwable);

        $this->assertContextContainsFormattedException($formattedThrowable, $message);
    }

    public function testMaxNormalizeItemCountWith0ItemsMax()
    {
        $formatter = new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true);
        $formatter->setMaxNormalizeDepth(9);
        $formatter->setMaxNormalizeItemCount(0);
        $throwable = new \Error('Foo');

        $message = $this->formatRecordWithExceptionInContext($formatter, $throwable);

        $this->assertEquals(
            '{"Over":"Over 0 items (6 total), aborting normalization"}' . "\n",
            $message
        );
    }

    public function testMaxNormalizeItemCountWith2ItemsMax()
    {
        $formatter = new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true);
        $formatter->setMaxNormalizeDepth(9);
        $formatter->setMaxNormalizeItemCount(2);
        $throwable = new \Error('Foo');

        $message = $this->formatRecordWithExceptionInContext($formatter, $throwable);

        $this->assertEquals(
            '{"level_name":"CRITICAL","channel":"core","Over":"Over 2 items (6 total), aborting normalization"}' . "\n",
            $message
        );
    }

    public function testDefFormatWithResource()
    {
        $formatter = new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, false);
        $record = $this->getRecord();
        $record['context'] = ['field_resource' => opendir(__DIR__)];
        $this->assertEquals('{"message":"test","context":{"field_resource":"[resource(stream)]"},"level":300,"level_name":"WARNING","channel":"test","datetime":"' . $record['datetime']->format('Y-m-d\TH:i:s.uP') . '","extra":{}}', $formatter->format($record));
    }

    /**
     * @param string $expected
     * @param string $actual
     *
     * @internal param string $exception
     */
    private function assertContextContainsFormattedException($expected, $actual)
    {
        $this->assertEquals(
            '{"level_name":"CRITICAL","channel":"core","context":{"exception":' . $expected . '},"datetime":null,"extra":{},"message":"foobar"}' . "\n",
            $actual
        );
    }

    /**
     * @param JsonFormatter $formatter
     * @param \Throwable    $exception
     *
     * @return string
     */
    private function formatRecordWithExceptionInContext(JsonFormatter $formatter, $exception)
    {
        $message = $formatter->format([
            'level_name' => 'CRITICAL',
            'channel' => 'core',
            'context' => ['exception' => $exception],
            'datetime' => null,
            'extra' => [],
            'message' => 'foobar',
        ]);

        return $message;
    }

    /**
     * @param \Exception|\Throwable $exception
     *
     * @return string
     */
    private function formatExceptionFilePathWithLine($exception)
    {
        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $path = substr(json_encode($exception->getFile(), $options), 1, -1);

        return $path . ':' . $exception->getLine();
    }

    /**
     * @param \Exception|\Throwable $exception
     *
     * @param null|string $previous
     *
     * @return string
     */
    private function formatException($exception, $previous = null)
    {
        $formattedException =
            '{"class":"' . get_class($exception) .
            '","message":"' . $exception->getMessage() .
            '","code":' . $exception->getCode() .
            ',"file":"' . $this->formatExceptionFilePathWithLine($exception) .
            ($previous ? '","previous":' . $previous : '"') .
            '}';

        return $formattedException;
    }
}
