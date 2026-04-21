<?php

declare(strict_types=1);

namespace miralsoft\weclapp\api\Tests\Unit\Resource;

use miralsoft\weclapp\api\Exception\AuthenticationException;
use miralsoft\weclapp\api\Exception\NotFoundException;
use miralsoft\weclapp\api\Exception\OptimisticLockException;
use miralsoft\weclapp\api\Exception\RateLimitException;
use miralsoft\weclapp\api\Exception\ServerException;
use miralsoft\weclapp\api\Exception\ValidationException;
use miralsoft\weclapp\api\Exception\WeclappApiException;
use miralsoft\weclapp\api\Util\ResponseParser;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ResponseParser.
 */
class ResponseParserTest extends TestCase
{
    public function test_parses_200_response_to_array(): void
    {
        $data   = ['id' => 'abc', 'name' => 'Test'];
        $result = ResponseParser::parse(200, json_encode($data));

        self::assertSame($data, $result);
    }

    public function test_returns_empty_array_for_204(): void
    {
        $result = ResponseParser::parse(204, '');

        self::assertSame([], $result);
    }

    public function test_throws_authentication_exception_on_401(): void
    {
        $this->expectException(AuthenticationException::class);

        ResponseParser::parse(401, '{"message":"Unauthorized"}');
    }

    public function test_throws_not_found_exception_on_404(): void
    {
        $this->expectException(NotFoundException::class);

        ResponseParser::parse(404, '{"message":"Not found"}');
    }

    public function test_throws_validation_exception_on_400(): void
    {
        $this->expectException(ValidationException::class);

        ResponseParser::parse(400, json_encode([
            'message'          => 'Validation failed',
            'validationErrors' => [['field' => 'email', 'message' => 'Invalid']],
        ]));
    }

    public function test_validation_exception_contains_errors(): void
    {
        try {
            ResponseParser::parse(400, json_encode([
                'message'          => 'Validation failed',
                'validationErrors' => [['field' => 'email', 'message' => 'Invalid format']],
            ]));
            self::fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            self::assertCount(1, $e->getErrors());
            self::assertSame('email', $e->getErrors()[0]['field']);
        }
    }

    public function test_throws_rate_limit_exception_on_429(): void
    {
        $this->expectException(RateLimitException::class);

        ResponseParser::parse(429, '{"message":"Too many requests"}');
    }

    public function test_rate_limit_parses_retry_after_header(): void
    {
        try {
            ResponseParser::parse(429, '{}', '', ['Retry-After' => '120']);
            self::fail('Expected RateLimitException.');
        } catch (RateLimitException $e) {
            self::assertSame(120, $e->getRetryAfter());
        }
    }

    public function test_throws_server_exception_on_500(): void
    {
        $this->expectException(ServerException::class);

        ResponseParser::parse(500, '{"message":"Internal Server Error"}');
    }

    public function test_throws_server_exception_on_503(): void
    {
        $this->expectException(ServerException::class);

        ResponseParser::parse(503, '');
    }

    public function test_throws_optimistic_lock_exception_on_409(): void
    {
        $this->expectException(OptimisticLockException::class);

        ResponseParser::parse(409, '{"message":"Conflict"}');
    }

    public function test_throws_on_invalid_json(): void
    {
        $this->expectException(WeclappApiException::class);
        $this->expectExceptionMessageMatches('/decode/i');

        ResponseParser::parse(200, 'not-valid-json');
    }

    public function test_extract_list_returns_result_key(): void
    {
        $items  = [['id' => '1'], ['id' => '2']];
        $result = ResponseParser::extractList(['result' => $items, 'recordCount' => 2]);

        self::assertSame($items, $result);
    }

    public function test_extract_list_returns_empty_array_for_missing_key(): void
    {
        $result = ResponseParser::extractList([]);

        self::assertSame([], $result);
    }

    public function test_extract_total_count_from_count_endpoint_v2_format(): void
    {
        // weclapp API v2 count endpoint returns {"result": 42} (integer, not array)
        $count = ResponseParser::extractTotalCount(['result' => 99]);

        self::assertSame(99, $count);
    }

    public function test_extract_total_count_from_count_endpoint_legacy_format(): void
    {
        // Legacy / alternative format {"count": 42}
        $count = ResponseParser::extractTotalCount(['count' => 99]);

        self::assertSame(99, $count);
    }

    public function test_extract_total_count_ignores_result_when_it_is_an_array(): void
    {
        // List response: {"result": [...], "recordCount": 5} — result is an array, not an integer
        $count = ResponseParser::extractTotalCount(['result' => [['id' => '1']], 'recordCount' => 5]);

        self::assertSame(5, $count);
    }

    public function test_extract_total_count_from_list_endpoint(): void
    {
        $count = ResponseParser::extractTotalCount(['result' => [], 'recordCount' => 250]);

        self::assertSame(250, $count);
    }

    public function test_extract_total_count_returns_null_when_absent(): void
    {
        $count = ResponseParser::extractTotalCount([]);

        self::assertNull($count);
    }
}
