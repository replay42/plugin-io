<?php //strict

namespace IO\Services;

use Plenty\Modules\Account\Contact\Models\Contact;
use Plenty\Modules\Item\SalesPrice\Contracts\SalesPriceSearchRepositoryContract;
use Plenty\Modules\Item\SalesPrice\Models\SalesPriceSearchRequest;
use Plenty\Modules\Item\SalesPrice\Models\SalesPriceSearchResponse;
use Plenty\Modules\Webshop\Contracts\CheckoutRepositoryContract;
use Plenty\Modules\Webshop\Contracts\ContactRepositoryContract;
use Plenty\Plugin\Application;

class SalesPriceService
{
    private $app;
    private $salesPriceSearchRepo;
    /** @var ContactRepositoryContract $contactRepository */
    private $contactRepository;
    /** @var CheckoutRepositoryContract $checkoutRepository */
    private $checkoutRepository;
    private $basketService;

    private $classId = null;
    private $singleAccess = null;
    private $currency = null;
    private $plentyId = null;
    private $shippingCountryId = null;
    private $referrerId = null;

    /**
     * SalesPriceService constructor.
     * @param Application $app
     * @param SalesPriceSearchRepositoryContract $salesPriceSearchRepo
     * @param ContactRepositoryContract $contactRepository
     * @param CheckoutRepositoryContract $checkoutRepository
     * @param BasketService $basketService
     */
    public function __construct(
        Application $app,
        SalesPriceSearchRepositoryContract $salesPriceSearchRepo,
        ContactRepositoryContract $contactRepository,
        CheckoutRepositoryContract $checkoutRepository,
        BasketService $basketService
    )
    {
        $this->app                  = $app;
        $this->salesPriceSearchRepo = $salesPriceSearchRepo;
        $this->contactRepository    = $contactRepository;
        $this->checkoutRepository   = $checkoutRepository;
        $this->basketService        = $basketService;

        $this->init();
    }

    /**
     * @param null $classId
     * @return $this
     */
    public function setClassId($classId)
    {
        $this->classId = $classId;
        return $this;
    }

    /**
     * @param null $currency
     * @return $this
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @param null $shippingCountryId
     * @return $this
     */
    public function setShippingCountryId($shippingCountryId)
    {
        $this->shippingCountryId = $shippingCountryId;
        return $this;
    }

    private function init()
    {
        $contact = $this->contactRepository->getContact();

        if ($contact instanceof Contact) {
            $this->classId      = $contact->classId;
            $this->singleAccess = $contact->singleAccess;
        }

        $this->currency          = $this->checkoutRepository->getCurrency();
        $this->shippingCountryId = $this->checkoutRepository->getShippingCountryId();
        $this->plentyId          = $this->app->getPlentyId();
        $this->referrerId        = $this->basketService->getBasket()->referrerId;
    }

    /**
     * @param int $variationId
     * @param string $type
     * @param int $quantity
     * @return SalesPriceSearchResponse
     */
    public function getSalesPriceForVariation(int $variationId, $type = 'default', int $quantity = 1)
    {
        /**
         * @var SalesPriceSearchRequest $salesPriceSearchRequest
         */
        $salesPriceSearchRequest = $this->getSearchRequest($variationId, $type, $quantity);
        
        /**
         * @var SalesPriceSearchResponse $salesPrice
         */
        $salesPrice = $this->salesPriceSearchRepo->search($salesPriceSearchRequest);


        return $this->applyCurrencyConversion($salesPrice);
    }
    
    /**
     * @param int $variationId
     * @param string $type
     * @param int $quantity
     * @return array
     */
    public function getAllSalesPricesForVariation(int $variationId, $type = 'default')
    {
        /**
         * @var SalesPriceSearchRequest $salesPriceSearchRequest
         */
        $salesPriceSearchRequest = $this->getSearchRequest($variationId, $type, -1);
    
        /**
         * @var array $salesPrices
         */
        $salesPrices = $this->salesPriceSearchRepo->searchAll($salesPriceSearchRequest);

        $convertedSalesPrices = [];
        foreach( $salesPrices as $salesPrice )
        {
            $convertedSalesPrices[] = $this->applyCurrencyConversion( $salesPrice );
        }

        return $convertedSalesPrices;
    }

    public function applyCurrencyConversion( SalesPriceSearchResponse $salesPrice ): SalesPriceSearchResponse
    {
        $salesPrice->price                      = $salesPrice->price * $salesPrice->conversionFactor;
        $salesPrice->priceNet                   = $salesPrice->priceNet * $salesPrice->conversionFactor;
//        $salesPrice->basePrice                  = $salesPrice->basePrice * $salesPrice->conversionFactor;
//        $salesPrice->basePriceNet               = $salesPrice->basePriceNet * $salesPrice->conversionFactor;
//        $salesPrice->unitPrice                  = $salesPrice->unitPrice * $salesPrice->conversionFactor;
//        $salesPrice->unitPriceNet               = $salesPrice->unitPriceNet * $salesPrice->conversionFactor;
        $salesPrice->customerClassDiscount      = $salesPrice->customerClassDiscount * $salesPrice->conversionFactor;
        $salesPrice->customerClassDiscountNet   = $salesPrice->customerClassDiscountNet * $salesPrice->conversionFactor;
        $salesPrice->categoryDiscount           = $salesPrice->categoryDiscount * $salesPrice->conversionFactor;
        $salesPrice->categoryDiscountNet        = $salesPrice->categoryDiscountNet * $salesPrice->conversionFactor;

        return $salesPrice;
    }

    private  function getSearchRequest(int $variationId, $type, int $quantity)
    {
        /**
         * @var SalesPriceSearchRequest $salesPriceSearchRequest
         */
        $salesPriceSearchRequest = pluginApp(SalesPriceSearchRequest::class);
    
        $salesPriceSearchRequest->variationId     = $variationId;
        $salesPriceSearchRequest->accountId       = 0;
        $salesPriceSearchRequest->accountType     = $this->singleAccess;
        $salesPriceSearchRequest->countryId       = $this->shippingCountryId;
        $salesPriceSearchRequest->currency        = $this->currency;
        $salesPriceSearchRequest->customerClassId = $this->classId;
        $salesPriceSearchRequest->plentyId        = $this->plentyId;
        $salesPriceSearchRequest->quantity        = $quantity;
        $salesPriceSearchRequest->referrerId      = $this->referrerId;
        $salesPriceSearchRequest->type            = $type;
        
        return $salesPriceSearchRequest;
    }
}
