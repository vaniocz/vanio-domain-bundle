<?php
namespace Vanio\DomainBundle\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class EarthDistanceFunction extends FunctionNode
{
    /** @var Node */
    private $latitudeFrom;

    /** @var Node */
    private $longitudeFrom;

    /** @var Node */
    private $latitudeTo;

    /** @var Node */
    private $longitudeTo;

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->latitudeFrom = $parser->ArithmeticFactor();
        $parser->match(Lexer::T_COMMA);
        $this->longitudeFrom = $parser->ArithmeticFactor();
        $parser->match(Lexer::T_COMMA);
        $this->latitudeTo = $parser->ArithmeticFactor();
        $parser->match(Lexer::T_COMMA);
        $this->longitudeTo = $parser->ArithmeticFactor();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            'earth_distance(ll_to_earth(%s, %s), ll_to_earth(%s, %s))',
            $this->latitudeFrom->dispatch($sqlWalker),
            $this->longitudeFrom->dispatch($sqlWalker),
            $this->latitudeTo->dispatch($sqlWalker),
            $this->longitudeTo->dispatch($sqlWalker)
        );
    }
}
