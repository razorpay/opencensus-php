import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch } from 'redux';
import lazy from 'merchant/routes/LazyLoader';

// assets imports
import shopifyLogo from 'assets/app-store/partner-logos/shopify.png';
import rzpLogo from 'assets/logo-dark.png';

// api imports
import { getSyncShopifyCouponsStatus } from 'merchant/views/MagicCheckout/CouponEngine/api';

// ui imports
import { SyncStatus } from 'merchant/views/MagicCheckout/CouponEngine/styles/CouponStatus';
import {
  ContentWrapper,
  Title,
  SettingsCard,
  CardWidget,
  CardWidgetWrapper,
  CardHeader,
  CardHeaderWrapper,
  LogoImage,
  CardTitle,
  CreateCouponLink,
  CardContent,
} from 'merchant/views/MagicCheckout/CouponEngine/pages/EnableCouponTab/EnableCouponTabStyles';

// helper imports
import { openCreateCouponModal } from 'merchant/views/MagicCheckout/CouponEngine/helpers';
import { openModal } from 'merchant_common/reducers/modals';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

const ShopifySyncModal = lazy(
  () =>
    import(
      /* webpackChunkName: 'MagicCouponEngineShopifySyncModal' */ 'merchant/views/MagicCheckout/CouponEngine/components/ShopifySyncModal'
    ),
);

interface EnableCouponsTabProps {
  openModal: (options: { size: string; className: string; component: JSX.Element }) => void;
}

const EnableCouponsTab: React.FC<EnableCouponsTabProps> = ({ openModal }) => {
  const [syncStatus, setSyncStatus] = useState<string>('not-started');

  useEffect(() => {
    const fetchShopifySyncStatus = async () => {
      try {
        const { data } = await getSyncShopifyCouponsStatus();
        setSyncStatus(data?.status || 'not-started');
      } catch (error) {
        console.error('Error fetching data:', error);
      }
    };

    fetchShopifySyncStatus();
  }, []);

  const updateSyncStatus = (status: string) => {
    setSyncStatus(status);
  };

  const openShopifySyncModal = () => {
    openModal({
      size: 'small',
      className: 'order-editing-modal',
      component: (
        <SuspenseWithLoader type="center">
          <ShopifySyncModal updateSyncStatus={updateSyncStatus} />
        </SuspenseWithLoader>
      ),
    });
  };

  return (
    <ContentWrapper className="content-wrapper">
      <Title>Create and sync coupons on Magic checkout</Title>
      <SettingsCard className="settings-card">
        <CardWidget className="settings-card-widget">
          <CardWidgetWrapper>
            <CardHeader style={{ borderBottom: 'none' }}>
              <CardHeaderWrapper>
                <LogoImage src={rzpLogo} alt="Razorpay" className="rzp-logo" />
                <CardTitle>Coupons on Magic</CardTitle>
              </CardHeaderWrapper>
              <CreateCouponLink
                onClick={() => openCreateCouponModal(openModal)}
                data-testid="create-coupon-cta"
              >
                <i className="i i-plus" /> Create Coupon
              </CreateCouponLink>
            </CardHeader>
            <CardContent>
              Create, edit and add coupons on Checkout to increase customer engagement and reduce
              cart abandonment
            </CardContent>
          </CardWidgetWrapper>
        </CardWidget>
      </SettingsCard>
      <SettingsCard className="settings-card">
        <CardWidget className="settings-card-widget">
          <CardWidgetWrapper>
            <CardHeader>
              <CardHeaderWrapper>
                <LogoImage src={shopifyLogo} alt="shopify" className="rzp-logo" />
                <CardTitle>
                  Coupons from Shopify
                  {syncStatus === 'in-progress' ||
                    (syncStatus === 'completed' && (
                      <SyncStatus variant={(syncStatus as 'completed') || 'in-progress'}>
                        {syncStatus === 'completed' ? 'Sync Completed' : 'Sync in progress'}
                      </SyncStatus>
                    ))}
                </CardTitle>
              </CardHeaderWrapper>
              {syncStatus === 'not-started' && (
                <CreateCouponLink onClick={openShopifySyncModal} data-testid="sync-to-shopify-cta">
                  Sync now
                </CreateCouponLink>
              )}
            </CardHeader>
            <CardContent className="display-flex settings-card-widget-content">
              <div>
                Sync your coupons from Shopify to view and manage them from your Razorpay dashboard
              </div>
            </CardContent>
          </CardWidgetWrapper>
        </CardWidget>
      </SettingsCard>
    </ContentWrapper>
  );
};

const mapDispatchToProps = (dispatch: Dispatch) =>
  bindActionCreators(
    {
      openModal,
    },
    dispatch,
  );
export default connect(null, mapDispatchToProps)(EnableCouponsTab);
