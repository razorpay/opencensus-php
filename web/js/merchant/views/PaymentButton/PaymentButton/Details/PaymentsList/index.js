import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import { fetchPayments as fetchAll } from 'merchant/reducers/collection';
import ListContainer from 'merchant/containers/ListContainer';
import PaymentsListFilter from './PaymentsListFilter';
import { paymentId, amount, customer, createdAtShort, status } from 'common/ui/item/pair';
import EntityTable from 'merchant/components/EntityTable';
import { _paymentId } from 'merchant/views/Transactions/v1/Payments/Utils';
import { SelfServeActionPages } from 'common/constant/enums';

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

@connect((state) => state.payments, { fetchAll })
class PaymentsList extends ListContainer {
  // Hook to modify fetchAll of ListContainer
  fetchEntityList = (params) => {
    return this.props.fetchAll({
      ...params,
      payment_link_id: this.props.paymentPageId,
    });
  };

  render() {
    const { children, ...restProps } = this.props;

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
        />
      </div>
    );
  }
}

export default withRouter(PaymentsList);
