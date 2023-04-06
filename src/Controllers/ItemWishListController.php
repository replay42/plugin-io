<?php //strict
namespace IO\Controllers;
use IO\Helper\RouteConfig;
/**
 * Class WishListController
 * @package IO\Controllers
 */
class ItemWishListController extends LayoutController
{
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
        return $this->renderTemplate(
            "tpl.wish-list.detail",
            [
                "wishlistId" => $wishlistId
            ],
            true
        );
    }

    public function showWishListByAccessCode( $accessCode ): string
    {
        return $this->renderTemplate(
            "tpl.wish-list.access-code",
            [
                "accessCode" => $accessCode
            ],
            true
        );
    }
 
    public function redirect()
    {
        if(!is_null($categoryByUrl = $this->checkForExistingCategory())) {
            return $categoryByUrl;
        }

        /** @var CategoryController $categoryController */
        $categoryController = pluginApp(CategoryController::class);
        return $categoryController->redirectRoute(RouteConfig::WISH_LIST);
    }
}
