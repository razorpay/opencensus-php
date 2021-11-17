import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { fetchEmandatePayments as fetchAll } from 'merchant/reducers/collection';
import ListContainer from 'merchant/containers/ListContainer';

import PaymentsTable from 'merchant/views/Transactions/Payments/components/PaymentsTable';
import PaymentListFilter from 'merchant/views/Transactions/Payments/components/PaymentsListFilter';
import HeaderAction from 'common/ui/HeaderAction';
import DocsLink from 'merchant/components/DocsLink';
import analytics from '../analytics';
import { trackSearchEvent } from '../utils';

@connect((state) => ({ ...state.payments }), { fetchAll })
@RTracking(() => window.rzpQ.component('EmandatePayments'))
export default class EmandatePayments extends ListContainer {
  trackSearch = (event, options) => {
    trackSearchEvent(event, { options, eventStartLabel: 'payment.search' });
  };

  onSubmit = (filters) => {
    this.trackSearch('initiate');
    this.trackSearch(filters);
    this.search(filters);
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
          {...this.props}
          paginate={(params, type) => {
            analytics.track(`payment.browse.${type}`, {
              page: params.skip % params.count,
            });
            this.paginate(params);
          }}
          onErrorCloseClick={this.onErrorCloseClick}
        />
      </div>
    );
  }
}
