<?php //strict
namespace IO\Controllers;

use Plenty\Plugin\Http\Response;
use bkWishlist\Services\WishlistService;
use Plenty\Modules\Webshop\ItemSearch\Services\ItemSearchService;
use Plenty\Modules\Webshop\ItemSearch\SearchPresets\BasketItems;
use Plenty\Plugin\Log\Loggable;
/**
 * Class WishListController
 * @package IO\Controllers
 */
class ItemWishListController extends LayoutController
{
    use Loggable;

    /**
     * Render the wish list
     * @return string
     */
    public function showWishList():string
    {
        return $this->renderTemplate(
			"tpl.wish-list",
			[
                "listOverview" => true
            ],
            true
		);
    }

    public function showWishListDetail( int $wishlistId ): string
    {
        $templateData = [];
        $templateData['listAccessible'] = false;
        if($wishlistId <= 0)
            return $this->listNotFound();

        $templateData['wishlistId'] = $wishlistId;

        // Request Wishlist Data
        $wishlistService = pluginApp(WishlistService::class);
        $list = $wishlistService->getListWithItems($wishlistId);

        // List not found / not accessible
        if(is_null($list)) {
            $this->getLogger(__CLASS__)
                ->error("Private Wishlist not found. Showing PageNotFound", $templateData);
            return $this->listNotFound();
        }

        $templateData['listAccessible'] = true;
        $templateData['isPublic'] = false;
        $templateData['wishlistData'] = $list;

        return $this->renderTemplate(
            "tpl.wish-list.detail",
            $templateData,
            false
        );
    }

    public function showWishListByAccessCode( $accessCode ): string
    {
        $wishlistService = pluginApp(WishlistService::class);
        $list = $wishlistService->getByAccessCode($accessCode);

        if (is_null($list))
        {
            $this->getLogger(__CLASS__)
                ->error("Public Wishlist not found. Redirect to PageNotFound", ['accessCode' => $accessCode]);
            return $this->listNotFound();
        }

        $templateData = [];
        $templateData['listAccessible'] = true;
        $templateData['isPublic'] = true;
        $templateData['wishlistData'] = $list;

        return $this->renderTemplate(
            "tpl.wish-list.access-code",
            $templateData,
            false
        );
    }

    

    public function listNotFound()
    {
        return $this->renderTemplate(
            "tpl.page-not-found",
            [
                "object" => ""
            ],
            false
        );
    }

    public function redirect()
    {
        if(!is_null($categoryByUrl = $this->checkForExistingCategory())) {
            return $categoryByUrl;
        }

        /** @var CategoryController $categoryController */
        #$categoryController = pluginApp(CategoryController::class);
        #return $categoryController->redirectRoute(RouteConfig::WISH_LIST);
        return;
    }
}
