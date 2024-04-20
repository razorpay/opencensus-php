import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import track from 'merchant/views/PaymentHandle/track';
import EntityTable from 'merchant/components/EntityTable';
import ListContainer from 'merchant/containers/ListContainer';
import { paymentId, amount, customer, createdAtShort, status } from 'common/ui/item/pair';
import PaymentsListFilter from 'merchant/views/PaymentHandle/views/List/PaymentsList/PaymentsListFilter';
import { withSplitzService } from 'common/splitz';
import {
  fetchPaymentPagePayments as fetchPaymentHandlePayments,
  isFetchViaNCA,
} from 'merchant/views/PaymentPages/PaymentPages/utils';
import { onPaginate } from 'merchant/views/Transactions/v2/common/utils';

const _paymentId = {
  title: paymentId.title,
  value: (item) => {
    const intermediateElement = paymentId.value(item);
    return <div onClick={track.paymentIdClick}>{intermediateElement}</div>;
  },
};

const PaymentsTable = (props) => {
  const paymentColumns = [_paymentId, amount, customer, createdAtShort, status];
  return <EntityTable title="Payments" columns={paymentColumns} {...props} />;
};

@connect(
  (state) => ({ payments: state.payments, ncaPayments: state.invoices.storefrontPayments }),
  { fetchPaymentHandlePayments },
)
class PaymentsList extends ListContainer {
  fetchEntityList = (params) => {
    const { paymentPageId, splitz } = this.props;

    return this.props.fetchPaymentHandlePayments('payment_handle', splitz, paymentPageId, params);
  };

  render() {
    const { children, splitz, payments, ncaPayments, ...restProps } = this.props;

    const entityData = isFetchViaNCA(splitz) ? ncaPayments : payments;

    return (
      <div class="content-wrapper">
        {children}
        <PaymentsListFilter
          form="paymentListFilter"
          count={this.state.count}
          onSubmit={this.search}
          fetchAll={this.fetchAll}
        />
        <PaymentsTable
          count={this.state.count}
          skip={this.state.skip}
          paginate={onPaginate(this.paginate)}
          {...restProps}
          {...entityData}
        />
      </div>
    );
  }
}

export default withSplitzService(withRouter(PaymentsList));
