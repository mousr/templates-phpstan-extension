<?php declare(strict_types=1);

namespace Mousr\TemplatesPhpstanRules\Rules\Escaping;

use Mousr\Templates\Escaper;
use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Echo_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/** @implements Rule<Echo_> */
class NoUnescapedOutputRule implements Rule {
    /** @param list<string> $templateExtensions */
    public function __construct(private array $templateExtensions) {}

    #[Override]
    public function getNodeType(): string {
        return Echo_::class;
    }

    #[Override]
    public function processNode(Node $node, Scope $scope): array {
        $extension = $scope->getFileDescription()
                |> basename(...)
                |> (fn($x) => strstr($x, '.'));
        if ($extension === false) {
            return [];
        }

        if (in_array(ltrim($extension, '.'), $this->templateExtensions, true) === false) {
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

        return false;
    }
}
