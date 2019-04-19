<?php

namespace Dnd\Bundle\CriteoConnectorBundle\Controller\Rest;

use Akeneo\Pim\Structure\Component\Repository\AttributeRepositoryInterface;
use Akeneo\Channel\Component\Repository\CurrencyRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class AttributeController
 *
 * @author          Didier Youn <didier.youn@dnd.fr>
 * @copyright       Copyright (c) 2019 Agence Dn'D
 * @license         https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link            https://www.dnd.fr/
 */
class AttributeController
{
    /**
     * Description $attributeRepository field
     *
     * @var AttributeRepositoryInterface $attributeRepository
     */
    private $attributeRepository;
    /**
     * Description $currencyRepository field
     *
     * @var CurrencyRepositoryInterface $currencyRepository
     */
    private $currencyRepository;

    /**
     * AttributeController constructor
     *
     * @param AttributeRepositoryInterface $attributeRepository
     * @param CurrencyRepositoryInterface  $currencyRepository
     *
     * @return void
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        CurrencyRepositoryInterface $currencyRepository
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->currencyRepository  = $currencyRepository;
    }

    /**
     * Description listAttributesAction function
     *
     * @return JsonResponse
     */
    public function listAttributesAction(): JsonResponse
    {
        /** @var string[] $attributesList */
        $attributesList = [];
        $attributesList[''] = '';
        foreach ($this->attributeRepository->getAttributesAsArray() as $attribute) {
            if (!isset($attribute['code'])) {
                continue;
            }
            $attributesList[$attribute['code']] = $attribute['code'];
        }

        return new JsonResponse(array_combine($attributesList, $attributesList));
    }

    /**
     * Description listCurrenciesAction function
     *
     * @return JsonResponse
     */
    public function listCurrenciesAction(): JsonResponse
    {
        /** @var string[] $availableCurrenciesList */
        $availableCurrenciesList = [];
        $availableCurrenciesList[''] = '';
        foreach ($this->currencyRepository->getActivatedCurrencies() as $currency) {
            $availableCurrenciesList[$currency->getCode()] = $currency->getCode();
        }

        return new JsonResponse(array_combine($availableCurrenciesList, $availableCurrenciesList));
    }
}
