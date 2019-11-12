import { connect } from 'react-redux';

import { fetchEmandatePayments as fetchAll } from 'merchant/reducers/collection';
import ListContainer from 'merchant/containers/ListContainer';

import PaymentsTable from 'merchant/components/Payments/PaymentsTable';
import PaymentListFilter from 'merchant/components/Payments/PaymentsListFilter';
import HeaderAction from 'common/ui/HeaderAction';
import DocsLink from 'merchant/components/DocsLink';

@connect(state => ({ ...state.payments }), { fetchAll })
export default class EmandatePayments extends ListContainer {
  render() {
    return (
      <div className="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar">
            <DocsLink url="https://razorpay.com/docs/recurring-payments/" />
          </div>
        </HeaderAction>
        <PaymentListFilter
          form="emandatePaymentListFilter"
          count={this.state.count}
          onSubmit={this.searh}
          showBatchIdFilter
        />

        <PaymentsTable
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
