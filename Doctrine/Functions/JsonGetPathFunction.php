<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class JsonGetPathFunction extends FunctionNode
{
    /** @var Node */
    private $json;

    /** @var Node[] */
    private $path;

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->json = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->path = $this->parsePath($parser);
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            '(%s#>>%s)',
            $this->json->dispatch($sqlWalker),
            $sqlWalker->walkStringPrimary(sprintf('{%s}', implode(', ', $this->path)))
        );
    }

    private function parsePath(Parser $parser): array
    {
        $path = [];

        while (true) {
            if (!$parser->getLexer()->isNextTokenAny([Lexer::T_STRING, Lexer::T_INTEGER])) {
                $parser->syntaxError('Literal');
            }

            $path[] = $parser->Literal()->value;

            if ($parser->getLexer()->isNextToken(Lexer::T_COMMA)) {
                $parser->match(Lexer::T_COMMA);
            } else {
                break;
            }
        }

        return $path;
    }
}
