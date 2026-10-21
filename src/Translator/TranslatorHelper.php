<?php
/*
 * This file is part of the Sidus/AdminBundle package.
 *
 * Copyright (c) 2015-2023 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\AdminBundle\Translator;

use Sidus\BaseBundle\Utilities\TranslatorUtility;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Used to try multiple translations with fallback
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class TranslatorHelper
{
    public function __construct(
        protected TranslatorInterface $translator,
    ) {
    }

    /**
     * Will check the translator for the provided keys and humanize the code if no translation is found
     */
    public function tryTranslate(
        string|array $tIds,
        array $parameters = [],
        ?string $fallback = null,
        bool $humanizeFallback = true,
    ): ?string {
        foreach ((array) $tIds as $tId) {
            try {
                if ($this->translator instanceof TranslatorBagInterface) {
                    if ($this->translator->getCatalogue()->has($tId)) {
                        return $this->translator->trans($tId, $parameters);
                    }
                } elseif ($this->translator instanceof TranslatorInterface) {
                    $label = $this->translator->trans($tId, $parameters);
                    if ($label !== $tId) {
                        return $label;
                    }
                }
            } catch (\InvalidArgumentException) {
                // Do nothing
            }
        }

        if (null === $fallback) {
            return null;
        }
        if (!$humanizeFallback) {
            return $fallback;
        }
        $pattern = '/(?!^)[A-Z]{2,}(?=[A-Z][a-z])|[A-Z][a-z]|\d{1,}/';

        return str_replace('_', ' ', preg_replace($pattern, ' $0', $fallback));
    }
}
