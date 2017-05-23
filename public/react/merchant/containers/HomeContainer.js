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
                this.props.fetchAnalytics(params);
              }}
              isLive={isLive}
            />
          </div>
          <div>
            <small class="text-muted">
              Welcome to Razorpay.
            </small>
            {/*<a class='start-tour-link'>Start Tour</a>*/}
          </div>
        </Header>
        <div class="wrapper-md">
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
              <AnalyticsGraph
                panelClass="panel wrapper"
                title="Successful Transactions"
                style={{
                  height: '244px',
                  textAlign: 'center',
                  lineHeight: '244px',
                }}
                loading={graphData.loading}
                error={graphData.error}
                data={graphData.transaction_count}
              />
            </div>
          </div>
          <div
            class="panel"
            style={{
              display: 'table',
              width: '100%',
              height: '100%',
              borderSpacing: '0',
              tableLayout: 'fixed',
            }}
          >
            <AnalyticsGraph
              panelClass="wrapper"
              title="Transaction Volume"
              style={{
                height: '306px',
                textAlign: 'center',
                lineHeight: '306px',
              }}
              panelStyle={{
                display: 'table-cell',
                float: 'none',
                height: '100%',
                verticalAlign: 'top',
              }}
              loading={graphData.loading}
              error={graphData.error}
              data={graphData.transaction_amount}
            />
            <MethodBreakupCard
              data={payment_breakup.data}
              loading={payment_breakup.loading}
              error={payment_breakup.error}
            />
          </div>
          <div class="panel wrapper">
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
    );
  }
}
