<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vanio\DomainBundle\Model\Image;

class ImageType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'class' => Image::class,
                'supported_image_types' => [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP],
            ])
            ->setAllowedTypes('supported_image_types', 'array')
            ->setNormalizer('options', $this->optionsNormalizer());
    }

    public function getParent(): string
    {
        return FileType::class;
    }

    private function optionsNormalizer(): \Closure
    {
        return function (Options $options, array $innerOptions) {
            if (!isset($options['attr']['accept']) && $options['supported_image_types']) {
                $accept = implode(',', $this->resolveMimeTypes($options['supported_image_types']));
                $innerOptions['attr']['accept'] = $accept;
            }

            return $innerOptions;
        };
    }

    private function resolveMimeTypes(array $imageTypes): array
    {
        $mimeTypes = [];

        foreach ($imageTypes as $imageType) {
            $mimeType = image_type_to_mime_type($imageType);
            $mimeTypes[$mimeType] = $mimeType;

            if ($mimeType === 'image/x-ms-bmp') {
                $mimeTypes['image/bmp'] = 'image/bmp';
            }
        }

        return $mimeTypes;
    }
}
