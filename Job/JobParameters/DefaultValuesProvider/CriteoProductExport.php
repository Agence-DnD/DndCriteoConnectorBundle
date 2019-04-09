<?php

namespace Dnd\Bundle\CriteoConnectorBundle\Job\JobParameters\DefaultValuesProvider;

use Akeneo\Tool\Component\Batch\Job\JobInterface;
use Akeneo\Pim\Enrichment\Component\Product\Connector\Job\JobParameters\DefaultValueProvider\ProductCsvExport;

/**
 * Class CriteoProductExport
 *
 * @author          Didier Youn <didier.youn@dnd.fr>
 * @copyright       Copyright (c) 2017 Agence Dn'D
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link            http://www.dnd.fr/
 */
class CriteoProductExport extends ProductCsvExport
{
    /**
     * {@inheritdoc}
     */
    public function getDefaultValues()
    {
        return array_merge(
            parent::getDefaultValues(),[
                'filePath'          => './tmp/export_criteo.xml',
                'currency'          => null,
                'id'                => null,
                'name'              => null,
                'productUrl'        => null,
                'smallImage'        => null,
                'bigImage'          => null,
                'description'       => null,
                'price'             => null,
                'retailPrice'       => null,
                'recommendable'     => true,
                'includeCategories' => false,
                'pimMediaUrl'       => null,
                'with_media'        => true
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supports(JobInterface $job)
    {
        return in_array($job->getName(), $this->supportedJobNames);
    }
}
