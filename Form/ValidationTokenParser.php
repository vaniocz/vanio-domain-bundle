<?php
namespace Vanio\DomainBundle\Form;

use Doctrine\Common\Annotations\TokenParser;
use Symfony\Bundle\FrameworkBundle\Translation\PhpStringTokenParser;
use Vanio\DomainBundle\Assert\Validation;
use Vanio\Stdlib\Strings;
use Vanio\TypeParser\UseStatementsParser;

class ValidationTokenParser implements ValidationParser
{
    /** @var UseStatementsParser */
    private $useStatementsParser;

    /** @var TokenParser */
    private $tokenParser;

    /** @var array */
    private $validationRules;

    public function __construct(UseStatementsParser $useStatementsParser = null)
    {
        $this->useStatementsParser = $useStatementsParser ?: new UseStatementsParser;
    }

    /**
     * @param string $class
     * @return array
     */
    public function parseValidationRules(string $class): array
    {
        if ($validationRules = $this->validationRules[$class] ?? null) {
            return $validationRules;
        }

        $validationAliases = $this->parseValidationAliases($class);
        $this->tokenParser = new TokenParser($this->getClassContents(new \ReflectionClass($class)));
        $this->validationRules[$class] = [];
        $lastTokens = [null, null];

        while ($token = $this->tokenParser->next()) {
            $lastTokens = [end($lastTokens), $token];

            if (!$validationClass = $validationAliases[strtolower($lastTokens[0][1] ?? null)] ?? null) {
                continue;
            } elseif ($lastTokens[1][0] === T_DOUBLE_COLON) {
                if ($validationRule = $this->parseValidationRule($validationClass)) {
                    $this->validationRules[$class][] = $validationRule;
                }
            }
        }

        return $this->validationRules[$class];
    }

    /**
     * @param string $class
     * @return array|null
     */
    private function parseValidationRule(string $class)
    {
        $method = $this->parseToken(T_STRING);

        if (!$method || !$this->parseToken('(')) {
            return null;
        }

        $reflectionMethod = new \ReflectionMethod(
            $class,
            Strings::startsWith($method, 'nullOr') ? substr($method, 6) : $method
        );
        $arguments = $this->parseFunctionArguments();
        $validationRule = ['class' => $class, 'method' => $method];

        foreach ($reflectionMethod->getParameters() as $parameter) {
            if (!$argument = $arguments[$parameter->getPosition()] ?? null) {
                continue;
            }

            switch ($parameter->name) {
                case 'message':
                    $validationRule['message'] = $this->resolveScalarToken($argument);
                    break;
                case 'propertyPath':
                    $validationRule['property_path'] = $this->resolveScalarToken($argument);
                    break;
                case 'value':
                case 'value1':
                    $validationRule['property_path'] = $this->resolveVariableNameToken($argument);
                    break;
                default:
                    try {
                        $validationRule[$parameter->name] = $this->resolveScalarToken($argument);
                    } catch (\UnexpectedValueException $e) {
                        return null;
                    }
            }
        }

        return $validationRule;
    }

    private function parseValidationAliases(string $class): array
    {
        $useStatements = $this->useStatementsParser->parseClass($class);
        $validationAliases = [];

        foreach ($useStatements as $alias => $useStatement) {
            if (is_a($useStatement, Validation::class, true)) {
                $validationAliases[$alias] = $useStatement;
            }
        }

        return $validationAliases;
    }

    private function parseFunctionArguments(): array
    {
        $arguments = [];
        $nesting = 0;
        $position = 0;

        while ($token = $this->tokenParser->next()) {
            if ($token === '(') {
                $nesting++;
            } elseif ($token === ')') {
                if (!$nesting) {
                    return $arguments;
                }

                $nesting--;
            } elseif ($token === ',' && !$nesting) {
                $position++;
                continue;
            }

            $arguments[$position][] = $token;
        }

        return $arguments;
    }

    /**
     * @param array $tokens
     * @return mixed
     * @throws \UnexpectedValueException
     */
    private function resolveScalarToken(array $tokens)
    {
        $value = null;
        $hereDoc = '';

        foreach ($tokens as $token) {
            if (!isset($token[1])) {
                break;
            }

            switch ($token[0]) {
                case T_LNUMBER:
                    return (int) $token[1];
                case T_DNUMBER:
                    return (float) $token[1];
                case T_ENCAPSED_AND_WHITESPACE:
                case T_CONSTANT_ENCAPSED_STRING:
                    $value .= $token[1];
                    break;
                case T_START_HEREDOC:
                    $hereDoc = $token[1];
                    break;
                case T_END_HEREDOC:
                    return PhpStringTokenParser::parseDocString($hereDoc, $value);
                default:
                    throw new \UnexpectedValueException(sprintf('Unexpected token %s.', token_name($token[0])));
            }
        }

        return $value === null ? null : PhpStringTokenParser::parse($value);
    }

    /**
     * @param array $tokens
     * @return string|null
     */
    private function resolveVariableNameToken(array $tokens)
    {
        if (($tokens[0][0] ?? null) !== T_VARIABLE) {
            return null;
        }

        $variableName = '';

        foreach ($tokens as $token) {
            $variableName .= $token[1];
        }

        return substr($variableName, Strings::startsWith($variableName, '$this->') ? 7 : 1);
    }

    /**
     * @param int|string|null $expectedToken
     * @return string|null
     */
    private function parseToken($expectedToken = null)
    {
        $token = $this->tokenParser->next();

        return $expectedToken !== null && ($token[0] ?? $token) !== $expectedToken
            ? null
            : $token[1] ?? $token;
    }

    private function getClassContents(\ReflectionClass $class): string
    {
        $lineNumber = $class->getStartLine();

        try {
            $file = new \SplFileObject($class->getFileName());
        } catch (\Exception $e) {
            return '';
        }

        $file->seek($lineNumber - 1);
        $contents = '<?php ';

        while ($line = $file->fgets()) {
            $contents .= $line;

            if ($lineNumber === $class->getEndLine()) {
                break;
            }

            $lineNumber++;
        }

        return $contents;
    }
}
