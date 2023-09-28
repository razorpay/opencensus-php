/* eslint-disable */
import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import TestModeBanner from 'merchant/components/TestModeBanner';
import HeaderAction from 'common/ui/HeaderAction';
import Alert from 'common/ui/Forms/Alert';
import Spinner from 'common/ui/Spinner';

import { RZPFeatures } from 'merchant/helpers/data';

import RTracking from 'react-tracking';

import DocsLink from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { fetchAffordabilityWidget } from 'merchant/reducers/affordability/affordabilityWidget';

import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

import './affordability-widget.styl';
import './affordability-widget-content.styl';
import './affordability-widget-self-serve.styl';

import OnBoarding from './Onboarding';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import FeatureTiles from './Onboarding/FeatureTile';
import PlanDetails from './PlanDetails';
import track from './Onboarding/track';
import { compose } from 'redux';
import { withRouter } from 'common/deprecated/withRouter';
import Settings from 'merchant/views/Affordability/components/settings';
import ProductWrapper from 'common/ui/ProductWrapper';

const tabsData = [{ title: 'Widget', url: '/affordability/widget' }];

const AffordabilityWidget = (props) => {
  const { affordabilityWidgetProductOnBoarding, affordabilityWidget } = props;
  const { affordability, loading } = affordabilityWidget;
  const { enabled } = affordability;
  const { showOnboarding } = affordabilityWidgetProductOnBoarding;

  useEffect(() => {
    if (window.rzpQ && window.rzpQ.merchantActions) {
      props.tracking.trackEvent(
        window.rzpQ.merchantActions().success('Affordability_Widget_rendered'),
      );
    }
    selfServeTrackInitiate({
      selfServeAction: 'AffordabilityWidget Fetched',
      page: 'Affordability',
      screen: 'AffordabilityWidget',
    });
  }, []);

  useEffect(() => {
    if (!showOnboarding) {
      track.widgetTabClicked();
    }

    track.widgetTabRender({
      is_default_tab: true,
      is_widget_live: enabled,
    });
  }, [loading, enabled]);

  if (loading) {
    return (
      <div class="page-spinner-container">
        <Spinner />
      </div>
    );
  }

  if (showOnboarding) {
    return <OnBoarding history={props.history} {...props} />;
  }

  return (
    <ProductWrapper
      tabsData={tabsData}
      extra={
        <div className="aff-product-nav">
          <TakeATourButton feature={RZPFeatures.AFFORDABILITY_WIDGET} />
          <DocsLink url="https://razorpay.com/docs/payments/payment-gateway/affordability/widget/" />

          {affordability.enabled ? (
            <Settings
              openModal={props.openModal}
              closeModal={props.closeModal}
              user={props.user}
              source={affordability.widget_enable_source}
            />
          ) : null}
        </div>
      }
    >
      <content>
        <div className="aff-self-server content-wrapper">
          <TestModeBanner />
          <HeaderAction responsive>
            <div className="aff-header btn-toolbar pull-right" />
          </HeaderAction>
          <Alert type={status.type} message={status.message} />
          <div className="benefits-wrapper">
            <p className="caption">Benefits</p>
            <FeatureTiles expanded={true} />
          </div>
          <PlanDetails loading={loading} affordability={affordability} {...props} />
        </div>
      </content>
    </ProductWrapper>
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
)(AffordabilityWidget);
