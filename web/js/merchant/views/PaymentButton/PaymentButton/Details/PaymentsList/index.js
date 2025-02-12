import { connect } from 'react-redux';
import { compose } from 'redux';

import { SelfServeActionPages } from 'common/constant/enums';
import { withRouter } from 'common/deprecated/withRouter';
import { withSplitzService } from 'common/splitz';
import { paymentId, amount, customer, createdAtShort, status } from 'common/ui/item/pair';
import EntityTable from 'merchant/components/EntityTable';
import ListContainer from 'merchant/containers/ListContainer';
import {
  fetchPaymentPagePayments,
  isFetchViaNCA,
} from 'merchant/views/PaymentPages/PaymentPages/utils';
import { _paymentId } from 'merchant/views/Transactions/v1/Payments/Utils';
import { onPaginate } from 'merchant/views/Transactions/v2/common/utils';

import PaymentsListFilter from './PaymentsListFilter';

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
      <div className="content-wrapper">
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
          {...tableData}
        />
      </div>
    );
  }
}

export default compose(
  connect(
    (state) => ({ payments: state.payments, ncaPayments: state.invoices.storefrontPayments }),
    { fetchPaymentPagePayments },
  ),
  withRouter,
  withSplitzService,
)(PaymentsList);
