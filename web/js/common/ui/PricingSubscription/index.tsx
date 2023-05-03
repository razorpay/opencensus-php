import React, { Suspense, useEffect } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import lazy, { lazyRetry } from 'merchant/routes/LazyLoader';
import { fetchPricingSubscription as fetchPricingSubscriptionProps } from 'merchant/reducers/growthService';
import { getCookie } from 'common/utils/cookies';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import GrowthAssetEB from 'common/ui/GrowthAssetEB';
import { withRouter } from 'react-router';
import { LS_LABELS } from 'common/ui/PricingSubscription/constants';

export const PricingBundle = ({
  user,
  openModal,
  pricing_bundles_obj,
  fetchPricingSubscription,
  location,
  mode,
}): React.ReactElement | null => {
  const maxImpressions = 10;
  const { pricing_bundles, loading } = pricing_bundles_obj || {};
  const impressionCount = Number(
    localStorage.getItem(`${LS_LABELS.IMPRESSION_COUNT}-${user?.current}`),
  );
  const isWithinTimeInterval = Boolean(
    getCookie(`${LS_LABELS.LAST_IMPRESSION_WITHIN_INTERVAL}-${user?.current}`),
  );
  const isNotInterested = Boolean(
    localStorage.getItem(`${LS_LABELS.NOT_INTERESTED}-${user?.current}`),
  );
  const isAllowedToFetch =
    user?.isBundlePricingEnabled &&
    mode === 'live' &&
    impressionCount < maxImpressions &&
    !isWithinTimeInterval &&
    !isNotInterested;

  useEffect(() => {
    if (isAllowedToFetch) fetchPricingSubscription({ fromWhere: location?.pathname });
  }, []);

  const openPricingSubcriptionModal = async (loading) => {
    if (isAllowedToFetch && !loading && pricing_bundles && Object.keys(pricing_bundles).length) {
      const lazyPricingSubscriptionImport = await lazyRetry(
        () =>
          import(
            /* webpackChunkName: 'PricingSubscriptionComponent' */ 'common/ui/PricingSubscription/PricingSubscriptionComponent'
          ),
      );

      const LazyPricingSubscriptionComponent = lazy(
        () => new Promise((resolve) => resolve(lazyPricingSubscriptionImport)),
      );

      openModal({
        closeOnOverLay: true,
        component: (
          <Suspense fallback={null}>
            <LazyPricingSubscriptionComponent pricingSubscription={pricing_bundles} />
          </Suspense>
        ),
      });
    }
  };

  useEffect(() => {
    openPricingSubcriptionModal(loading);
  }, [loading]);

  return null;
};

const PricingSubscriptionCompose = compose<any>(
  withRouter,
  connect(
    (state) => ({
      user: state.session.user,
      mode: state.session.mode,
      pricing_bundles_obj: state?.growthService?.pricing_bundles || {},
    }),
    {
      fetchPricingSubscription: fetchPricingSubscriptionProps,
      openModal: fnOpenModal,
    },
  ),
)(PricingBundle);

const PricingSubscriptionWrapper = (): JSX.Element => {
  return (
    <GrowthAssetEB>
      <PricingSubscriptionCompose />
    </GrowthAssetEB>
  );
};

export default PricingSubscriptionWrapper;
