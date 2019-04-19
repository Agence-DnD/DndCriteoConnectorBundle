<?php

namespace Dnd\Bundle\CriteoConnectorBundle\Job\JobParameters\ConstraintCollectionProvider;

use Akeneo\Tool\Component\Batch\Job\JobInterface;
use Akeneo\Tool\Component\Batch\Job\JobParameters\ConstraintCollectionProviderInterface;
use Symfony\Component\Validator\Constraints\Collection;

/**
 * Class CriteoProductExport
 *
 * @author          Didier Youn <didier.youn@dnd.fr>
 * @copyright       Copyright (c) 2019 Agence Dn'D
 * @license         https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link            https://www.dnd.fr/
 */
class CriteoProductExport implements ConstraintCollectionProviderInterface
{
    /**
     * Description $supportedJobNames field
     *
     * @var string[] $supportedJobNames
     */
    protected $supportedJobNames;

    /**
     * CriteoProductExport constructor.
     *
     * @param string[] $supportedJobNames
     */
    public function __construct(
        array $supportedJobNames
    ) {
        $this->supportedJobNames = $supportedJobNames;
    }

    /**
     * {@inheritdoc}
     */
    public function getConstraintCollection()
    {
        return new Collection(
            [
                'fields' => []
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
