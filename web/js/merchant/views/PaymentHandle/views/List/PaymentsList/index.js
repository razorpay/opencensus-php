import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import track from 'merchant/views/PaymentHandle/track';
import EntityTable from 'merchant/components/EntityTable';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchPayments as fetchAll } from 'merchant/reducers/collection';
import { paymentId, amount, customer, createdAtShort, status } from 'common/ui/item/pair';
import PaymentsListFilter from 'merchant/views/PaymentHandle/views/List/PaymentsList/PaymentsListFilter';

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

@connect((state) => state.payments, { fetchAll })
class PaymentsList extends ListContainer {
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
