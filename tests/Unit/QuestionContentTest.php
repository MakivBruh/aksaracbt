<?php

namespace Tests\Unit;

use App\Services\QuestionContent;
use PHPUnit\Framework\TestCase;

class QuestionContentTest extends TestCase
{
    private QuestionContent $content;

    protected function setUp(): void
    {
        parent::setUp();
        $this->content = new QuestionContent;
    }

    public function test_bold_and_italic_are_preserved(): void
    {
        $this->assertSame('<p><strong>Tebal</strong> dan <em>miring</em></p>', $this->content->rich('<p><strong>Tebal</strong> dan <em>miring</em></p>'));
    }

    public function test_script_and_event_handlers_are_removed(): void
    {
        $result = $this->content->rich('<p onclick="alert(1)">Aman</p><script>alert(1)</script>');
        $this->assertSame('<p>Aman</p>', $result);
    }

    public function test_table_and_safe_spans_are_preserved(): void
    {
        $result = $this->content->rich('<table><tr><td colspan="2">Data</td></tr></table>');
        $this->assertStringContainsString('<table>', $result);
        $this->assertStringContainsString('colspan="2"', $result);
    }

    public function test_plain_text_old_question_remains_readable(): void
    {
        $this->assertSame('Baris satu<br>Baris dua', $this->content->rich("Baris satu\nBaris dua"));
    }

    public function test_unsafe_link_protocol_is_removed(): void
    {
        $this->assertSame('<a>klik</a>', $this->content->rich('<a href="javascript:alert(1)">klik</a>'));
    }

    public function test_blank_editor_line_is_preserved_between_paragraphs(): void
    {
        $result = $this->content->rich('<div>Paragraf pertama</div><div><br></div><div>Paragraf kedua</div>');

        $this->assertSame('<p>Paragraf pertama</p><p><br></p><p>Paragraf kedua</p>', $result);
    }
}
