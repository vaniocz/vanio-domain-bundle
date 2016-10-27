<?php
namespace Vanio\DomainBundle\Form;

use Doctrine\Common\Cache\Cache;

class CachingValidationParser implements ValidationParser
{
    /** @var ValidationParser */
    private $validationParser;

    /** @var Cache */
    private $cache;

    /** @var array|null */
    private $validationRules;

    /** @var bool */
    private $debug;

    /**
     * @param ValidationParser $validationParser
     * @param Cache $cache
     * @param bool $debug Whether to invalidate cache on file change (slower)
     */
    public function __construct(ValidationParser $validationParser, Cache $cache, bool $debug = true)
    {
        $this->validationParser = $validationParser;
        $this->cache = $cache;
        $this->debug = $debug;
    }

    public function parseValidationRules(string $class): array
    {
        if (!isset($this->validationRules[$class])) {
            $cacheId = $this->resolveCacheId($class);

            if (!$validationRules = $this->cache->fetch($cacheId)) {
                $validationRules = $this->validationParser->parseValidationRules($class);
                $this->cache->save($cacheId, $validationRules);
            }

            $this->validationRules[$class] = $validationRules;
        }

        return $this->validationRules[$class];
    }

    /**
     * @param string $class
     * @return string
     */
    private function resolveCacheId(string $class): string
    {
        if (!$this->debug) {
            return sprintf('%s[%s]', __CLASS__, $class);
        }

        $reflectionClass = new \ReflectionClass($class);
        $file = preg_replace('~\(\d+\) : eval\(\)\'d code$~', '', $reflectionClass->getFileName());
        $modificationTimes = [];

        do {
            $modificationTimes[] = @filemtime($reflectionClass->getFileName());
        } while ($reflectionClass = $reflectionClass->getParentClass());

        return sprintf('%s[%s][%s][%s]', __CLASS__, $file, implode(',', $modificationTimes), $class);
    }
}
