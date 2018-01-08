<?php
namespace Vanio\DomainBundle\Translatable;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\AST\ArithmeticExpression;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\InExpression;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\ORM\Query\AST\JoinAssociationDeclaration;
use Doctrine\ORM\Query\AST\JoinAssociationPathExpression;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\TreeWalkerAdapter;

class TranslatableWalker extends TreeWalkerAdapter
{
    const HINT_LOCALE = 'vanio.translatable_walker.locale';
    const HINT_FALLBACK_LOCALE = 'vanio.translatable_walker.fallback_locale';
    const HINT_INNER_JOIN = 'vanio.translatable_walker.inner_join';
    const HINT_DQL_ALIASES = 'vanio.translatable_walker.dql_aliases';

    /** @var string[]|null */
    private $dqlAliases;

    /** @var string|bool */
    private $locale;

    /** @var string|bool */
    private $fallbackLocale;

    /** @var bool */
    private $innerJoin;

    /** @var TranslatableListener|null */
    private $translatableListener;

    /**
     * @param AbstractQuery $query
     * @param ParserResult $parserResult
     * @param array $queryComponents
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        parent::__construct($query, $parserResult, $queryComponents);
        $hints = $query->getHints();
        $this->dqlAliases = isset($hints[self::HINT_DQL_ALIASES]) ? array_flip($hints[self::HINT_DQL_ALIASES]) : null;
        $this->locale = $hints[self::HINT_LOCALE] ?? true;
        $this->fallbackLocale = $hints[self::HINT_FALLBACK_LOCALE] ?? false;
        $this->innerJoin = $hints[self::HINT_INNER_JOIN] ?? false;
    }

    public function walkSelectStatement(SelectStatement $select)
    {
        foreach ($select->fromClause->identificationVariableDeclarations as $from) {
            $this->joinTranslations($select, $from, $from->rangeVariableDeclaration->aliasIdentificationVariable);

            foreach ($from->joins as $join) {
                $this->joinTranslations($select, $from, $join->joinAssociationDeclaration->aliasIdentificationVariable);
            }
        }
    }

    private function joinTranslations(
        SelectStatement $select,
        IdentificationVariableDeclaration $from,
        string $dqlAlias
    ) {
        $queryComponent = $this->getQueryComponents()[$dqlAlias];
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $queryComponent['metadata'];

        if (
            $this->dqlAliases !== null && !isset($this->dqlAliases[$dqlAlias])
            || !$classMetadata->reflClass->implementsInterface(Translatable::class)
        ) {
            return;
        }

        $translationsDqlAlias = sprintf('%s_translations', $dqlAlias);
        $from->joins[] = $this->createTranslationsJoin($dqlAlias, $translationsDqlAlias);
        $select->selectClause->selectExpressions[] = new SelectExpression($translationsDqlAlias, null, false);
        $translationClass = $classMetadata->getAssociationTargetClass('translations');
        $this->setQueryComponent($translationsDqlAlias, [
            'metadata' => $this->_getQuery()->getEntityManager()->getClassMetadata($translationClass),
            'parent' => $dqlAlias,
            'relation' => $classMetadata->associationMappings['translations'],
            'map' => null,
            'nestingLevel' => $queryComponent['nestingLevel'],
            'token' => null,
        ]);
    }

    private function createTranslationsJoin(string $dqlAlias, string $translationsDqlAlias): Join
    {
        $join = new Join(
            $this->innerJoin ? Join::JOIN_TYPE_INNER : Join::JOIN_TYPE_LEFT,
            new JoinAssociationDeclaration(
                new JoinAssociationPathExpression($dqlAlias, 'translations'),
                $translationsDqlAlias,
                null
            )
        );
        $pathExpression = new PathExpression(PathExpression::TYPE_STATE_FIELD, $translationsDqlAlias, 'locale');
        $pathExpression->type = PathExpression::TYPE_STATE_FIELD;
        $arithmeticExpression = new ArithmeticExpression;
        $arithmeticExpression->simpleArithmeticExpression = new SimpleArithmeticExpression([$pathExpression]);

        if ($this->locale !== false) {
            $inExpression = new InExpression($arithmeticExpression);
            $inExpression->literals = [];

            foreach ($this->resolveLocales() as $locale) {
                $inExpression->literals[] = new Literal(Literal::STRING, $locale);
            }

            $join->conditionalExpression = new ConditionalPrimary;
            $join->conditionalExpression->simpleConditionalExpression = $inExpression;
        }

        return $join;
    }

    private function resolveLocales(): array
    {
        $locales = [];

        if ($this->locale !== false) {
            $locales[] = $this->locale === true
                ? $this->translatableListener()->resolveCurrentLocale()
                : $this->locale;
        }

        if ($this->fallbackLocale !== false) {
            $locales[] = $this->fallbackLocale === true
                ? $this->translatableListener()->resolveDefaultLocale()
                : $this->fallbackLocale;
        }

        return $locales;
    }

    private function translatableListener(): TranslatableListener
    {
        if ($this->translatableListener === null) {
            foreach ($this->_getQuery()->getEntityManager()->getEventManager()->getListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof TranslatableListener) {
                        $this->translatableListener = $listener;

                        return $listener;
                    }
                }
            }

            throw new \RuntimeException('The translatable listener could not be found.');
        }

        return $this->translatableListener;
    }
}
