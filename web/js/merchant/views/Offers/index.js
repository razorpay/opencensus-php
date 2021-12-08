import React, { Component } from 'react';
import { connect } from 'react-redux';

import HeaderAction from 'common/ui/HeaderAction';
import Alert from 'common/ui/Forms/Alert';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';

import { RZPFeatures } from 'merchant/helpers/data';

import ShowWhen from 'merchant/components/ShowWhen';
import TestModeBanner from 'merchant/components/TestModeBanner';
import List from 'merchant/views/Offers/List';
import { Route, Switch, NavLink } from 'react-router-dom';
import RTracking from 'react-tracking';

import DocsLink from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';

import OnBoarding, { getIsOffersEnabled, getIsAllowedResetOffersOnBoarding } from './OnBoarding';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';
import DashboardBanner from '../../../common/ui/DashboardBanner';

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
export default class OfferIndex extends Component {
  componentDidMount() {
    if (window.rzpQ && window.rzpQ.merchantActions) {
      this.props.tracking.trackEvent(window.rzpQ.merchantActions().success('Offer_rendered'));
    }
  }

  componentWillReceiveProps(nextProps) {
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
                              }}
                            >
                              Create No Cost EMI
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
