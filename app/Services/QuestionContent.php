<?php

namespace App\Services;

class QuestionContent
{
    private const ALLOWED_TAGS = ['p','br','strong','b','em','i','u','s','ul','ol','li','h2','h3','h4','blockquote','sup','sub','table','thead','tbody','tr','th','td','a'];

    public function rich(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') return null;
        if (! str_contains($value, '<')) return preg_replace('/\R/u', '<br>', htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="question-root">'.$value.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $root = $dom->getElementById('question-root');
        if (! $root) return null;
        $this->sanitizeChildren($root);
        $html = '';
        foreach ($root->childNodes as $child) $html .= $dom->saveHTML($child);

        return trim($html) === '' ? null : trim($html);
    }

    public function plain(?string $value): ?string
    {
        return $this->rich($value);
    }

    private function sanitizeChildren(\DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof \DOMComment) { $parent->removeChild($node); continue; }
            if (! $node instanceof \DOMElement) continue;
            $tag = strtolower($node->tagName);
            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                if (in_array($tag, ['script','style','iframe','object','embed'], true)) { $parent->removeChild($node); continue; }
                while ($node->firstChild) $parent->insertBefore($node->firstChild, $node);
                $parent->removeChild($node); continue;
            }
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                $allowed = in_array($name, ['colspan','rowspan'], true) || ($tag === 'a' && in_array($name, ['href','target','rel'], true));
                if (! $allowed || str_starts_with($name, 'on')) $node->removeAttribute($attribute->name);
            }
            if ($tag === 'a') {
                $href = trim($node->getAttribute('href'));
                if ($href !== '' && ! preg_match('~^(https?://|mailto:|#)~i', $href)) $node->removeAttribute('href');
                if ($node->getAttribute('target') === '_blank') $node->setAttribute('rel', 'noopener noreferrer');
            }
            $this->sanitizeChildren($node);
        }
    }

    public function table(?string $value): ?array
    {
        $lines = preg_split('/\R/u', trim((string) $value)) ?: [];
        $rows = collect($lines)
            ->filter(fn ($line) => trim($line) !== '')
            ->map(fn ($line) => array_map(
                fn ($cell) => trim(strip_tags($cell)),
                explode("\t", $line)
            ))
            ->filter(fn ($row) => collect($row)->contains(fn ($cell) => $cell !== ''))
            ->values()
            ->all();

        return $rows === [] ? null : $rows;
    }
}
