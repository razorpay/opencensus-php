import React, { useContext } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch } from 'redux';
import lazy from 'merchant/routes/LazyLoader';

// assets imports
import shopifyLogo from 'assets/app-store/partner-logos/shopify.png';
import rzpLogo from 'assets/logo-dark.png';

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

import EnableCouponsOnCheckout from 'merchant/views/MagicCheckout/CouponEngine/pages/EnableCouponTab/EnableCouponsOnCheckout';

// helper imports
import { openCreateCouponModal } from 'merchant/views/MagicCheckout/CouponEngine/helpers';
import { openModal } from 'merchant_common/reducers/modals';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';
import { MAGIC_DASHBOARD_REVAMP_EXPERIMENT } from 'merchant/views/MagicCheckout/constants';

const ShopifySyncModal = lazy(
  () =>
    import(
      /* webpackChunkName: 'MagicCouponEngineShopifySyncModal' */ 'merchant/views/MagicCheckout/CouponEngine/components/ShopifySyncModal'
    ),
);

interface EnableCouponsTabProps {
  openModal: (options: { size: string; className: string; component: JSX.Element }) => void;
  org: any;
}

const EnableCouponsTab: React.FC<EnableCouponsTabProps> = ({ openModal, org }) => {
  const { shopifySyncStatus, setShopifySyncStatus } = useContext(ModalContext);

  const updateSyncStatus = ({
    status,
    start_date,
    end_date,
  }: {
    status: string;
    start_date: string;
    end_date: string;
  }) => {
    setShopifySyncStatus({
      status,
      last_sync_dates: {
        start_date,
        end_date,
      },
    });
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
      {useMagicExperiment(MAGIC_DASHBOARD_REVAMP_EXPERIMENT) && <EnableCouponsOnCheckout />}
      <Title>Create and sync coupons on Magic checkout</Title>
      <SettingsCard className="settings-card">
        <CardWidget className="settings-card-widget">
          <CardWidgetWrapper>
            <CardHeader style={{ borderBottom: 'none' }}>
              <CardHeaderWrapper>
                <LogoImage src={rzpLogo} alt={org.business_name} className="rzp-logo" />
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
      {shopifySyncStatus && (
        <SettingsCard className="settings-card">
          <CardWidget className="settings-card-widget">
            <CardWidgetWrapper>
              <CardHeader>
                <CardHeaderWrapper>
                  <LogoImage src={shopifyLogo} alt="shopify" className="rzp-logo" />
                  <CardTitle>
                    Coupons from Shopify{' '}
                    {shopifySyncStatus.status === 'in-progress' ||
                    shopifySyncStatus.status === 'completed' ? (
                      <SyncStatus
                        variant={(shopifySyncStatus.status as 'completed') || 'in-progress'}
                      >
                        {shopifySyncStatus.status === 'completed'
                          ? 'Sync Completed'
                          : 'Sync in progress'}
                      </SyncStatus>
                    ) : null}
                  </CardTitle>
                </CardHeaderWrapper>
                {shopifySyncStatus.status === 'not-started' && (
                  <CreateCouponLink
                    onClick={openShopifySyncModal}
                    data-testid="sync-to-shopify-cta"
                  >
                    Sync now
                  </CreateCouponLink>
                )}
              </CardHeader>
              <CardContent className="display-flex settings-card-widget-content">
                {shopifySyncStatus.status === 'not-started' || shopifySyncStatus.status === '' ? (
                  <div>
                    Sync your coupons from Shopify to view and manage them from your{' '}
                    {org.business_name} dashboard
                  </div>
                ) : (
                  <div>
                    Your coupons have been successfully synced from{' '}
                    <strong>{shopifySyncStatus?.last_sync_dates?.start_date}</strong> to{' '}
                    <strong>{shopifySyncStatus?.last_sync_dates?.end_date}</strong>.
                  </div>
                )}
              </CardContent>
            </CardWidgetWrapper>
          </CardWidget>
        </SettingsCard>
      )}
    </ContentWrapper>
  );
};

const mapStateToProps = (state) => ({
  org: state.session.org,
});

const mapDispatchToProps = (dispatch: Dispatch) =>
  bindActionCreators(
    {
      openModal,
    },
    dispatch,
  );
export default connect(mapStateToProps, mapDispatchToProps)(EnableCouponsTab);
