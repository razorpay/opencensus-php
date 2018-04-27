import React, { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import moment from 'moment';
import { Redirect, Link } from 'react-router-dom';

import Header from 'rzp/ui/Header';
import {
  fetchPayments,
  fetchRefunds,
  fetchSettlements,
} from 'rzp/modules/collection';
import DateRangePickerField from 'rzp/ui/Forms/DateRangePickerField';

import * as HomeActions from 'merchant/modules/home';
import InfoCardList from 'merchant/components/Home/InfoCardList';
import RecentEntityTable from 'merchant/components/Home/EntityTable';
import AnalyticsGraph from 'merchant/components/Home/AnalyticsGraph';
import MethodBreakupCard from 'merchant/components/Home/MethodBreakupCard';
import NewUserOnboardingCard from 'merchant/containers/Home/OnboardingCard';
import { defaults } from 'react-chartjs-2';
import ShowWhen from 'merchant/components/ShowWhen';
import LocalStorageService from 'rzp/utils/localStorage';
import NewHome from './New';

import { isMobileDevice } from 'merchant/components/Home/data';
import { trackForceOldDashboard } from './ga';

defaults.global.defaultFontColor = '#666';
defaults.global.defaultFontFamily =
  '"Lato", "Helvetica Neue", Helvetica, Arial,sans-serif';
defaults.global.defaultFontSize = 11;
defaults.global.layout = {
  padding: {
    left: 10,
    bottom: 15,
    top: 5,
    right: 5,
  },
};

const analyticsGoTo = name => {
  window.rzpAnalytics({
    eventCategory: 'Dashboard - Home',
    eventAction: `Go To - ${name}`,
  });
};

const analyticsOpenDetails = name => {
  window.rzpAnalytics({
    eventCategory: 'Dashboard - Home',
    eventAction: `Open Details - ${name}`,
  });
};

// graph data
// numbers
@connect(
  state => {
    return {
      user: state.session.user,
      mode: state.session.mode,
      analytics: state.home.analytics,
      entity_totals: state.home.entity_totals,
      payment_breakup: state.home.payment_breakup,
      current_balance: state.home.current_balance,
      payments: state.payments,
      refunds: state.refunds,
      settlements: state.settlements,
    };
  },
  {
    ...HomeActions,
    fetchPayments,
    fetchRefunds,
    fetchSettlements,
  }
)
class HomeContainer extends Component {
  constructor(props) {
    super(props);

    const { user, mode } = props,
      // onboarding card is shown if this is present in localstorage
      onboardingCardToken = 'show_onboarding_card',
      // onboarding card first step is shown if this is present in localstorage
      firstStepToken = 'onboarding_first_step';

    // tokens particular for the current merchant
    this.onboardingBannerToken = `${onboardingCardToken}--${user.current}`;
    this.firstStepToken = `${firstStepToken}--${user.current}`;

    /*
     * Earlier , the tokens apply at browser level, if old tokens are present
     * converting them specific to the merchants the current user can switch to
     */
    if (LocalStorageService.getItem(onboardingCardToken)) {
      Object.keys(user.merchants).forEach(key => {
        LocalStorageService.setItem(`${onboardingCardToken}--${key}`, 'true');
      });

      LocalStorageService.removeItem(onboardingCardToken);
    }

    if (LocalStorageService.getItem(firstStepToken)) {
      Object.keys(user.merchants).forEach(key => {
        LocalStorageService.setItem(`${firstStepToken}--${key}`, 'true');
      });

      LocalStorageService.removeItem(firstStepToken);
    }

    const showOnboardingBanner = LocalStorageService.getItem(
        this.onboardingBannerToken
      ),
      showOnboardingBannerFirstStep = LocalStorageService.getItem(
        this.firstStepToken
      );

    this.state = {
      expandOnboardingBanner: showOnboardingBanner, // used for transition
      showOnboardingBanner,
      showOnboardingBannerFirstStep,
      // payments is used to change content in the integration step
      payments: {
        loading: true,
        items: [],
      },
    };

    /*
     * If token not present to show the banner,
     * Need to show the banner until the user integrates in live mode
     * which we can check by checking his live transactions
     *
     * If the user is in live mode, we make fetchAll payments in 
     * RecentActivity component, which will be done using `onFetchPayments`
     * below
     */
    if (!showOnboardingBanner) {
      if (!user.isActivated) {
        this.state = {
          ...this.state,
          showOnboardingBanner: true,
          showOnboardingBannerFirstStep: true,
          expandOnboardingBanner: true,
        };

        LocalStorageService.setItem(this.onboardingBannerToken, 'true');
        LocalStorageService.setItem(this.firstStepToken, 'true');
      } else if (mode !== 'live') {
        this.props.fetchPayments({ mode: 'live' }).then(data => {
          if (data && data.items && data.items.length === 0) {
            this.setShowOnboardingBanner();
          }
        });
      }
    }

    this.onFetchPayments = this.onFetchPayments.bind(this);
    this.onHideOnboardingBanner = this.onHideOnboardingBanner.bind(this);
    this.onFirstStepClose = this.onFirstStepClose.bind(this);
  }

  setShowOnboardingBanner() {
    this.setState(
      {
        showOnboardingBanner: true,
        showOnboardingBannerFirstStep: true,
      },
      () => {
        this.setState({
          expandOnboardingBanner: true,
        });
      }
    );

    LocalStorageService.setItem(this.onboardingBannerToken, 'true');
    LocalStorageService.setItem(this.firstStepToken, 'true');
  }

  onFirstStepClose() {
    this.setState({
      showOnboardingBannerFirstStep: false,
    });

    LocalStorageService.removeItem(this.firstStepToken);
  }

  onHideOnboardingBanner() {
    this.setState(
      {
        expandOnboardingBanner: false,
      },
      () => {
        this.setState({
          showOnboardingBanner: false,
        });
      }
    );

    LocalStorageService.removeItem(this.onboardingBannerToken);
  }

  onFetchPayments(data) {
    const { user, mode } = this.props;

    const items = (data && data.items) || [];

    const { showOnboardingBanner } = this.state;

    this.setState({
      payments: {
        loading: false,
        items,
      },
    });

    /*
     * When fetched payments in live mode, using recent activity component
     * we use it to show the banner , if there are no trasaction
     */
    if (
      !this.state.showOnboardingBanner &&
      user.isActivated &&
      mode === 'live' &&
      items.length === 0
    ) {
      this.setShowOnboardingBanner();
    }
  }

  componentWillMount() {
    this.props.fetchEntityTotals();
    this.props.fetchPaymentBreakup();
    this.props.fetchCurrentBalance();
    this.props.fetchPayments({ count: 5 }).then(this.onFetchPayments);
    this.props.fetchRefunds({ count: 5 });
    this.props.fetchSettlements({ count: 5 });
  }

  render() {
    let {
      entity_totals,
      payment_breakup,
      current_balance,
      payments,
      refunds,
      settlements,
    } = this.props;
    let mode = this.props.mode;
    let graphData = this.props.analytics;

    return (
      <div class="react-root">
        <Header title="Dashboard" showMode={true}>
          <div class="pull-right">
            <DateRangePickerField
              onDatesChange={params => {
                this.props.fetchAnalytics({
                  ...params,
                  mode,
                });
              }}
            />
          </div>
        </Header>

        <div
          class="Dashboard"
          style={{
            padding: '20px',
          }}
        >
          <div class="row">
            <div
              className={`col-md-12 v2-onboarding-card old-analytics${
                this.state.expandOnboardingBanner ? ' expand' : ''
              }`}
            >
              {this.state.showOnboardingBanner && (
                <NewUserOnboardingCard
                  payments={this.state.payments}
                  onClose={this.onHideOnboardingBanner}
                  onFirstStepClose={this.onFirstStepClose}
                  isFirstStep={this.state.showOnboardingBannerFirstStep}
                />
              )}
            </div>

            <InfoCardList
              entity_totals={entity_totals}
              payment_breakup={payment_breakup}
              current_balance={current_balance}
              payments={payments}
              refunds={refunds}
              settlements={settlements}
            />
            <div class="col-md-12 col-lg-6">
              <div class="WidgetContainer">
                <AnalyticsGraph
                  title="Successful Transactions"
                  loading={graphData.loading}
                  error={graphData.error}
                  data={graphData.transaction_count}
                  yLabel="Number of Successful Transactions"
                />
              </div>
            </div>
          </div>
          <div class="clearfix">
            <MethodBreakupCard
              data={payment_breakup.data}
              loading={payment_breakup.loading}
              error={payment_breakup.error}
            />
            <div class="WidgetContainer Transaction__Vol">
              <AnalyticsGraph
                title="Transaction Volume"
                loading={graphData.loading}
                error={graphData.error}
                data={graphData.transaction_amount}
                yLabel="Transaction Volume in INR"
              />
            </div>
          </div>

          <div class="row RecentTxns">
            <RecentEntityTable
              onSeeAll={() => analyticsGoTo('Payments')}
              onOpenDetails={() => analyticsOpenDetails('Payments')}
              entity="payment"
              data={payments}
              loading={payments.loading}
            />
            <RecentEntityTable
              onSeeAll={() => analyticsGoTo('Refunds')}
              onOpenDetails={() => analyticsOpenDetails('Refunds')}
              entity="refund"
              data={refunds}
              loading={refunds.loading}
            />
            <RecentEntityTable
              onSeeAll={() => analyticsGoTo('Settlements')}
              onOpenDetails={() => analyticsOpenDetails('Settlements')}
              entity="settlement"
              data={settlements}
              loading={settlements.loading}
            />
          </div>
        </div>
      </div>
    );
  }
}

@connect(state => {
  return {
    user: state.session.user,
  };
}, null)
export default class HomeSwitcher extends Component {
  render() {
    // if the tag is enabled, force user to new dashboard
    if (this.props.user.isNewAnalyticsEnabled) {
      if (isMobileDevice) {
        trackForceOldDashboard();
        return <HomeContainer />;
      }

      return <Redirect to="/dashboard_v2" />;
    }

    return <HomeContainer />;
  }
}
