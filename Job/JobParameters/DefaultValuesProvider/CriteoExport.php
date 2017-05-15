<?php

namespace Dnd\Bundle\CriteoConnectorBundle\Job\JobParameters\DefaultValuesProvider;

use Akeneo\Component\Batch\Job\JobInterface;
use Akeneo\Component\Batch\Job\JobParameters\DefaultValuesProviderInterface;
use Akeneo\Component\Localization\Localizer\LocalizerInterface;
use Pim\Component\Catalog\Query\Filter\Operators;
use Pim\Component\Catalog\Repository\ChannelRepositoryInterface;
use Pim\Component\Catalog\Repository\LocaleRepositoryInterface;

/**
 * Class CriteoExport
 *
 * @author                 Alexandre Granjeon <alexandre.granjeon@dnd.fr>
 * @copyright              Copyright (c) 2016 Agence Dn'D
 * @license                http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link                   http://www.dnd.fr/
 */
class CriteoExport implements DefaultValuesProviderInterface
{
    /** @var array $supportedJobNames */
    protected $supportedJobNames;

    /** @var ChannelRepositoryInterface $channelRepository */
    protected $channelRepository;

    /** @var LocaleRepositoryInterface $localeRepository */
    protected $localeRepository;

    /**
     * CriteoExport constructor.
     * @param ChannelRepositoryInterface $channelRepository
     * @param LocaleRepositoryInterface  $localeRepository
     * @param array                      $supportedJobNames
     */
    public function __construct(
        ChannelRepositoryInterface $channelRepository,
        LocaleRepositoryInterface $localeRepository,
        array $supportedJobNames
    ) {
        $this->channelRepository = $channelRepository;
        $this->localeRepository = $localeRepository;
        $this->supportedJobNames = $supportedJobNames;
    }

    /**
     * Get the default values for the export
     * @return array
     */
    public function getDefaultValues()
    {
        $parameters['filePath'] = '/tmp/export_criteo.xml';
        $parameters['decimalSeparator'] = LocalizerInterface::DEFAULT_DECIMAL_SEPARATOR;
        $parameters['dateFormat'] = LocalizerInterface::DEFAULT_DATE_FORMAT;

        $parameters['currency'] = null;
        $parameters['id'] = null;
        $parameters['name'] = null;
        $parameters['productUrl'] = null;
        $parameters['smallImage'] = null;
        $parameters['bigImage'] = null;
        $parameters['description'] = null;
        $parameters['price'] = null;
        $parameters['retailPrice'] = null;
        $parameters['recommendable'] = true;
        $parameters['includeCategories'] = false;
        $parameters['pimMediaUrl'] = null;
        $parameters['with_media'] = true;

        $defaultChannel = $this->channelRepository->getFullChannels()[0];
        $defaultLocaleCode = $this->localeRepository->getActivatedLocaleCodes()[0];
        $parameters['filters'] = [
            'data'      => [
                [
                    'field'    => 'enabled',
                    'operator' => Operators::EQUALS,
                    'value'    => true,
                ],
                [
                    'field'    => 'completeness',
                    'operator' => Operators::GREATER_OR_EQUAL_THAN,
                    'value'    => 100,
                ],
                [
                    'field'    => 'categories.code',
                    'operator' => Operators::IN_CHILDREN_LIST,
                    'value'    => [],
                ],
            ],
            'structure' => [
                'scope'   => $defaultChannel->getCode(),
                'locales' => $defaultLocaleCode,
            ],
        ];

        return $parameters;

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
