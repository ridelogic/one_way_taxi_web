<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_decimal_to_paise_conversion_is_exact(): void
    {
        $this->assertSame(123450, Money::toPaise('1234.50'));
        $this->assertSame(10, Money::toPaise('0.1'));
        $this->assertSame(700, Money::toPaise(7));
        $this->assertSame(1999, Money::toPaise(19.99));
        $this->assertSame('1234.50', Money::toDecimal(123450));
    }

    public function test_round_up_to_step(): void
    {
        $this->assertSame(697000, Money::roundUp(696500, 10));
        $this->assertSame(697000, Money::roundUp(697000, 10));
        $this->assertSame(559100, Money::roundUp(559010, 1));
    }

    public function test_indian_number_format(): void
    {
        $this->assertSame('₹6,970', Money::format('6970.00'));
        $this->assertSame('₹1,23,456', Money::format('123456'));
        $this->assertSame('₹12,34,567.50', Money::format('1234567.5'));
        $this->assertSame('₹999', Money::format(999));
    }

    public function test_html_sanitizer_removes_dangerous_markup(): void
    {
        $dirty = '<h2 onclick="x()">Title</h2><script>alert(1)</script><p style="color:red">Hi <a href="javascript:alert(1)">x</a> <a href="/routes" target="_blank">ok</a></p><iframe src="//evil"></iframe>';
        $clean = HtmlSanitizer::clean($dirty);

        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('style=', $clean);
        $this->assertStringNotContainsString('iframe', $clean);
        $this->assertStringContainsString('<h2>Title</h2>', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
    }
}
