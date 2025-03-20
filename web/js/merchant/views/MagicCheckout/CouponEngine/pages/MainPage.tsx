import React, { Fragment, useCallback, useState, useEffect, useContext } from 'react';
import { connect } from 'react-redux';

// helper imports
import { getItem, setItem } from 'common/utils/localStorage';
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

// ui components
import EnableCouponBanner from 'merchant/views/MagicCheckout/CouponEngine/components/EnableCouponBanner/EnableCouponBanner';
import MainContent from 'merchant/views/MagicCheckout/CouponEngine/components/MainContent';
import SideNav from 'merchant/views/MagicCheckout/CouponEngine/components/SideNav/SideNav';
import { PromotionalBanner } from 'merchant/views/MagicCheckout/CouponEngine/pages';
import Loader from 'common/components/Loader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

// api imports
import {
  getSyncShopifyCouponsStatus,
  listCoupons,
} from 'merchant/views/MagicCheckout/CouponEngine/api';

//style imports
import 'merchant/views/MagicCheckout/css/coupon-engine/promotional-banner.styl';

// constant imports
import { getNavItems, COUPON_NAMES } from 'merchant/views/MagicCheckout/CouponEngine/constants';
import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';

const initialFiltersState = {
  type: 'all',
  code: '',
  status: 'all',
  sort_by: 'date-desc',
  skip: 0,
  count: 10,
  display: 'all',
  source: 'all',
};

interface MainPageProps {
  merchantId: string;
  initialCouponEngineEnabled: boolean;
  updatedCouponEngineEnabled: boolean | null | undefined;
  isRcodEnabled: boolean;
}

const MainPage: React.FC<MainPageProps> = ({
  merchantId,
  initialCouponEngineEnabled,
  updatedCouponEngineEnabled,
  isRcodEnabled,
}) => {
  const NAV_ITEMS = getNavItems();
  const [activeNav, setActiveNav] = useState<string>(NAV_ITEMS[0].id);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const isFreebieCouponExpEnabled = useMagicExperiment('freebie_coupon');
  const { setAllCouponsList, setShopifySyncStatus } = useContext(ModalContext);
  const [shouldShowPromotionalBanner, setShouldShowPromotionalBanner] = useState<boolean>(
    getItem(`showCouponBanner-${merchantId}`) !== 'false',
  );

  const fetchInitialDataWithSync = async () => {
    try {
      setIsLoading(true);

      const [statusResponse, couponsResponse] = await Promise.allSettled([
        getSyncShopifyCouponsStatus(),
        listCoupons(initialFiltersState),
      ]);

      const isStatusSuccess = statusResponse.status === 'fulfilled';
      const isCouponsSuccess = couponsResponse.status === 'fulfilled';

      const statusData = isStatusSuccess ? statusResponse.value.data : {};
      const couponsData = isCouponsSuccess ? couponsResponse.value.data : {};

      const setActiveNavAndCouponsList = (navIndex, couponsList) => {
        setActiveNav(NAV_ITEMS[navIndex].id);
        setAllCouponsList(couponsList);
      };

      if (
        isStatusSuccess &&
        statusData.status === 'not-started' &&
        isCouponsSuccess &&
        couponsData.coupons.length === 0
      ) {
        setActiveNavAndCouponsList(0, []);
      } else {
        /**
         * For MagicX , Free shipping coupon type and bulk order discount is not available
         */
        const excludedCouponsForMagicX = [COUPON_NAMES.FREE_SHIPPING, COUPON_NAMES.BULK_ORDER];
        const excludedCouponsForMagicCheckout: string[] = [];
        if (!isFreebieCouponExpEnabled) {
          excludedCouponsForMagicX.push(COUPON_NAMES.FREEBIE_ITEM);
          excludedCouponsForMagicCheckout.push(COUPON_NAMES.FREEBIE_ITEM);
        }
        const excludedCouponTypes = isRcodEnabled
          ? excludedCouponsForMagicX
          : excludedCouponsForMagicCheckout;

        const couponsList = couponsData?.coupons?.filter(
          (coupon) => !excludedCouponTypes.includes(coupon.type),
        );

        setActiveNavAndCouponsList(1, couponsList);
      }
      const syncStatus = isStatusSuccess
        ? statusData
        : {
            status: 'not-started',
            last_sync_dates: {
              start_date: '',
              end_date: '',
            },
          };

      setShopifySyncStatus(syncStatus);
    } catch (errors) {
      setActiveNav(NAV_ITEMS[0].id);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchInitialDataWithSync();
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
  isRcodEnabled: state.magicCheckout.rcod,
});

export default connect(mapStateToProps, null)(MainPage);
