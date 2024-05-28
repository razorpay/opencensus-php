/* eslint-disable */
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Button, PlusIcon, Heading } from '@razorpay/blade/components';
import HeaderAction from 'common/ui/HeaderAction';
import Alert from 'common/ui/Forms/Alert';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { withRouter } from 'shell/deprecated/withRouter';
import { RZPFeatures } from 'merchant/helpers/data';
import ShowWhen from 'merchant/components/ShowWhen';
import TestModeBanner from 'merchant/components/TestModeBanner';
import List from 'merchant/views/Offers/List';
import { Route, Routes, useNavigate } from 'react-router-dom';
import RTracking from 'react-tracking';

import { withSplitzService } from 'common/splitz';
import DashboardBanner from 'common/ui/DashboardBanner';
// eslint-disable-next-line no-restricted-imports
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import DocsLink from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import { isLowCostExperimentEnabled as isLowCostEnabled } from 'merchant/views/Offers/New/Screens/NoCostEMI/helpers/helper';
import { withI18Service } from 'common/i18';
import OnBoarding, { getIsOffersEnabled, getIsAllowedResetOffersOnBoarding } from './OnBoarding';

// eslint-disable-next-line react/no-unsafe
const OfferIndexWrapper = (props) => {
  const navigate = useNavigate();
  return <OfferIndex navigate={navigate} {...props} />;
};
@connect(
  (state) => {
    return {
      offers: state.offers,
      user: state.session.user,
      offersProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.OFFERS),
    };
  },
  {
    handleProductQuickGuide,
  },
)
@RTracking(() => window.rzpQ.component('OfferIndex'))
class OfferIndex extends Component {
  componentDidMount() {
    if (window.rzpQ && window.rzpQ.merchantActions) {
      this.props.tracking.trackEvent(window.rzpQ.merchantActions().success('Offer_rendered'));
    }
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (nextProps.offers.loading !== this.props.offers.loading) {
      this.initOffersOnboarding(nextProps);
    }
  }

  initOffersOnboarding = (props = this.props) => {
    if (props.offersProductOnBoarding.isTour) {
      return;
    }

    const data = {
      user: props.user,
      merchantId: props.user.current,
      offers: props.offers,
    };

    const isOffersEnabled = getIsOffersEnabled(data);

    let showOnboarding = !isOffersEnabled;

    if (isOffersEnabled) {
      showOnboarding = getIsAllowedResetOffersOnBoarding(data.offers);
    }

    const offersProductOnBoarding = {
      ...props.offersProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: false,
    };

    this.props.handleProductQuickGuide(offersProductOnBoarding);
  };
  render() {
    const { offersProductOnBoarding, i18, navigate } = this.props;
    const { showOnboarding } = offersProductOnBoarding;
    const { isConfigTagEnabled } = i18;
    if (showOnboarding) {
      return <OnBoarding />;
    }

    const createOfferRoute =
      this.props.user.isSubscriptionOffersEnabled &&
      !isConfigTagEnabled('subscriptions.subscription_offers')
        ? '/offers/new' // '/offers/new?offer_creation_modal_type=subscription'
        : '/offers/new?offer_creation_modal_type=basic';

    let isLowCostExperimentEnabled = false;
    if (this.props.splitz) {
      const {
        abExperiments: { Low_cost_offer },
      } = this.props.splitz;

      isLowCostExperimentEnabled = isLowCostEnabled(Low_cost_offer);
    }
    const handleOnClick = (path, merchantAction, selfServeAction) => {
      navigate(path);
      this.props.tracking.trackEvent(window.rzpQ.merchantActions().initiated(merchantAction));
      selfServeTrackInitiate({
        selfServeAction: selfServeAction,
        page: 'Offers',
        screen: 'Offers',
      });
    };

    return (
      <>
        <div className="banner-container">
          <DashboardBanner />
        </div>
        <tabbed-container>
          <header id="link-header">
            <Heading
              color="surface.text.gray.staticBlack.Normal"
              size="medium"
              weight="semibold"
              display={'inline'}
            >
              Offers
            </Heading>
          </header>

          <TestModeBanner />
          <ErrorBoundary resetOnProps>
            <Routes>
              <Route
                index
                element={
                  <content>
                    <div className="content-wrapper">
                      <HeaderAction>
                        <div className="btn-toolbar pull-right">
                          <TakeATourButton feature={RZPFeatures.OFFERS} />
                          <DocsLink url="https://razorpay.com/docs/offers/" />
                          <ShowWhen
                            additionalCondition={(user) =>
                              (this.props.mode !== 'live' || !user.isRejected) &&
                              user.isAllowedEdit('offers')
                            }
                          >
                            <Button
                              color="primary"
                              onClick={() => {
                                handleOnClick(
                                  createOfferRoute,
                                  'Offer_create',
                                  'New Offer Created',
                                );
                              }}
                              size="medium"
                              type="button"
                              variant="primary"
                              icon={PlusIcon}
                              marginRight="spacing.3"
                              marginBottom="spacing.2"
                            >
                              Create New Offer
                            </Button>
                            <Button
                              color="primary"
                              onClick={() => {
                                handleOnClick(
                                  '/offers/new?offer_creation_modal_type=no-cost-emi',
                                  'nocostemi_create',
                                  'New No Cost EMI Offer Created',
                                );
                              }}
                              size="medium"
                              type="button"
                              variant="primary"
                              icon={PlusIcon}
                            >
                              {!isLowCostExperimentEnabled
                                ? 'Create No Cost EMI'
                                : 'Create No & Low Cost EMI'}
                            </Button>
                          </ShowWhen>
                        </div>
                      </HeaderAction>
                      <Alert type={status.type} message={status.message} />
                      <List {...this.props} />
                    </div>
                  </content>
                }
              />
            </Routes>
          </ErrorBoundary>
        </tabbed-container>
      </>
    );
  }
}

export default withRouter(withSplitzService(withI18Service(OfferIndexWrapper)));
