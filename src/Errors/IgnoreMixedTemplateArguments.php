<?php declare(strict_types=1);

namespace Mousr\TemplatesPhpstanExtension\Errors;

use Mousr\Templates\Assert;
use Mousr\TemplatesPhpstanExtension\TemplateFileFilter;
use Override;
use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;

final readonly class IgnoreMixedTemplateArguments implements IgnoreErrorExtension {
    public function __construct(private TemplateFileFilter $templateFileFilter) {}

    #[Override]
    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool {
        if ($this->templateFileFilter->isTemplateFile($scope) === false) {
            return false;
        }

        if ($error->getIdentifier() !== 'argument.type'
            || $node instanceof Node\Expr\StaticCall === false
            || $node->class instanceof Node\Name === false
            || $node->name instanceof Node\Identifier === false) {
            return false;
        }

        return $node->class->toString() === Assert::class
            && $node->name->name === 'template';
    }
}
