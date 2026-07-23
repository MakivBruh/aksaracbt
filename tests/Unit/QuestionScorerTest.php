<?php

namespace Tests\Unit;

use App\Services\QuestionScorer;
use PHPUnit\Framework\TestCase;

class QuestionScorerTest extends TestCase
{
    private QuestionScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new QuestionScorer;
    }

    public function test_correct_mandatory_multiple_choice_scores_five(): void
    {
        $result = $this->scorer->score('pilihan_ganda', '5', 'B', 'B');
        $this->assertSame('5.000000', $result['score']);
    }

    public function test_correct_elective_multiple_choice_scores_ten(): void
    {
        $result = $this->scorer->score('pilihan_ganda', '10', 'A', 'A');
        $this->assertSame('10.000000', $result['score']);
    }

    public function test_five_correct_mandatory_sub_items_score_five(): void
    {
        $items = $this->items([true, false, true, false, true]);
        $answer = ['1' => true, '2' => false, '3' => true, '4' => false, '5' => true];
        $result = $this->scorer->score('benar_salah', '5', $answer, null, $items);
        $this->assertSame('5.000000', $result['score']);
    }

    public function test_three_of_four_elective_sub_items_score_seven_point_five(): void
    {
        $items = $this->items([true, true, true, false]);
        $result = $this->scorer->score('pilih_semua', '10', ['1', '2'], null, $items);
        $this->assertSame('7.500000', $result['score']);
    }

    public function test_empty_answer_scores_zero(): void
    {
        $result = $this->scorer->score('pilih_semua', '10', [], null, $this->items([true, false]));
        $this->assertSame('0.000000', $result['score']);
        $this->assertFalse($result['answered']);
    }

    public function test_six_hundred_raw_points_convert_to_one_hundred(): void
    {
        $this->assertSame('100.000000', $this->scorer->finalScore('600'));
    }

    public function test_scoring_twice_returns_same_result_instead_of_accumulating(): void
    {
        $items = $this->items([true, false, true]);
        $first = $this->scorer->score('benar_salah', '5', ['1' => true, '2' => false, '3' => true], null, $items);
        $second = $this->scorer->score('benar_salah', '5', ['1' => true, '2' => false, '3' => true], null, $items);
        $this->assertSame($first, $second);
        $this->assertSame('5.000000', $second['score']);
    }

    public function test_decimal_is_not_rounded_per_sub_item_too_early(): void
    {
        $items = $this->items([true, true, true]);
        $partial = $this->scorer->score('benar_salah', '5', ['1' => true, '2' => true], null, $items);
        $full = $this->scorer->score('benar_salah', '5', ['1' => true, '2' => true, '3' => true], null, $items);
        $this->assertSame('3.333333', $partial['score']);
        $this->assertSame('5.000000', $full['score']);
    }

    public function test_stable_a_answer_matches_a_key_regardless_of_label(): void
    {
        $result = $this->scorer->score('benar_salah', '5', ['1' => 'A'], null, [['id' => 1, 'correct_value' => 'A', 'is_correct' => true]]);
        $this->assertSame('5.000000', $result['score']);
    }

    public function test_stable_b_answer_is_wrong_when_key_is_a(): void
    {
        $result = $this->scorer->score('benar_salah', '5', ['1' => 'B'], null, [['id' => 1, 'correct_value' => 'A', 'is_correct' => true]]);
        $this->assertSame('0.000000', $result['score']);
    }

    public function test_legacy_boolean_answers_remain_compatible(): void
    {
        $result = $this->scorer->score('benar_salah', '5', ['1' => true, '2' => false], null, $this->items([true, false]));
        $this->assertSame('5.000000', $result['score']);
    }

    private function items(array $keys): array
    {
        return collect($keys)->map(fn($key, $index) => ['id' => $index + 1, 'is_correct' => $key])->all();
    }
}
