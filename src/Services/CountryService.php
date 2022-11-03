<?php //strict

namespace IO\Services;

use IO\Helper\Utils;
use Plenty\Modules\Frontend\Contracts\Checkout;
use Plenty\Modules\Order\Shipping\Contracts\EUCountryCodesServiceContract;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Order\Shipping\Countries\Models\Country;
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

    /**
     * @var Country[][] Active countries
     */
    private static $activeCountries = [];

    /**
     * CountryService constructor.
     * @param CountryRepositoryContract $countryRepository Repository used for manipulating country data
     */
    public function __construct(CountryRepositoryContract $countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }

    public function getEUCountriesList($lang = null): array
    {
        if ($lang === null) {
            $lang = Utils::getLang();
        }
        $list = $this->countryRepository->getCountriesList(null, ['states', 'names']);

        /** @var EUCountryCodesServiceContract $euCountryService */
        $euCountryService = pluginApp(EUCountryCodesServiceContract::class);
        $euCountryList = [];

        foreach ($list as $country) {
            if ($euCountryService->isEUCountry($country->id)) {
                $euCountryList[] = [
                    'id' => $country->id,
                    'currLangName' => $this->getCountryNameByLang($country, $lang),
                    'isoCode2' => $country->isoCode2,
                    'states' => $country->states,
                    'vatCodes' => $country->vatCodes
                ];
            }
        }

        return $euCountryList;
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

        if (!isset(self::$activeCountries[$lang])) {
            $list = $this->countryRepository->getActiveCountriesList();

            foreach ($list as $country) {
                $country->currLangName = $this->getCountryNameByLang($country, $lang);
                self::$activeCountries[$lang][] = $country;
            }
        }

        $column = array_column(self::$activeCountries[$lang], "currLangName");
        array_multisort($column, SORT_ASC, SORT_LOCALE_STRING, self::$activeCountries[$lang]);

        return self::$activeCountries[$lang];
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

    /**
     * Get country name for given language
     * Fall back to ID if no name found
     * 
     * @param object $country Country with a name
     * @param string $lang Language for country name
     * @return string
     */
    private function getCountryNameByLang($country, $lang): string {
        if ($country->currLangName = $country->names->contains('language', $lang)) {
            return $country->names->where('language', $lang)->first()->name;
        }
        if ($country->names->first()->name) {
            return $country->names->first()->name;
        }

        $this
            ->getLogger(__CLASS__)
            ->error('IO::Debug.CountryService_noNameFound', [
                'id' => $country->id
            ]);

        return 'ID: ' . $country->id;
    }
}
