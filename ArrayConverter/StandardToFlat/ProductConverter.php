<?php

namespace Dnd\Bundle\CriteoConnectorBundle\ArrayConverter\StandardToFlat;

use Akeneo\Pim\Enrichment\Component\Product\Connector\ArrayConverter\StandardToFlat\ProductLocalized;
use Akeneo\Pim\Enrichment\Component\Product\Localization\Localizer\AttributeConverterInterface;
use Akeneo\Pim\Structure\Component\AttributeTypes;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use Akeneo\Pim\Structure\Component\Repository\AttributeRepositoryInterface;
use Akeneo\Tool\Component\Connector\ArrayConverter\ArrayConverterInterface;
use Dnd\Bundle\CriteoConnectorBundle\Model\CriteoImportExport;
use Dnd\Bundle\CriteoConnectorBundle\Renderer\PublicFileRenderer;

/**
 * Class ProductConverter
 *
 * @category  Class
 * @package   Dnd\Bundle\CriteoConnectorBundle\ArrayConverter\FlatToStandard
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class ProductConverter extends ProductLocalized implements ArrayConverterInterface
{
    /**
     * Description CRITEO_MAPPING_ATTRIBUTES constant
     *
     * @var string[] CRITEO_MAPPING_ATTRIBUTES
     */
    const CRITEO_MAPPING_ATTRIBUTES = [
        'id' => CriteoImportExport::ATTR_IDENTIFIER,
        'name' => CriteoImportExport::ATTR_NAME,
        'description' => CriteoImportExport::ATTR_DESCRIPTION,
        'productUrl' => CriteoImportExport::ATTR_PRODUCT_URL,
        'smallImage' => CriteoImportExport::ATTR_SMALL_IMG,
        'bigImage' => CriteoImportExport::ATTR_BIG_IMG,
        'price' => CriteoImportExport::ATTR_PRICE,
        'retailPrice' => CriteoImportExport::ATTR_RETAIL_PRICE,
        'recommendable' => CriteoImportExport::ATTR_RECOMMENDABLE,
    ];

    /**
     * Description CRITEO_PRICING_ATTRIBUTES constant
     *
     * @var string[] CRITEO_PRICING_ATTRIBUTES
     */
    const CRITEO_PRICING_ATTRIBUTES = [
        'price',
        'retailprice'
    ];

    /**
     * Description $publicFileRenderer field
     *
     * @var PublicFileRenderer $publicFileRenderer
     */
    private $publicFileRenderer;

    /**
     * ProductConverter constructor
     *
     * @param ArrayConverterInterface     $converter
     * @param AttributeConverterInterface $localizer
     * @param PublicFileRenderer          $fileRenderer
     *
     * @return void
     */
    public function __construct(
        ArrayConverterInterface $converter,
        AttributeConverterInterface $localizer,
        PublicFileRenderer $fileRenderer
    ) {
        parent::__construct($converter, $localizer);

        $this->publicFileRenderer = $fileRenderer;
    }


    /**
     * Description convert function
     *
     * @param string[] $product
     * @param string[] $options
     *
     * @return array
     * @throws \Exception
     */
    public function convert(array $product, array $options = []): array
    {
        /** @var string[] $convertedProduct */
        $convertedProduct = parent::convert($product, $options);
        if (!isset($options['jobParameters'])) {

            return $convertedProduct;
         }
        /** @var string[] $jobParameters */
        $jobParameters = $options['jobParameters'];
        foreach (self::CRITEO_PRICING_ATTRIBUTES as $priceAttribute) {
            /** @var string $priceAttributeCurrency */
            $priceAttributeCurrency = sprintf(
                '%s-%s',
                $priceAttribute,
                $jobParameters['currency']
            );
            if (isset($convertedProduct[$priceAttributeCurrency])) {
                $convertedProduct[$priceAttribute] = $convertedProduct[$priceAttributeCurrency];
            }
        }
        try {
            $this->map($convertedProduct, $jobParameters, $options);
            $this->clean($convertedProduct);
        } catch (\Exception $exception) {
            throw new \Exception(sprintf('%s', $exception->getMessage()));
        }

        return $convertedProduct;
    }

    /**
     * Description map function
     *
     * @param string[] $convertedProduct
     * @param string[] $jobParameters
     * @param string[] $options
     *
     * @return void
     */
    protected function map(
        array &$convertedProduct,
        array $jobParameters,
        array $options
    ): void {
        /**
         * @var string $attributeKey
         * @var string $attributeValue
         */
        foreach (self::CRITEO_MAPPING_ATTRIBUTES as $attributeKey => $attributeValue) {
            if (!isset($jobParameters[$attributeValue])) {
                continue;
            }
            /** @var string|array $pimAttribute */
            $pimAttribute = $jobParameters[$attributeValue];
            if (!is_scalar($pimAttribute)) {
                continue;
            }
            if (!isset($convertedProduct[$pimAttribute])) {
                $localizableAttribute = sprintf(
                    '%s-%s-%s',
                    $pimAttribute,
                    $options['jobLocale'],
                    $options['jobChannel']
                );
                if (!isset($convertedProduct[$localizableAttribute])) {
                    continue;
                }
                $value = $this->reformatValue($convertedProduct[$localizableAttribute], $pimAttribute, $options);
                $convertedProduct[$attributeKey] = $value;
                continue;
            }
            /** @var mixed $value */
            $value = $this->reformatValue($convertedProduct[$pimAttribute], $pimAttribute, $options);
            $convertedProduct[$attributeKey] = $value;
        }

    }

    /**
     * Description clean function
     *
     * @param string[] $convertedProduct
     *
     * @return void
     */
    protected function clean(&$convertedProduct): void
    {
        /**
         * @var string $attributeKey
         * @var string $attributeValue
         */
        foreach ($convertedProduct as $attributeKey => $attributeValue) {
            if (true === array_key_exists($attributeKey, self::CRITEO_MAPPING_ATTRIBUTES)) {
                continue;
            }
            unset($convertedProduct[$attributeKey]);
        }
    }

    /**
     * Description reformatValue function
     *
     * @param string  $value
     * @param string  $attributeCode
     * @param mixed[] $options
     *
     * @return string
     */
    private function reformatValue(
        string $value,
        string $attributeCode,
        array $options
    ): string {
        if (!isset($options['attributeRepository'])) {

            return $value;
        }
        /** @var AttributeRepositoryInterface $attributeRepository */
        $attributeRepository = $options['attributeRepository'];
        /** @var AttributeInterface|null $attribute */
        $attribute = $attributeRepository->findOneByIdentifier($attributeCode);
        if (!$attribute || false === $attribute instanceof AttributeInterface) {
            return $value;
        }
        /** @var string $type */
        $type = $attribute->getType();
        switch ($type) {
            case AttributeTypes::IMAGE:
                /** @var string|null $publicUrl */
                $publicUrl = $this->publicFileRenderer->getBrowserUrlPath($value);
                if ($publicUrl && $publicUrl !== '') {
                    $value = $publicUrl;
                }
                break;
            case AttributeTypes::BOOLEAN:
                return $value;
            case AttributeTypes::TEXTAREA:
                $value = htmlentities(strip_tags($value));
                break;
        }

        return $value;
    }
}
