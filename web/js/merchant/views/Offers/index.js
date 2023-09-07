import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import RTracking from 'react-tracking';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { withSplitzService } from 'common/splitz';
import DashboardBanner from 'common/ui/DashboardBanner';
import Alert from 'common/ui/Forms/Alert';
// eslint-disable-next-line no-restricted-imports
import HeaderAction from 'common/ui/HeaderAction';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import DocsLink from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import ShowWhen from 'merchant/components/ShowWhen';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { RZPFeatures } from 'merchant/helpers/data';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import List from 'merchant/views/Offers/List';
import { isLowCostExperimentEnabled as isLowCostEnabled } from 'merchant/views/Offers/New/Screens/NoCostEMI/helpers/helper';

import OnBoarding, { getIsOffersEnabled, getIsAllowedResetOffersOnBoarding } from './OnBoarding';

// eslint-disable-next-line react/no-unsafe
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
    const { showOnboarding } = this.props.offersProductOnBoarding;
    if (showOnboarding) {
      return <OnBoarding />;
    }

    const createOfferRoute = this.props.user.isSubscriptionOffersEnabled
      ? '/offers/new' // '/offers/new?offer_creation_modal_type=subscription'
      : '/offers/new?offer_creation_modal_type=basic';

    let isLowCostExperimentEnabled = false;
    if (this.props.splitz) {
      const {
        abExperiments: { Low_cost_offer },
      } = this.props.splitz;

      isLowCostExperimentEnabled = isLowCostEnabled(Low_cost_offer);
    }

    return (
      <>
        <div className="banner-container">
          <DashboardBanner />
        </div>
        <tabbed-container>
          <header id="link-header">
            <NavLink exact to="/offers">
              Offers
            </NavLink>
          </header>

          <TestModeBanner />
          <ErrorBoundary resetOnProps>
            <Switch>
              <Route path="/offers">
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
                          <NavLink class="btn btn-primary" exact to={createOfferRoute}>
                            <i className="i i-plus" />
                            <span
                              onClick={() => {
                                this.props.tracking.trackEvent(
                                  window.rzpQ.merchantActions().initiated('Offer_create'),
                                );
                                selfServeTrackInitiate({
                                  selfServeAction: 'New Offer Created',
                                  page: 'Offers',
                                  screen: 'Offers',
                                });
                              }}
                            >
                              Create New Offer
                            </span>
                          </NavLink>
                          <NavLink
                            class="btn btn-primary"
                            exact
                            to="/offers/new?offer_creation_modal_type=no-cost-emi"
                          >
                            <i className="i i-plus" />
                            <span
                              onClick={() => {
                                this.props.tracking.trackEvent(
                                  window.rzpQ.merchantActions().initiated('nocostemi_create'),
                                );
                                selfServeTrackInitiate({
                                  selfServeAction: 'New No Cost EMI Offer Created',
                                  page: 'Offers',
                                  screen: 'Offers',
                                });
                              }}
                            >
                              {!isLowCostExperimentEnabled
                                ? 'Create No Cost EMI'
                                : 'Create No & Low Cost EMI'}
                            </span>
                          </NavLink>
                        </ShowWhen>
                      </div>
                    </HeaderAction>
                    <Alert type={status.type} message={status.message} />
                    <List {...this.props} />
                  </div>
                </content>
              </Route>
            </Switch>
          </ErrorBoundary>
        </tabbed-container>
      </>
    );
  }
}

export default withSplitzService(OfferIndex);
