<?php

namespace IO\Tests\Unit;

use IO\Helper\MemoryCache;
use Mockery;
use IO\Tests\TestCase;
use Plenty\Modules\Frontend\Contracts\Checkout;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Plugin\Models\Plugin;
use Illuminate\Support\Facades\Session;
use Plenty\Modules\System\Models\WebstoreConfiguration;
use Plenty\Modules\Webshop\Contracts\CheckoutRepositoryContract;
use Plenty\Modules\Webshop\Contracts\SessionStorageRepositoryContract;
use Plenty\Modules\Webshop\Contracts\WebstoreConfigurationRepositoryContract;

/**
 * User: mklaes
 * Date: 08.08.18
 */
class CheckoutServiceCurrencyTest extends TestCase
{

    /** @var CheckoutRepositoryContract $checkoutRepository */
    protected $checkoutRepository;
    /** @var FrontendSessionStorageFactoryContract */
    protected $sessionStorageMock;
    /** @var Plugin $pluginMock */
    protected $pluginMock;
    /** @var WebstoreConfigurationRepositoryContract $webstoreConfigurationRepositoryMock */
    protected $webstoreConfigurationRepositoryMock;
    /** @var Checkout $checkoutMock */
    protected $checkoutMock;
    /** @var SessionStorageRepositoryContract $sessionStorageRepositoryMock */
    protected $sessionStorageRepositoryMock;
    /** @var MemoryCache $memoryCacheMock */
    protected $memoryCacheMock;


    protected function setUp()
    {
        parent::setUp();

        $this->createApplication();

        $this->sessionStorageMock = Mockery::mock(FrontendSessionStorageFactoryContract::class);
        $this->replaceInstanceByMock(FrontendSessionStorageFactoryContract::class, $this->sessionStorageMock);

        $this->pluginMock = Mockery::mock(Plugin::class);
        $this->replaceInstanceByMock(Plugin::class, $this->pluginMock);

        $this->webstoreConfigurationRepositoryMock = Mockery::mock(WebstoreConfigurationRepositoryContract::class);
        $this->replaceInstanceByMock(
            WebstoreConfigurationRepositoryContract::class,
            $this->webstoreConfigurationRepositoryMock
        );

        $this->checkoutMock = Mockery::mock(Checkout::class);
        $this->replaceInstanceByMock(Checkout::class, $this->checkoutMock);

        $this->sessionStorageRepositoryMock = Mockery::mock(SessionStorageRepositoryContract::class);
        $this->replaceInstanceByMock(SessionStorageRepositoryContract::class, $this->sessionStorageRepositoryMock);

        $this->checkoutRepository = pluginApp(CheckoutRepositoryContract::class);
    }

    /** @test */
    public function it_returns_the_currency_from_session_storage()
    {
        $expectedCurrency = $this->fake->currencyCode;

        $this->pluginMock->shouldReceive('getValue')->with(SessionStorageRepositoryContract::CURRENCY)->andReturn(
            $expectedCurrency
        );
        $this->sessionStorageMock->shouldReceive('getPlugin')->andReturn($this->pluginMock);

        $currency = $this->checkoutRepository->getCurrency();

        $this->assertNotNull($currency);
        $this->assertEquals($expectedCurrency, $currency);
    }


    /** @test */
    public function it_returns_the_currency_not_from_session_storage_and_webstore_config()
    {
        $webstoreConfiguration = factory(WebstoreConfiguration::class)->make();

        $expectedCurrency = $webstoreConfiguration->defaultCurrency;

        $this->pluginMock->shouldReceive('getValue')->with(SessionStorageRepositoryContract::CURRENCY)->andReturn(null);
        $this->pluginMock->shouldReceive('setValue')->andReturn();
        $this->sessionStorageMock->shouldReceive('getPlugin')->andReturn($this->pluginMock);

        $this->webstoreConfigurationRepositoryMock->shouldReceive('getWebstoreConfiguration')->andReturn(
            $webstoreConfiguration
        );

        $this->checkoutMock->shouldReceive('setCurrency')->andReturn();

        $currency = $this->checkoutRepository->getCurrency();

        $this->assertNotNull($currency);
        $this->assertEquals($expectedCurrency, $currency);
    }

    /** @test */
    public function it_returns_the_currency_not_from_session_storage_and_not_from_the_webstore_config()
    {
        $webstoreConfiguration = factory(WebstoreConfiguration::class)->make(
            [
                "defaultCurrencyList" => "",
            ]
        );

        $expectedCurrency = "EUR";

        $this->pluginMock->shouldReceive('getValue')->with(SessionStorageRepositoryContract::CURRENCY)->andReturn(null);
        $this->pluginMock->shouldReceive('setValue')->andReturn();
        $this->sessionStorageMock->shouldReceive('getPlugin')->andReturn($this->pluginMock);

        Session::shouldReceive('getLang')
            ->andReturn("");

        $this->webstoreConfigurationRepositoryMock->shouldReceive('getWebstoreConfiguration')->andReturn($webstoreConfiguration);

        $this->checkoutMock->shouldReceive('setCurrency')->andReturn();

        $currency = $this->checkoutRepository->getCurrency();

        $this->assertNotNull($currency);
        $this->assertEquals($expectedCurrency, $currency);
    }

}
