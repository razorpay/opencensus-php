import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { fetchEmandatePayments as fetchAll } from 'merchant/reducers/collection';
import ListContainer from 'merchant/containers/ListContainer';

import PaymentsTable from 'merchant/views/Transactions/Payments/components/PaymentsTable';
import PaymentListFilter from 'merchant/views/Transactions/Payments/components/PaymentsListFilter';
import HeaderAction from 'common/ui/HeaderAction';
import DocsLink from 'merchant/components/DocsLink';

@connect(state => ({ ...state.payments }), { fetchAll })
@RTracking(() => window.rzpQ.component('EmandatePayments'))
export default class EmandatePayments extends ListContainer {
  trackSearch = (event, options) => {
    if (!event) return;

    this.props.tracking.trackEvent(
      window.rzpQ.chargeAtWill().interaction(`payment.search.${event}`, options)
    );
  };

  onSubmit = filters => {
    Object.keys(filters).forEach(filter => {
      this.trackSearch(filter);
    });

    this.search(filters);

    this.trackSearch('initiate');
  };

  onClearAnalytics = () => {
    this.trackSearch('clear');
  };

  onErrorCloseClick = () => {
    this.trackSearch('error', { response: this.state.status.message[1] });
  };

  render() {
    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar">
            <DocsLink url="https://razorpay.com/docs/recurring-payments/" />
          </div>
        </HeaderAction>
        <PaymentListFilter
          form="emandatePaymentListFilter"
          count={this.state.count}
          showBatchIdFilter
          onSubmit={this.onSubmit}
          onClearAnalytics={this.onClearAnalytics}
        />

        <PaymentsTable
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
          paginate={(params, type) => {
            this.props.tracking.trackEvent(
              window.rzpQ.chargeAtWill().interaction(`payment.browse.${type}`, {
                page: params.skip % params.count,
              })
            );

            this.paginate(params);
          }}
          onErrorCloseClick={this.onErrorCloseClick}
        />
      </div>
    );
  }
}
