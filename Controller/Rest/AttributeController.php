<?php

namespace Dnd\Bundle\CriteoConnectorBundle\Controller\Rest;

use Pim\Component\Catalog\Repository\AttributeRepositoryInterface;
use Pim\Component\Catalog\Repository\CurrencyRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class AttributeController
 *
 * @author          Didier Youn <didier.youn@dnd.fr>
 * @copyright       Copyright (c) 2017 Agence Dn'D
 * @license         http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link            http://www.dnd.fr/
 */
class AttributeController
{

    /** @var AttributeRepositoryInterface $attributeRepository */
    private $attributeRepository;

    /** @var CurrencyRepositoryInterface $currencyRepository */
    private $currencyRepository;

    /**
     * AttributeController constructor.
     *
     * @param AttributeRepositoryInterface $attributeRepository
     * @param CurrencyRepositoryInterface $currencyRepository
     */
    public function __construct(AttributeRepositoryInterface $attributeRepository, CurrencyRepositoryInterface $currencyRepository)
    {
        $this->attributeRepository = $attributeRepository;
        $this->currencyRepository = $currencyRepository;
    }

    /**
     * Get attributes as JSON
     *
     * @return JsonResponse
     */
    public function listAttributesAction()
    {
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
     * Get currencies as JSON
     *
     * @return JsonResponse
     */
    public function listCurrenciesAction()
    {
        $availableCurrenciesList = [];
        $availableCurrenciesList[''] = '';
        foreach ($this->currencyRepository->getActivatedCurrencies() as $currency) {
            $availableCurrenciesList[$currency->getCode()] = $currency->getCode();
        }

        return new JsonResponse(array_combine($availableCurrenciesList, $availableCurrenciesList));
    }
}