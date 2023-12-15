import React, { Fragment, useCallback, useState, useEffect } from 'react';
import { connect } from 'react-redux';

// helper imports
import { getItem, setItem } from 'common/utils/localStorage';
import { useSplitzService } from 'common/splitz';

// ui components
import EnableCouponBanner from 'merchant/views/MagicCheckout/CouponEngine/components/EnableCouponBanner/EnableCouponBanner';
import MainContent from 'merchant/views/MagicCheckout/CouponEngine/components/MainContent';
import SideNav from 'merchant/views/MagicCheckout/CouponEngine/components/SideNav/SideNav';
import { PromotionalBanner } from 'merchant/views/MagicCheckout/CouponEngine/pages';
import Loader from 'common/components/Loader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

// constant imports
import { getNavItems } from 'merchant/views/MagicCheckout/CouponEngine/constants';

// api imports
import {
  getSyncShopifyCouponsStatus,
  listCoupons,
} from 'merchant/views/MagicCheckout/CouponEngine/api';

//style imports
import 'merchant/views/MagicCheckout/css/coupon-engine/promotional-banner.styl';

interface MainPageProps {
  merchantId: string;
  initialCouponEngineEnabled: boolean;
  updatedCouponEngineEnabled: boolean | null | undefined;
}

const MainPage: React.FC<MainPageProps> = ({
  merchantId,
  initialCouponEngineEnabled,
  updatedCouponEngineEnabled,
}) => {
  const { abExperiments } = useSplitzService();

  const isShopifyCouponSyncEnabled =
    abExperiments?.magic_shopify_coupon_sync?.variables?.result === 'on';

  const NAV_ITEMS = getNavItems(isShopifyCouponSyncEnabled);
  const [activeNav, setActiveNav] = useState<string>(NAV_ITEMS[0].id);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [shouldShowPromotionalBanner, setShouldShowPromotionalBanner] = useState<boolean>(
    getItem(`showCouponBanner-${merchantId}`) !== 'false',
  );

  const fetchInitialDataWithSync = async () => {
    setIsLoading(true);
    try {
      const [statusResponse, couponsResponse] = await Promise.allSettled([
        getSyncShopifyCouponsStatus(),
        listCoupons({ count: 1 }),
      ]);

      // Check the status of each promise
      const isStatusSuccess = statusResponse.status === 'fulfilled';
      const isCouponsSuccess = couponsResponse.status === 'fulfilled';

      // Extract data from the responses if successful
      const statusData = isStatusSuccess ? statusResponse.value.data : {};
      const couponsData = isCouponsSuccess ? couponsResponse.value.data : {};

      // Check conditions and set activeNav
      if (
        isStatusSuccess &&
        statusData.status === 'not-started' &&
        isCouponsSuccess &&
        couponsData.coupons.length === 0
      ) {
        setActiveNav(NAV_ITEMS[0].id);
      } else {
        setActiveNav(NAV_ITEMS[1].id);
      }
    } catch (errors) {
      setActiveNav(NAV_ITEMS[0].id);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    if (isShopifyCouponSyncEnabled) {
      fetchInitialDataWithSync();
    }
  }, []);

  const setContent = useCallback(
    (selectedTab: string) =>
      NAV_ITEMS.map((item) =>
        item.id === selectedTab ? <div key={item.id}>{item.component}</div> : null,
      ),
    [],
  );

  const handlePromotionalBannerClose = () => {
    setShouldShowPromotionalBanner(false);
    setItem(`showCouponBanner-${merchantId}`, 'false');
  };

  // reason for checking updatedCouponEngineEnabled is because we need to show the banner only when the user has enabled the coupon engine, and currently we have two stores which can have its data. Either a config call which is called on the page load or if a user has enabled the coupon engine from the settings page. So we need to check both the values and show the banner accordingly.
  const getCouponEngineLatestStatus = () => {
    if (updatedCouponEngineEnabled !== null) {
      return updatedCouponEngineEnabled;
    }
    return initialCouponEngineEnabled;
  };

  return (
    <Fragment>
      {shouldShowPromotionalBanner ? (
        <div>
          <PromotionalBanner handlePromotionalBannerClose={handlePromotionalBannerClose} />
        </div>
      ) : (
        <Fragment>
          {!getCouponEngineLatestStatus() && <EnableCouponBanner />}
          <div className="display-flex nav-container">
            {isLoading ? (
              <div className="display-flex flex-center width-100">
                <Loader />
              </div>
            ) : (
              <SuspenseWithLoader type="center">
                <SideNav
                  tabs={NAV_ITEMS}
                  onTabClick={(id) => setActiveNav(id)}
                  activeNav={activeNav}
                />
                <MainContent activeNav={activeNav} render={setContent} />
              </SuspenseWithLoader>
            )}
          </div>
        </Fragment>
      )}
    </Fragment>
  );
};

const mapStateToProps = (state: {
  magicCheckout: any;
  magic_settings: any;
  config: { config: { id: string } };
}) => ({
  merchantId: state.config?.config?.id || '',
  initialCouponEngineEnabled: state.magicCheckout.one_cc_coupon_engine,
  updatedCouponEngineEnabled: state.magic_settings.one_cc_coupon_engine,
});

export default connect(mapStateToProps, null)(MainPage);
