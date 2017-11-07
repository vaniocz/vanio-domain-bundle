<?php
namespace Vanio\DomainBundle\Translatable;

trait TranslationTrait
{
    /** @var int|null */
    private $id;

    /** @var Translatable */
    private $translatable;

    /** @var string */
    private $locale;

    /**
     * @return int|null
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @return Translatable|null
     */
    public function translatable()
    {
        return $this->translatable;
    }

    /**
     * @throws TranslationException
     * @internal
     */
    public function setTranslatable(Translatable $translatable): self
    {
        if ($this->translatable !== null && $this->translatable !== $translatable) {
            TranslationException::cannotReassign($this);
        }

        $this->translatable = $translatable;

        return $this;
    }

    /**
     * @return string|null
     */
    public function locale()
    {
        return $this->locale;
    }

    /**
     * @throws TranslationException
     */
    public function setLocale(string $locale): self
    {
        if ($this->translatable && $this->locale !== null && $this->locale !== $locale) {
            throw TranslationException::cannotOverwriteLocale($this, $locale);
        }

        $this->locale = $locale;

        return $this;
    }

    public function isEmpty(): bool
    {
        foreach (get_object_vars($this) as $property => $value) {
            if ($value !== null && $value !== '' && !in_array($property, ['id', 'translatable', 'locale'])) {
                return $property === 'isTranslated' ? !$value : false;
            }
        }

        return true;
    }

    public static function translatableClass(): string
    {
        return substr(__CLASS__, 0, -11);
    }
}
