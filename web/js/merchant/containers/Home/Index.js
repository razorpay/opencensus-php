import React, { Component } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';
import moment from 'moment';
import { Redirect } from 'react-router-dom';

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

import {
isMobileDevice 
} from 'merchant/components/Home/data';
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
    eventAction: `Go To - ${name}`
  });
};

const analyticsOpenDetails = name => {
  window.rzpAnalytics({
    eventCategory: 'Dashboard - Home',
    eventAction: `Open Details - ${name}`
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
  componentWillMount() {
    this.props.fetchEntityTotals();
    this.props.fetchPaymentBreakup();
    this.props.fetchCurrentBalance();
    this.props.fetchPayments({ count: 5 });
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
            <div class="col-md-12">
              <NewUserOnboardingCard payments={payments.items} />
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
    user: state.session.user
  };
}, null)
export default class HomeSwitcher extends Component {

  render () {

    // if the tag is enabled, force user to new dashboard
    if (this.props.user.isNewAnalyticsEnabled) {
    
      if (isMobileDevice) {
      
        trackForceOldDashboard();
        return <HomeContainer/>;
      }

      return <Redirect to="/dashboard_v2"/>;
    }

    return <HomeContainer/>;
  }
}
