import React, { Suspense, useEffect } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import lazy, { lazyRetry } from 'merchant/routes/LazyLoader';
import { fetchPricingSubscription as fetchPricingSubscriptionProps } from 'merchant/reducers/growthService';
import { getCookie } from 'common/utils/cookies';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import GrowthAssetEB from 'common/ui/GrowthAssetEB';
import { withRouter } from 'common/deprecated/withRouter';
import { LS_LABELS } from 'common/ui/PricingSubscription/constants';
import { useSplitzService } from 'common/splitz';
import { isExperimentActive } from 'common/utils/rzp-utils';

const LazyPricingBundleMweb = lazy(
  () =>
    import(
      /* webpackChunkName: 'PricingSubscriptionMWebComponent Mweb' */ 'common/ui/PricingSubscription/Mobile/BottomSheetPricingContainer'
    ),
);

export const PricingBundle = ({
  user,
  openModal,
  pricing_bundles_obj,
  fetchPricingSubscription,
  location,
  mode,
  isMobileResolution,
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
  const { abExperiments: { STREAKS_REWARDS_GROWTH } = {} } = useSplitzService();

  const isStreaksExperimentEnabled = isExperimentActive(STREAKS_REWARDS_GROWTH);

  const isAllowedToFetch =
    !isStreaksExperimentEnabled && // if merchant is part of customerGLU segment then don't open pricing bundle
    user?.isBundlePricingEnabled &&
    mode === 'live' &&
    impressionCount < maxImpressions &&
    !isWithinTimeInterval &&
    !isNotInterested;

  useEffect(() => {
    if (isAllowedToFetch) fetchPricingSubscription({ fromWhere: location?.pathname });
  }, []);

  const showPricingBundleWeb =
    isAllowedToFetch && pricing_bundles && Object.keys(pricing_bundles).length;

  const openPricingSubcriptionModal = async (loading) => {
    if (showPricingBundleWeb && !loading) {
      const lazyPricingSubscriptionImport = await lazyRetry(
        () =>
          import(
            /* webpackChunkName: 'PricingSubscriptionComponent Dweb ' */ 'common/ui/PricingSubscription/PricingSubscriptionComponent'
          ),
      );
      const LazyPricingSubscriptionComponent = lazy(
        () => new Promise((resolve) => resolve(lazyPricingSubscriptionImport)),
      );
      return openModal({
        closeOnOverLay: true,
        component: (
          <Suspense fallback={null}>
            <LazyPricingSubscriptionComponent pricingSubscription={pricing_bundles} />
          </Suspense>
        ),
      });
    }
    return null;
  };

  useEffect(() => {
    if (!isMobileResolution) openPricingSubcriptionModal(loading);
  }, [loading, isMobileResolution]);

  return showPricingBundleWeb && isMobileResolution ? (
    <Suspense fallback={null}>
      <LazyPricingBundleMweb pricingSubscription={pricing_bundles} />;
    </Suspense>
  ) : null;
};

const PricingSubscriptionCompose = compose<any>(
  withRouter,
  connect(
    (state) => ({
      user: state.session.user,
      mode: state.session.mode,
      pricing_bundles_obj: state?.growthService?.pricing_bundles || {},
      isMobileResolution: state.app.isMobileResolution,
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
