<?php

namespace Dnd\Bundle\CriteoConnectorBundle\Job\JobParameters\FormConfigurationProvider;

use Akeneo\Component\Batch\Job\JobInterface;
use Pim\Bundle\ImportExportBundle\JobParameters\FormConfigurationProviderInterface;
use Pim\Component\Catalog\Repository\CurrencyRepositoryInterface;
use Pim\Component\Catalog\Repository\AttributeRepositoryInterface;

/**
 * Class CriteoExport
 *
 * @author                 Alexandre Granjeon <alexandre.granjeon@dnd.fr>
 * @copyright              Copyright (c) 2016 Agence Dn'D
 * @license                http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link                   http://www.dnd.fr/
 */
class CriteoExport implements FormConfigurationProviderInterface
{

    /** @var array $decimalSeparators */
    protected $decimalSeparators;

    /** @var array $dateFormats */
    protected $dateFormats;

    /** @var AttributeRepositoryInterface $attributeRepository */
    protected $attributeRepository;

    /** @var CurrencyRepositoryInterface $currencyRepository */
    protected $currencyRepository;

    /**
     * @var array $supportedJobNames
     */
    protected $supportedJobNames;

    /**
     * CriteoExport constructor.
     * @param array                        $decimalSeparators
     * @param array                        $dateFormats
     * @param AttributeRepositoryInterface $attributeRepository
     * @param CurrencyRepositoryInterface  $currencyRepository
     * @param array                        $supportedJobNames
     */
    public function __construct(
        array $decimalSeparators,
        array $dateFormats,
        AttributeRepositoryInterface $attributeRepository,
        CurrencyRepositoryInterface $currencyRepository,
        array $supportedJobNames
    ) {
        $this->decimalSeparators = $decimalSeparators;
        $this->dateFormats = $dateFormats;
        $this->attributeRepository = $attributeRepository;
        $this->currencyRepository = $currencyRepository;
        $this->supportedJobNames = $supportedJobNames;
    }


    /**
     * @return array
     */
    public function getFormConfiguration()
    {
        $csvFormOptions = [
            'filePath' => [
                'options' => [
                    'label' => 'pim_connector.export.csv.filePath.label',
                    'help'  => 'pim_connector.export.csv.filePath.help',
                ],
            ],
        ];

        $productFormOptions = [
            'filters'          => [
                'type'    => 'hidden',
                'options' => [
                    'attr' => [
                        'data-tab' => 'content',
                    ],
                ],
            ],
            'decimalSeparator' => [
                'type'    => 'choice',
                'options' => [
                    'choices'  => $this->decimalSeparators,
                    'required' => true,
                    'select2'  => true,
                    'label'    => 'pim_connector.export.csv.decimalSeparator.label',
                    'help'     => 'pim_connector.export.csv.decimalSeparator.help',
                ],
            ],
            'dateFormat'       => [
                'type'    => 'choice',
                'options' => [
                    'choices'  => $this->dateFormats,
                    'required' => true,
                    'select2'  => true,
                    'label'    => 'pim_connector.export.csv.dateFormat.label',
                    'help'     => 'pim_connector.export.csv.dateFormat.help',
                ],
            ],
        ];

        $customOptions = [
            'currency'          => [
                'type'    => 'choice',
                'options' => [
                    'choices'  => $this->getCurrencyCodes(),
                    'required' => true,
                    'select2'  => true,
                    'label'    => 'dnd_criteo_connector.export.currency.label',
                    'help'     => 'dnd_criteo_connector.export.currency.help',
                ],
            ],
            'id'                => [
                'type'    => 'choice',
                'options' => [
                    'label'    => 'dnd_criteo_connector.export.id.label',
                    'help'     => 'dnd_criteo_connector.export.id.help',
                    'required' => true,
                    'choices'  => $this->getAttributesChoices(),
                    'select2'  => true,
                ],
            ],
            'name'              => [
                'type'    => 'choice',
                'options' => [
                    'label'    => 'dnd_criteo_connector.export.name.label',
                    'help'     => 'dnd_criteo_connector.export.name.help',
                    'required' => true,
                    'choices'  => $this->getAttributesChoices(),
                    'select2'  => true,
                ],
            ],
            'productUrl'        => [
                'type'    => 'choice',
                'options' => [
                    'label'    => 'dnd_criteo_connector.export.productUrl.label',
                    'help'     => 'dnd_criteo_connector.export.productUrl.help',
                    'required' => true,
                    'choices'  => $this->getAttributesChoices(),
                    'select2'  => true,
                ],
            ],
            'smallImage'        => [
                'type'    => 'choice',
                'options' => [
                    'label'    => 'dnd_criteo_connector.export.smallImage.label',
                    'help'     => 'dnd_criteo_connector.export.smallImage.help',
                    'required' => true,
                    'choices'  => $this->getAttributesChoices(),
                    'select2'  => true,
                ],
            ],
            'bigImage'          => [
                'type'    => 'choice',
                'options' => [
                    'label'   => 'dnd_criteo_connector.export.bigImage.label',
                    'help'    => 'dnd_criteo_connector.export.bigImage.help',
                    'choices' => $this->getAttributesChoices(),
                    'select2' => true,
                ],
            ],
            'description'       => [
                'type'    => 'choice',
                'options' => [
                    'label'   => 'dnd_criteo_connector.export.description.label',
                    'help'    => 'dnd_criteo_connector.export.description.help',
                    'choices' => $this->getAttributesChoices(),
                    'select2' => true,
                ],
            ],
            'price'             => [
                'type'    => 'choice',
                'options' => [
                    'label'   => 'dnd_criteo_connector.export.price.label',
                    'help'    => 'dnd_criteo_connector.export.price.help',
                    'choices' => $this->getAttributesChoices(),
                    'select2' => true,
                ],
            ],
            'retailPrice'       => [
                'type'    => 'choice',
                'options' => [
                    'label'   => 'dnd_criteo_connector.export.retailPrice.label',
                    'help'    => 'dnd_criteo_connector.export.retailPrice.help',
                    'choices' => $this->getAttributesChoices(),
                    'select2' => true,
                ],
            ],
            'recommendable'     => [
                'type'    => 'choice',
                'options' => [
                    'label'   => 'dnd_criteo_connector.export.recommendable.label',
                    'help'    => 'dnd_criteo_connector.export.recommendable.help',
                    'choices' => $this->getAttributesChoices(),
                    'select2' => true,
                ],
            ],
            'includeCategories' => [
                'type'    => 'switch',
                'options' => [
                    'label' => 'dnd_criteo_connector.export.includeCategories.label',
                    'help'  => 'dnd_criteo_connector.export.includeCategories.help',
                ],
            ],
            'pimMediaUrl'       => [
                'options' => [
                    'label' => 'dnd_criteo_connector.export.pimMediaUrl.label',
                    'help'  => 'dnd_criteo_connector.export.pimMediaUrl.help',
                ],
            ],
        ];

        return array_merge($productFormOptions, $csvFormOptions, $customOptions);
    }

    /**
     * Retrieve attributes code for select option
     *
     * @return array[] $choices
     */
    protected function getAttributesChoices()
    {
        $choices = [];
        $choices[''] = '';
        foreach ($this->attributeRepository->getAttributesAsArray() as $attribute) {
            $choices[$attribute['code']] = $attribute['code'];
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
     * @param JobInterface $job
     * @return bool
     */
    public function supports(JobInterface $job)
    {
        return in_array($job->getName(), $this->supportedJobNames);
    }
}