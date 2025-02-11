<?php

namespace IO\Services;

use IO\Helper\Utils;
use Plenty\Modules\Frontend\Contracts\Checkout;
use Plenty\Modules\Order\Shipping\Contracts\EUCountryCodesServiceContract;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Order\Shipping\Countries\Models\Country;
use Plenty\Modules\Webshop\Contracts\CountryRepositoryContract as WebshopCountryRepositoryContract;
use Plenty\Plugin\Log\Loggable;

/**
 * Class CountryService
 *
 * This service class contains methods related to countries.
 * All public functions are available in the Twig template renderer.
 *
 * @package IO\Services
 */
class CountryService
{
    use Loggable;

    /**
     * @var CountryRepositoryContract Repository used for manipulating country data
     */
    private $countryRepository;

    /** @var EUCountryCodesServiceContract */
    private $euCountryService;

    /**
     * CountryService constructor.
     * @param CountryRepositoryContract $countryRepository Repository used for manipulating country data
     */
    public function __construct(
        CountryRepositoryContract $countryRepository,
        EUCountryCodesServiceContract $euCountryService
    ) {
        $this->countryRepository = $countryRepository;
        $this->euCountryService = $euCountryService;
    }

    public function hasEUShippingCountry()
    {
        $activeCountriesList = $this->getActiveCountriesList();
        foreach ($activeCountriesList as $activeCountry) {
            if ($this->euCountryService->isEUCountry($activeCountry->id)) {
                return true;
            }
        }

        return false;
    }

    public function getEUCountriesList($lang = null): array
    {
        if ($lang === null) {
            $lang = Utils::getLang();
        }

        /** @var WebshopCountryRepositoryContract $countryRepository */
        $countryRepository = pluginApp(WebshopCountryRepositoryContract::class);
        return $countryRepository->getEUCountriesList($lang);
    }

    /**
     * List all active countries
     *
     * @param string|null $lang Optional: Language for country names
     * @return Country[]
     */
    public function getActiveCountriesList($lang = null): array
    {
        if ($lang === null) {
            $lang = Utils::getLang();
        }

        /** @var WebshopCountryRepositoryContract $countryRepository */
        $countryRepository = pluginApp(WebshopCountryRepositoryContract::class);
        $countryList = $countryRepository->getActiveCountriesList($lang);

        $compareFunction = function ($a, $b) {
            $aName = str_replace(["Ä", "ä", "Ö", "ö", "Ü", "ü", "ß"], ["Ae", "ae", "Oe", "oe", "Ue", "ue", "ss"], $a['currLangName']);
            $bName = str_replace(["Ä", "ä", "Ö", "ö", "Ü", "ü", "ß"], ["Ae", "ae", "Oe", "oe", "Ue", "ue", "ss"], $b['currLangName']);
            return strcmp(strtolower($aName), strtolower($bName));
        };

        usort($countryList, $compareFunction);
        return $countryList;
    }

    /**
     * Get a list of names for the active countries
     *
     * @param string $language Language of names
     * @return array
     */
    public function getActiveCountryNameMap(string $language): array
    {
        $nameMap = [];
        foreach ($this->getActiveCountriesList($language) as $country) {
            $nameMap[$country->id] = $country->currLangName;
        }

        return $nameMap;
    }


    /**
     * Get the id of the current shipping country
     *
     * @return int $shippingCountryId
     */
    public function getShippingCountryId()
    {
        /** @var Checkout $checkout */
        $checkout = pluginApp(Checkout::class);
        return $checkout->getShippingCountryId();
    }

    /**
     * Set the id of the current shipping country
     *
     * @param int $shippingCountryId Id of shippingCountry
     */
    public function setShippingCountryId(int $shippingCountryId)
    {
        /** @var Checkout $checkout */
        $checkout = pluginApp(Checkout::class);
        $checkout->setShippingCountryId($shippingCountryId);
    }

    /**
     * Get a specific Country model by id
     *
     * @param int $countryId Id of country
     * @return Country
     */
    public function getCountryById(int $countryId): Country
    {
        return $this->countryRepository->getCountryById($countryId);
    }

    /**
     * Get the name of specific country
     *
     * @param int $countryId Id of country to get name from
     * @param string|null $lang Optional: Language for country name
     * @return string
     */
    public function getCountryName(int $countryId, string $lang = null): string
    {
        if ($lang === null) {
            $lang = Utils::getLang();
        }

        $country = $this->countryRepository->getCountryById($countryId);
        if ($country instanceof Country && count($country->names) != 0) {
            foreach ($country->names as $countryName) {
                if ($countryName->language == $lang) {
                    return $countryName->name;
                }
            }
            return $country->names[0]->name;
        }
        return "";
    }
}
