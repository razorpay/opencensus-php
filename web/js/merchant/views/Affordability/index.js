import React, { useEffect } from 'react';
import ErrorBoundary, { Teams } from 'common/new-ui/ErrorBoundary';

import { Route, Routes, Navigate } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import RTracking from 'react-tracking';

import DashboardBanner from 'common/ui/DashboardBanner';
import { RouteGuard } from 'merchant/components/ShowWhen';
import AffordabilityWidget from './AffordabilityWidget';
import { connect } from 'react-redux';
import { RZPFeatures } from 'merchant/helpers/data';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { fetchAffordabilityWidget } from 'merchant/reducers/affordability/affordabilityWidget';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import {
  getIsAffordabilityWidgetEnabled,
  getIsAllowedResetAffordabilityWidgetOnBoarding,
} from './AffordabilityWidget/Onboarding';
import WidgetEnabledBanner from './components/banners/WidgetEnabledBanner';
import WidgetDisabledBanner from './components/banners/WidgetDisabledBanner';
import SpecialOfferBanner from './components/banners/SpecialOfferBanner';
import { compose } from 'redux';
import OfferBanner from './components/banners/OfferBanner';

const Affordability = (props) => {
  const { affordabilityWidget, affordabilityWidgetProductOnBoarding, user, openModal, closeModal } =
    props;
  const { affordability, loading } = affordabilityWidget;
  const { isTour, showOnboarding } = affordabilityWidgetProductOnBoarding;
  const { trial_period_in_days, enabled, pricing } = affordability;

  useEffect(() => {
    if (window.rzpQ && window.rzpQ.merchantActions) {
      props.tracking.trackEvent(window.rzpQ.merchantActions().success('Affordability_rendered'));
    }
    props.fetchDetails();
  }, []);

  const initAffordabilityWidgetOnboarding = (props) => {
    if (props.affordabilityWidgetProductOnBoarding.isTour) {
      return;
    }
    const data = {
      user: props.user,
      merchantId: props.user.current,
      affordabilityWidget: props.affordabilityWidget,
    };

    const isAffordabilityWidgetEnabled = getIsAffordabilityWidgetEnabled(data);

    let showOnboarding = !isAffordabilityWidgetEnabled;

    if (isAffordabilityWidgetEnabled) {
      showOnboarding = getIsAllowedResetAffordabilityWidgetOnBoarding(data.affordabilityWidget);
    }

    const affordabilityWidgetProductOnBoarding = {
      ...props.affordabilityWidgetProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: false,
    };

    props.handleProductQuickGuide(affordabilityWidgetProductOnBoarding);
  };

  useEffect(() => {
    initAffordabilityWidgetOnboarding(props);
  }, [loading, isTour, enabled]);

  return (
    <>
      <div className="banner-container">
        <DashboardBanner />
        {affordabilityWidget.loading ? null : (
          <>
            {enabled ? <WidgetEnabledBanner trialDays={trial_period_in_days} /> : null}
            {user.isAffordabilityWidgetEnabled && !enabled ? (
              <WidgetDisabledBanner
                {...props}
                openModal={openModal}
                closeModal={closeModal}
                pricing={pricing}
                trialDays={trial_period_in_days}
              />
            ) : null}
            {showOnboarding &&
            ((affordability.pricing && affordability.pricing.default) || trial_period_in_days) ? (
              <SpecialOfferBanner
                pricing={affordability.pricing}
                trialDays={trial_period_in_days}
              />
            ) : null}
            {showOnboarding ? <OfferBanner /> : null}
          </>
        )}
      </div>
      <ErrorBoundary team={Teams.AFFORDABILITY} resetOnProps>
        <Routes>
          <Route index element={<Navigate to="/affordability/widget" replace />} />
          <Route
            path="widget/*"
            element={
              <RouteGuard>
                <AffordabilityWidget />
              </RouteGuard>
            }
          />
        </Routes>
      </ErrorBoundary>
    </>
  );
};

export default compose(
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('Affordability')),
  connect(
    (state) => {
      return {
        affordabilityWidget: state.affordabilityWidget,
        user: state.session.user,
        affordabilityWidgetProductOnBoarding: getCurrentProductOnBoardingDetails(
          state,
          RZPFeatures.AFFORDABILITY_WIDGET,
        ),
      };
    },
    {
      handleProductQuickGuide,
      fetchDetails: fetchAffordabilityWidget,
      openModal,
      closeModal,
    },
  ),
  withRouter,
)(Affordability);
