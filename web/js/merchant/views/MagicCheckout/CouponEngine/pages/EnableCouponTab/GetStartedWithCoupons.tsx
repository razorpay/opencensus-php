import React, { useContext } from 'react';
import { connect } from 'react-redux';
import { Heading, Link } from '@razorpay/blade/components';
import { bindActionCreators, Dispatch } from 'redux';
import lazy from 'merchant/routes/LazyLoader';

// assets imports
import shopifyLogo from 'assets/app-store/partner-logos/shopify.png';
import rzpLogo from 'assets/logo-dark.png';

// ui imports
import { SyncStatus } from 'merchant/views/MagicCheckout/CouponEngine/styles/CouponStatus';

// helper imports
import { openCreateCouponModal } from 'merchant/views/MagicCheckout/CouponEngine/helpers';
import { openModal } from 'merchant_common/reducers/modals';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';
import { Section, Card, CardHeader, ToggleWrapper } from './styled';

const ShopifySyncModal = lazy(
  () =>
    import(
      /* webpackChunkName: 'MagicCouponEngineShopifySyncModal' */ 'merchant/views/MagicCheckout/CouponEngine/components/ShopifySyncModal'
    ),
);

interface Props {
  openModal: (options: { size: string; className: string; component: JSX.Element }) => void;
  org: any;
}

const GetStartedWithCoupons: React.FC<Props> = ({ openModal, org }) => {
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
    <Section>
      <Heading as="h3" size="large" weight="semibold" color="surface.text.gray.normal">
        Get started with Coupons
      </Heading>
      <Card>
        <CardHeader>
          <CardHeader.Icon src={rzpLogo} alt="" role="presentation" />
          <CardHeader.Title as="h4">Coupons on Magic</CardHeader.Title>
          <ToggleWrapper>
            <Link
              variant="button"
              onClick={() => openCreateCouponModal(openModal)}
              testID="create-coupon-cta"
            >
              Create Coupon
            </Link>
          </ToggleWrapper>
        </CardHeader>
        <p>
          Create, edit and add coupons on Checkout to increase customer engagement and reduce cart
          abandonment
        </p>
      </Card>
      {shopifySyncStatus && (
        <Card>
          <CardHeader>
            <CardHeader.Icon src={shopifyLogo} alt="shopify" role="presentation" />
            <CardHeader.Title as="h4">
              Coupons from Shopify{' '}
              {shopifySyncStatus.status === 'in-progress' ||
              shopifySyncStatus.status === 'completed' ? (
                <SyncStatus variant={(shopifySyncStatus.status as 'completed') || 'in-progress'}>
                  {shopifySyncStatus.status === 'completed' ? 'Sync Completed' : 'Sync in progress'}
                </SyncStatus>
              ) : null}
            </CardHeader.Title>
            {shopifySyncStatus.status === 'not-started' && (
              <Link variant="button" onClick={openShopifySyncModal} testID="sync-to-shopify-cta">
                Sync now
              </Link>
            )}
          </CardHeader>
          <p>
            {shopifySyncStatus.status === 'not-started' || shopifySyncStatus.status === '' ? (
              <div>
                Sync your coupons from Shopify to view and manage them from your {org.business_name}{' '}
                dashboard
              </div>
            ) : (
              <div>
                Your coupons have been successfully synced from{' '}
                <strong>{shopifySyncStatus?.last_sync_dates?.start_date}</strong> to{' '}
                <strong>{shopifySyncStatus?.last_sync_dates?.end_date}</strong>.
              </div>
            )}
          </p>
        </Card>
      )}
    </Section>
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
export default connect(mapStateToProps, mapDispatchToProps)(GetStartedWithCoupons);
