import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import ListContainer from 'merchant/containers/ListContainer';
import PaymentsListFilter from './PaymentsListFilter';
import { paymentId, amount, customer, createdAtShort, status } from 'common/ui/item/pair';
import EntityTable from 'merchant/components/EntityTable';
import { _paymentId } from 'merchant/views/Transactions/v1/Payments/Utils';
import { SelfServeActionPages } from 'common/constant/enums';
import { withSplitzService } from 'common/splitz';
import {
  fetchPaymentPagePayments,
  isFetchViaNCA,
} from 'merchant/views/PaymentPages/PaymentPages/utils';

const PaymentsTable = (props) => {
  const paymentColumns = [
    {
      title: paymentId.title,
      value: (item) => _paymentId(item, SelfServeActionPages.PaymentbuttonsPayments),
    },
    amount,
    customer,
    createdAtShort,
    status,
  ];

  return <EntityTable title="Payments" columns={paymentColumns} {...props} />;
};

@connect(
  (state) => ({ payments: state.payments, ncaPayments: state.invoices.storefrontPayments }),
  { fetchPaymentPagePayments },
)
class PaymentsList extends ListContainer {
  // Hook to modify fetchAll of ListContainer
  fetchEntityList = (params) => {
    const { splitz, paymentPageId } = this.props;

    return this.props.fetchPaymentPagePayments('button', splitz, paymentPageId, params);
  };

  render() {
    const { children, splitz, payments, ncaPayments, ...restProps } = this.props;

    const tableData = isFetchViaNCA(splitz) ? ncaPayments : payments;

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
          paginate={this.paginate}
          {...restProps}
          {...tableData}
        />
      </div>
    );
  }
}

export default withSplitzService(withRouter(PaymentsList));
