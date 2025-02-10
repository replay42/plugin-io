<?php //strict

namespace IO\Api\Resources;

use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Http\Request;
use IO\Api\ApiResource;
use IO\Api\ApiResponse;
use IO\Api\ResponseCode;
use IO\Services\ShopBuilderService;

/**
 * Class ShopBuilderResource
 * @package IO\Api\Resources
 */
class ShopBuilderResource extends ApiResource
{
    /**
     * @var ShopBuilderService $shopBuilderService The instance of the current ShopBuilderService.
     */
    private $shopBuilderService;

    /**
     * ShopBuilderResource constructor.
     * @param Request $request
     * @param ApiResponse $response
     * @param ShippingService $shippingService
     */
    public function __construct(Request $request, ApiResponse $response, ShopBuilderService $shopBuilderService)
    {
        parent::__construct($request, $response);
        $this->shopBuilderService = $shopBuilderService;
    }

    /**
     * @return Response
     */
    public function index(): Response
    {
        $response = "";

        $categoryId = $this->request->get('categoryId', null);

        $response = $this->shopBuilderService->getContent($categoryId);
        return $this->response->create($response, ResponseCode::OK);
    }

}