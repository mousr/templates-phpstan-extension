<?php declare(strict_types=1);

namespace Mousr\TemplatesPhpstanExtension\Rules\Escaping;

use Mousr\Templates\Attributes\PreEscapedHTML;
use Mousr\Templates\Escaper;
use Mousr\TemplatesPhpstanExtension\TemplateFileFilter;
use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Echo_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/** @implements Rule<Echo_> */
final readonly class NoUnescapedOutputRule implements Rule {
    public function __construct(
        private TemplateFileFilter $templateFileFilter,
        private ReflectionProvider $reflectionProvider,
    ) {}

    #[Override]
    public function getNodeType(): string {
        return Echo_::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array {
        if ($this->templateFileFilter->isTemplateFile($scope) === false) {
            return [];
        }

        $errors = [];
        foreach ($node->exprs as $expr) {
            if ($this->isSafeExpression($expr, $scope)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message('All variables in HTML should be escaped by using their associated $escaper method')
                ->identifier('mousr.template.escape')
                ->line($expr->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function isSafeExpression(Node\Expr $expr, Scope $scope): bool {
        if ($expr instanceof Node\Expr\BinaryOp\Concat) {
            return $this->isSafeExpression($expr->left, $scope)
                && $this->isSafeExpression($expr->right, $scope);
        }

        if ($expr instanceof MethodCall
            && $scope->getType($expr->var)->equals(new ObjectType(Escaper::class))) {
            return true;
        }

        if ($expr instanceof Node\Expr\PropertyFetch
            && $expr->name instanceof Node\Identifier) {
            foreach ($scope->getType($expr->var)->getObjectClassNames() as $className) {
                $attributes = $this->reflectionProvider->getClass($className)
                    ->getInstanceProperty($expr->name->toString(), $scope)
                    ->getAttributes();

                if (array_any($attributes, fn(AttributeReflection $attribute) => $attribute->getName() === PreEscapedHTML::class)) {
                    return true;
                }
            }
        }

        return false;
    }
}
