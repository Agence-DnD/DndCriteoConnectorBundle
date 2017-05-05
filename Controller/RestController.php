<?php

namespace Dnd\Bundle\CriteoConnectorBundle\Controller;

use Pim\Component\Catalog\Repository\AttributeRepositoryInterface;
use Pim\Component\Catalog\Repository\CurrencyRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class RestController
 *
 * @author                 Agence Dn'D <contact@dnd.fr>
 * @copyright              Copyright (c) 2017 Agence Dn'D
 * @license                http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link                   http://www.dnd.fr/
 */
class RestController
{

    /**
     * @var AttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var CurrencyRepositoryInterface
     */
    protected $currencyRepository;


    /**
     * RestController constructor.
     *
     * @param AttributeRepositoryInterface $attributeRepository
     * @param CurrencyRepositoryInterface  $currencyRepository
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        CurrencyRepositoryInterface $currencyRepository
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->currencyRepository = $currencyRepository;
    }

    /**
     * @return JsonResponse
     */
    public function listAttributesAction()
    {
        $attributesList = [];
        $attributesList[''] = '';
        foreach ($this->attributeRepository->getAttributesAsArray() as $attribute) {
            $attributesList[$attribute['code']] = $attribute['code'];
        }

        return new JsonResponse(array_combine($attributesList, $attributesList));
    }

    /**
     * @return JsonResponse
     */
    public function listAvailableCurrenciesAction()
    {
        $availableCurrenciesList = [];
        $availableCurrenciesList[''] = '';
        foreach ($this->currencyRepository->getActivatedCurrencies() as $currency) {
            $availableCurrenciesList[$currency->getCode()] = $currency->getCode();
        }

        return new JsonResponse(array_combine($availableCurrenciesList, $availableCurrenciesList));
    }
}
