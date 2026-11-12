<?php

namespace App\Services;

class ProblemStatementSanitizer
{
    private const ALLOWED_TAGS = [
        'a', 'b', 'blockquote', 'br', 'caption', 'code', 'dd', 'div', 'dl', 'dt', 'em',
        'h1', 'h2', 'h3', 'h4', 'hr', 'i', 'li', 'math', 'mi', 'mn', 'mo', 'mrow', 'msup',
        'msub', 'mfrac', 'mtext', 'ol', 'p', 'pre', 's', 'span', 'strong', 'sub', 'sup',
        'table', 'tbody', 'td', 'th', 'thead', 'tr', 'u', 'ul',
    ];

    private const DROP_TAGS = ['iframe', 'object', 'script', 'style', 'svg', 'template'];

    public function sanitize(string $html): string
    {
        for ($decodePass = 0; $decodePass < 2; $decodePass++) {
            $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $html) {
                break;
            }
            $html = $decoded;
        }

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->loadHTML('<?xml encoding="UTF-8"><div id="statement-root">'.$html.'</div>');
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = (new \DOMXPath($document))->query('//*[@id="statement-root"]')?->item(0);
        if (! $root instanceof \DOMElement) {
            return '';
        }

        $this->cleanChildren($root);
        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return trim($result);
    }

    private function cleanChildren(\DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $child) {
            if (! $child instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP_TAGS, true)) {
                $parent->removeChild($child);

                continue;
            }

            $this->cleanChildren($child);
            if ($tag === 'p' && trim(str_replace("\xc2\xa0", '', $child->textContent)) === '') {
                $parent->removeChild($child);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                while ($child->firstChild !== null) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);

                continue;
            }

            while ($child->attributes->length > 0) {
                $child->removeAttributeNode($child->attributes->item(0));
            }
        }
    }
}
