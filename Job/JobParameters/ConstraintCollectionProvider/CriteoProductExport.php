<?php

namespace Dnd\Bundle\CriteoConnectorBundle\Job\JobParameters\ConstraintCollectionProvider;

use Akeneo\Component\Batch\Job\JobInterface;
use Akeneo\Component\Batch\Job\JobParameters\ConstraintCollectionProviderInterface;
use Symfony\Component\Validator\Constraints\Collection;

/**
 * Class CriteoProductExport
 *
 * @author          Didier Youn <didier.youn@dnd.fr>
 * @copyright       Copyright (c) 2017 Agence Dn'D
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link            http://www.dnd.fr/
 */
class CriteoProductExport implements ConstraintCollectionProviderInterface
{

    /** @var array $supportedJobNames */
    protected $supportedJobNames;

    /**
     * CriteoProductExport constructor.
     *
     * @param array $supportedJobNames
     */
    public function __construct(array $supportedJobNames)
    {
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