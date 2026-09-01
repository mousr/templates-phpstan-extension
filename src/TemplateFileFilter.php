<?php declare(strict_types=1);

namespace Mousr\TemplatesPhpstanExtension;

use PHPStan\Analyser\Scope;

final readonly class TemplateFileFilter {
    /** @param list<string> $templateExtensions */
    public function __construct(private array $templateExtensions) {}

    public function isTemplateFile(Scope $scope): bool {
        $extension = $scope->getFileDescription()
                |> basename(...)
                |> (fn($x) => strstr($x, '.'));
        if ($extension === false) {
            return false;
        }

        return in_array(
            ltrim($extension, '.'),
            $this->templateExtensions,
            true,
        );
    }
}
