<?php

namespace Dnd\Bundle\CriteoConnectorBundle\Processor;

use Akeneo\Component\Batch\Item\AbstractConfigurableStepElement;
use Akeneo\Component\Batch\Item\ItemProcessorInterface;
use Akeneo\Component\Localization\Localizer\LocalizerInterface;
use Pim\Bundle\BaseConnectorBundle\Validator\Constraints\Channel;
use Pim\Bundle\CatalogBundle\Builder\ProductBuilder;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Component\Catalog\Builder\ProductBuilderInterface;
use Pim\Component\Catalog\Model\ProductInterface;
use Pim\Component\Catalog\Model\ProductValueInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Pim\Component\Catalog\Repository\LocaleRepositoryInterface;
use Pim\Bundle\CatalogBundle\Repository\CurrencyRepositoryInterface;

/**
 * Process a product to an array
 *
 * @author    Florian Fauvel <florian.fauvel@dnd.fr>
 * @copyright 2016 Agence Dn'D (http://www.dnd.fr)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductToFlatArrayProcessor extends AbstractConfigurableStepElement implements ItemProcessorInterface
{
    /** @var Serializer */
    protected $serializer;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     * @Channel
     *
     * @var string Channel code
     */
    protected $channel;

    /** @var ChannelManager */
    protected $channelManager;

    /** @var array Normalizer context */
    protected $normalizerContext;

    /** @var array */
    protected $mediaAttributeTypes;

    /** @var array */
    protected $priceAttributeTypes;

    /** @var string */
    protected $decimalSeparator = LocalizerInterface::DEFAULT_DECIMAL_SEPARATOR;

    /** @var array */
    protected $decimalSeparators;

    /** @var string */
    protected $dateFormat = LocalizerInterface::DEFAULT_DATE_FORMAT;

    /** @var array */
    protected $dateFormats;

    /** @var ProductBuilder */
    protected $productBuilder;

    /** @var LocaleRepositoryInterface */
    protected $localeRepository;

    /** @var string */
    protected $locale;

    /** @var CurrencyRepositoryInterface */
    protected $currencyRepository;

    /** @var string */
    protected $currency;

    /**
     * @param Serializer                  $serializer
     * @param ChannelManager              $channelManager
     * @param string[]                    $mediaAttributeTypes
     * @param string[]                    $priceAttributeTypes
     * @param array                       $decimalSeparators
     * @param array                       $dateFormats
     * @param ProductBuilderInterface     $productBuilder
     * @param LocaleRepositoryInterface   $localeRepository
     * @param CurrencyRepositoryInterface $currencyRepository
     */
    public function __construct(Serializer $serializer, ChannelManager $channelManager, ProductBuilderInterface $productBuilder, array $mediaAttributeTypes, array $priceAttributeTypes, array $decimalSeparators, array $dateFormats, LocaleRepositoryInterface $localeRepository, CurrencyRepositoryInterface $currencyRepository)
    {
        $this->serializer          = $serializer;
        $this->channelManager      = $channelManager;
        $this->mediaAttributeTypes = $mediaAttributeTypes;
        $this->priceAttributeTypes = $priceAttributeTypes;
        $this->decimalSeparators   = $decimalSeparators;
        $this->dateFormats         = $dateFormats;
        $this->productBuilder      = $productBuilder;
        $this->localeRepository    = $localeRepository;
        $this->currencyRepository  = $currencyRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process($product)
    {
        $contextChannel = $this->channelManager->getChannelByCode($this->channel);
        $this->productBuilder->addMissingProductValues($product, [$contextChannel], [$this->localeRepository->findOneBy(['code' => $this->getLocale()])]);

        $this->removePricesNotInCurrency($product);

        $data['media'] = [];
        $mediaValues   = $this->getMediaProductValues($product);

        foreach ($mediaValues as $mediaValue) {
            $data['media'][] = $this->serializer->normalize(
                $mediaValue->getMedia(),
                'flat',
                ['field_name' => 'media', 'prepare_copy' => true, 'value' => $mediaValue]
            );
        }

        $data['product'] = $this->serializer->normalize($product, 'flat', $this->getNormalizerContext());

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return [
            'channel' => [
                'type'    => 'choice',
                'options' => [
                    'choices'  => $this->channelManager->getChannelChoices(),
                    'required' => true,
                    'select2'  => true,
                    'label'    => 'pim_base_connector.export.channel.label',
                    'help'     => 'pim_base_connector.export.channel.help'
                ]
            ],
            'decimalSeparator' => [
                'type'    => 'choice',
                'options' => [
                    'choices'  => $this->decimalSeparators,
                    'required' => true,
                    'select2'  => true,
                    'label'    => 'pim_base_connector.export.decimalSeparator.label',
                    'help'     => 'pim_base_connector.export.decimalSeparator.help'
                ]
            ],
            'dateFormat' => [
                'type'    => 'choice',
                'options' => [
                    'choices'  => $this->dateFormats,
                    'required' => true,
                    'select2'  => true,
                    'label'    => 'pim_base_connector.export.dateFormat.label',
                    'help'     => 'pim_base_connector.export.dateFormat.help',
                ]
            ],
            'locale' => [
                'type'    => 'choice',
                'options' => [
                    'choices'  => $this->getLocaleCodes(),
                    'required' => true,
                    'select2'  => true,
                    'label'    => 'dnd_criteo_connector.export.locale.label',
                    'help'     => 'dnd_criteo_connector.export.locale.help'
                ]
            ],
            'currency' => [
                'type'    => 'choice',
                'options' => [
                    'choices'  => $this->getCurrencyCodes(),
                    'required' => true,
                    'select2'  => true,
                    'label'    => 'dnd_criteo_connector.export.currency.label',
                    'help'     => 'dnd_criteo_connector.export.currency.help'
                ]
            ],
        ];
    }

    /**
     * Set channel
     *
     * @param string $channelCode Channel code
     *
     * @return $this
     */
    public function setChannel($channelCode)
    {
        $this->channel = $channelCode;

        return $this;
    }

    /**
     * Get channel
     *
     * @return string Channel code
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Set the separator for decimal
     *
     * @param string $decimalSeparator
     */
    public function setDecimalSeparator($decimalSeparator)
    {
        $this->decimalSeparator = $decimalSeparator;
    }

    /**
     * Get the delimiter for decimal
     *
     * @return string
     */
    public function getDecimalSeparator()
    {
        return $this->decimalSeparator;
    }

    /**
     * Set the date format
     *
     * @param string $dateFormat
     */
    public function setDateFormat($dateFormat)
    {
        $this->dateFormat = $dateFormat;
    }

    /**
     * Get the date format
     *
     * @return string
     */
    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    /**
     * Set locale
     *
     * @param string $localeCode Locale code
     *
     * @return $this
     */
    public function setLocale($localeCode)
    {
        $this->locale = $localeCode;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string Locale code
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set currency
     *
     * @param string $currencyCode Currency code
     *
     * @return $this
     */
    public function setCurrency($currencyCode)
    {
        $this->currency = $currencyCode;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string Currency code
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Get normalizer context
     *
     * @return array $normalizerContext
     */
    protected function getNormalizerContext()
    {
        if (null === $this->normalizerContext) {
            $this->normalizerContext = [
                'scopeCode'         => $this->channel,
                'localeCodes'       => [$this->getLocale()],
                'decimal_separator' => $this->decimalSeparator,
                'date_format'       => $this->dateFormat,
            ];
        }

        return $this->normalizerContext;
    }

    /**
     * Get locale codes for select option
     *
     * @return array
     */
    protected function getLocaleCodes()
    {
        $choices = [];
        foreach ($this->localeRepository->getActivatedLocales() as $locale) {
            $choices[$locale->getCode()] = $locale->getCode();
        }

        return $choices;
    }

    /**
     * Get currency codes for select option
     *
     * @return array
     */
    protected function getCurrencyCodes()
    {
        $choices = [];
        foreach ($this->currencyRepository->getActivatedCurrencies() as $currency) {
            $choices[$currency->getCode()] = $currency->getCode();
        }
        
        return $choices;
    }

    /**
     * Fetch medias product values
     *
     * @param ProductInterface $product
     *
     * @return ProductValueInterface[]
     */
    protected function getMediaProductValues(ProductInterface $product)
    {
        $values = [];
        foreach ($product->getValues() as $value) {
            if (in_array($value->getAttribute()->getAttributeType(), $this->mediaAttributeTypes)) {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * Remove prices in different currencies than the selected currency in the UI
     *
     * @param ProductInterface $product
     *
     * @return ProductValueInterface[]
     */
    protected function removePricesNotInCurrency(ProductInterface $product)
    {
        foreach ($product->getValues() as $value) {
            if (in_array($value->getAttribute()->getAttributeType(), $this->priceAttributeTypes)) {
                $this->productBuilder->removePricesNotInCurrency($value, [$this->getCurrency()]);
            }
        }
    }
}
