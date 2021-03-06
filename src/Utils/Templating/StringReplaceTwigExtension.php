<?php

declare(strict_types=1);

namespace App\Utils\Templating;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/*
 * Class RegExpReplaceTwigExtension.
 *
 * Create a Twig function extension to replace a pattern in string.
 */
class StringReplaceTwigExtension extends AbstractExtension
{
    /**
     * Get Twig function.
     *
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'preg_replace',
                [$this, 'replace'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Replace a pattern in string.
     *
     * @param string $pattern
     * @param string $replacement
     * @param string $subject
     *
     * @return string
     */
    public function replace(string $pattern, string $replacement, string $subject): string
    {
        return preg_replace($pattern, $replacement, $subject);
    }
}
