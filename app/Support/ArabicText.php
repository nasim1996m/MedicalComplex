<?php

namespace App\Support;

class ArabicText
{
    /**
     * Normalize Arabic text so spelling variants match in search:
     * removes diacritics/tatweel and unifies alef, taa marbuta and yaa forms.
     */
    public static function normalize(?string $text): string
    {
        $text = (string) $text;
        $text = preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $text);
        $text = strtr($text, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
            'ة' => 'ه', 'ى' => 'ي', 'ؤ' => 'و', 'ئ' => 'ي',
        ]);
        $text = mb_strtolower($text);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}
