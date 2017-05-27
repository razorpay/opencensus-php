import React, { Component } from 'react';
import Header from 'rzp/ui/Header';
import { connect } from 'react-redux';
import * as HomeActions from 'merchant/modules/home';
import { fetchPayments } from 'merchant/modules/payments/list';
import { fetchRefunds } from 'merchant/modules/refunds/list';
import { fetchSettlements } from 'merchant/modules/settlements/list';
import moment from 'moment';
import DateRangePickerField from 'rzp/ui/Forms/DateRangePickerField';
import InfoCardList from 'merchant/components/Home/InfoCardList';
import RecentEntityTable from 'merchant/components/Home/EntityTable';
import AnalyticsGraph from 'merchant/components/Home/AnalyticsGraph';
import MethodBreakupCard from 'merchant/components/Home/MethodBreakupCard';
import { defaults } from 'react-chartjs-2';

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

// graph data
// numbers
@connect(
  state => {
    return {
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
export default class HomeContainer extends Component {
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
    let isLive = this.props.mode === 'live';
    let graphData = this.props.analytics;

    return (
      <div class="react-root">
        <Header title="Dashboard" showMode={false}>
          <div class="pull-right">
            <DateRangePickerField
              onDatesChange={params => {
                this.props.fetchAnalytics({
                  ...params,
                  isLive,
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
                />
              </div>
            </div>
          </div>
          <div class="WidgetContainer clearfix">
            <MethodBreakupCard
              data={payment_breakup.data}
              loading={payment_breakup.loading}
              error={payment_breakup.error}
            />
            <div class="Transaction__Vol">
              <AnalyticsGraph
                title="Transaction Volume"
                loading={graphData.loading}
                error={graphData.error}
                data={graphData.transaction_amount}
              />
            </div>
          </div>

          <div class="WidgetContainer">
            <div class="panel">
              <div class="panel-body">
                <div class="row">
                  <RecentEntityTable
                    entity="payment"
                    data={payments}
                    loading={payments.loading}
                  />
                  <RecentEntityTable
                    entity="refund"
                    data={refunds}
                    loading={refunds.loading}
                  />
                  <RecentEntityTable
                    entity="settlement"
                    data={settlements}
                    loading={settlements.loading}
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
