<?php

namespace App\Services;

class QuestionScorer
{
    private const SCALE = 12;

    public function score(
        string $type,
        string $maximumScore,
        mixed $answer,
        ?string $multipleChoiceKey = null,
        array $items = [],
    ): array {
        if ($type === 'pilihan_ganda') {
            $answered = is_string($answer) && $answer !== '';
            $correct = $answered && strtoupper($answer) === strtoupper((string) $multipleChoiceKey);

            return [
                'score' => $correct ? $this->decimal($maximumScore) : '0.000000',
                'correct_items' => $correct ? 1 : 0,
                'item_count' => 1,
                'answered' => $answered,
                'fully_correct' => $correct,
            ];
        }

        $itemCount = count($items);
        if ($itemCount === 0) {
            return ['score' => '0.000000', 'correct_items' => 0, 'item_count' => 0, 'answered' => false, 'fully_correct' => false];
        }

        $correctItems = 0;
        $answered = false;

        if ($type === 'benar_salah') {
            $values = is_array($answer) ? $answer : [];
            foreach ($items as $item) {
                $id = (string) $item['id'];
                if (! array_key_exists($id, $values)) continue;
                $answered = true;
                $answerValue = $this->binaryValue($values[$id]);
                $correctValue = strtoupper((string) ($item['correct_value'] ?? ((bool) ($item['is_correct'] ?? false) ? 'A' : 'B')));
                if ($answerValue !== null && $answerValue === $correctValue) $correctItems++;
            }
        } else {
            $selected = collect(is_array($answer) ? $answer : [])->map(fn($id) => (string) $id);
            $answered = $selected->isNotEmpty();
            if ($answered) {
                foreach ($items as $item) {
                    $selectedValue = $selected->contains((string) $item['id']);
                    if ($selectedValue === (bool) $item['is_correct']) $correctItems++;
                }
            }
        }

        $score = $answered
            ? bcdiv(bcmul($maximumScore, (string) $correctItems, self::SCALE), (string) $itemCount, 6)
            : '0.000000';

        return [
            'score' => $score,
            'correct_items' => $correctItems,
            'item_count' => $itemCount,
            'answered' => $answered,
            'fully_correct' => $answered && $correctItems === $itemCount,
        ];
    }

    public function finalScore(string $rawScore): string
    {
        return bcdiv($rawScore, '6', 6);
    }

    public function add(string $left, string $right): string
    {
        return bcadd($left, $right, 6);
    }

    private function binaryValue(mixed $value): ?string
    {
        if (is_string($value) && in_array(strtoupper($value), ['A', 'B'], true)) return strtoupper($value);
        if (in_array($value, [true, 1, '1'], true)) return 'A';
        if (in_array($value, [false, 0, '0'], true)) return 'B';
        return null;
    }

    private function decimal(string $value): string
    {
        return bcadd($value, '0', 6);
    }
}
