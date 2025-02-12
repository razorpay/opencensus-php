import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import { fetchEmandatePayments as fetchAll } from 'merchant/reducers/collection';
import ListContainer from 'merchant/containers/ListContainer';
import { withRouter } from 'common/deprecated/withRouter';
import PaymentsTable from 'merchant/views/Transactions/v1/Payments/components/PaymentsTable';
import PaymentListFilter from 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter';
// eslint-disable-next-line no-restricted-imports
import HeaderAction from 'common/ui/HeaderAction'; // TODO: Remove this import , and use ProductWrapper instead of HeaderAction
import DocsLink from 'merchant/components/DocsLink';
import analytics from 'merchant/views/Subscriptions/analytics';
import { trackSearchEvent } from 'merchant/views/Subscriptions/utils';
import { SelfServeActionPages } from 'common/constant/enums';
import { onSearch } from 'merchant/views/Transactions/v2/common/utils';
import { compose } from 'redux';

class RecurringPaymentsListContainer extends ListContainer {
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
    const { history, loading } = this.props;
    return (
      <div className="content-wrapper">
        <HeaderAction responsive>
          <div className="btn-toolbar">
            <DocsLink url="https://razorpay.com/docs/recurring-payments/" />
          </div>
        </HeaderAction>
        <PaymentListFilter onSubmit={onSearch(history)} showBatchIdFilter loading={loading} />
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
          selfServeActionsPage={SelfServeActionPages.SubscriptionsRecurringpayments}
        />
      </div>
    );
  }
}

export default compose(
  withRouter,
  connect((state) => ({ ...state.payments, user: state.session.user }), { fetchAll }),
  rTracking(() => window.rzpQ.component('EmandatePayments')),
)(RecurringPaymentsListContainer);
