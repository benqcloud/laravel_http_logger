<?php declare(strict_types=1);

namespace Benq\Logger\Formatter;

use Monolog\Utils;
use Throwable;

/**
 * Encodes whatever record data is passed to it as json
 *
 * This can be useful to log to databases or remote APIs
 */
class JsonFormatter extends \Monolog\Formatter\NormalizerFormatter
{
    public const BATCH_MODE_JSON = 1;
    public const BATCH_MODE_NEWLINES = 2;
    public const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    protected $batchMode;
    protected $appendNewline;
    protected $ignoreEmptyContextAndExtra;
    protected $maxMessageLength;

    /**
     * @var bool
     */
    protected $includeStacktraces = false;

    public function __construct(
        int $batchMode = self::BATCH_MODE_JSON,
        bool $appendNewline = true,
        bool $ignoreEmptyContextAndExtra = false,
        int $maxNormalizeDepth = 2,
        int $maxNormalizeItemCount = 20,
        int $maxMessageLength = 5000
    ) {
        $this->batchMode = $batchMode;
        $this->appendNewline = $appendNewline;
        $this->ignoreEmptyContextAndExtra = $ignoreEmptyContextAndExtra;
        $this->setMaxNormalizeDepth($maxNormalizeDepth);
        $this->setMaxNormalizeItemCount($maxNormalizeItemCount);
        $this->setMaxMessageLength($maxMessageLength);
    }

    /**
     * The batch mode option configures the formatting style for
     * multiple records. By default, multiple records will be
     * formatted as a JSON-encoded array. However, for
     * compatibility with some API endpoints, alternative styles
     * are available.
     */
    public function getBatchMode(): int
    {
        return $this->batchMode;
    }

    /**
     * True if newlines are appended to every formatted record
     */
    public function isAppendingNewlines(): bool
    {
        return $this->appendNewline;
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record): string
    {
        $normalized = $this->normalize($record);

        if (isset($normalized['context']) && $normalized['context'] === []) {
            if ($this->ignoreEmptyContextAndExtra) {
                unset($normalized['context']);
            } else {
                $normalized['context'] = new \stdClass;
            }
        }
        if (isset($normalized['extra']) && $normalized['extra'] === []) {
            if ($this->ignoreEmptyContextAndExtra) {
                unset($normalized['extra']);
            } else {
                $normalized['extra'] = new \stdClass;
            }
        }

        return $this->toJson($normalized, true) . ($this->appendNewline ? "\n" : '');
    }

    /**
     * {@inheritdoc}
     */
    public function formatBatch(array $records): string
    {
        switch ($this->batchMode) {
            case static::BATCH_MODE_NEWLINES:
                return $this->formatBatchNewlines($records);

            case static::BATCH_MODE_JSON:
            default:
                return $this->formatBatchJson($records);
        }
    }

    public function setMaxMessageLength(int $maxMessageLength)
    {
        $this->maxMessageLength = $maxMessageLength;
    }

    public function includeStacktraces(bool $include = true)
    {
        $this->includeStacktraces = $include;
    }

    /**
     * Return a JSON-encoded array of records.
     */
    protected function formatBatchJson(array $records): string
    {
        return $this->toJson($this->normalize($records), true);
    }

    /**
     * Use new lines to separate records instead of a
     * JSON-encoded array.
     */
    protected function formatBatchNewlines(array $records): string
    {
        $instance = $this;

        $oldNewline = $this->appendNewline;
        $this->appendNewline = false;
        array_walk($records, function (&$value, $key) use ($instance) {
            $value = $instance->format($value);
        });
        $this->appendNewline = $oldNewline;

        return implode("\n", $records);
    }

    /**
     * Normalizes given $data.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    protected function normalize($data, int $depth = 0)
    {
        if (is_string($data)) {
            $length = strlen($data);
            if ($length > $this->maxMessageLength) {
                return substr($data, 0, $this->maxMessageLength) . ', Over ' . $this->maxMessageLength . ' (' . $length . ' total), abandon message';
            }
        }

        if (is_object($data)) {
            if ($data instanceof Throwable) {
                $value = $this->normalizeException($data, $depth);
            } elseif ($data instanceof \JsonSerializable) {
                $value = $data->jsonSerialize();
            } elseif (method_exists($data, '__toString')) {
                $value = $data->__toString();
            } else {
                // the rest is normalized by json encoding and decoding it
                $encoded = $this->toJson($data, true);
                if ($encoded === false) {
                    $value = 'JSON_ERROR';
                } else {
                    $value = json_decode($encoded, true);
                }
            }

            return $this->normalize($value, $depth + 1);
        }

        if (is_array($data)) {
            if ($depth > $this->maxNormalizeDepth) {
                return $this->normalize(Utils::jsonEncode($data, Utils::DEFAULT_JSON_FLAGS, true));
            }

            $normalized = [];

            $count = 1;
            foreach ($data as $key => $value) {
                if ($count++ > $this->maxNormalizeItemCount) {
                    $normalized['Over'] = 'Over ' . $this->maxNormalizeItemCount . ' items (' . count($data) . ' total), aborting normalization';
                    break;
                }

                $normalized[$key] = $this->normalize($value, $depth + 1);
            }

            return $normalized;
        }

        if (is_resource($data)) {
            return parent::normalize($data, $depth);
        }

        return $data;
    }

    /**
     * Normalizes given exception with or without its own stack trace based on
     * `includeStacktraces` property.
     */
    protected function normalizeException(Throwable $e, int $depth = 0): array
    {
        $data = parent::normalizeException($e, $depth);
        if (!$this->includeStacktraces) {
            unset($data['trace']);
        }

        return $data;
    }
}
