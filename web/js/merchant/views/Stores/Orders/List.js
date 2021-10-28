import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import { fetchPayments as fetchAll } from 'merchant/reducers/collection';

import ListContainer from 'merchant/containers/ListContainer';
import PaymentsListFilter from './Filter';

import { paymentId, amount, customer, createdAtShort, status } from 'common/ui/item/pair';

import EntityTable from 'merchant/components/EntityTable';

const PaymentsTable = (props) => {
  const paymentColumns = [paymentId, amount, customer, createdAtShort, status];

  return <EntityTable title="Payments" columns={paymentColumns} {...props} />;
};

@withRouter
@connect(
  (state) => ({
    ...state.payments,
    id: state.storefront.entity.data.id,
  }),
  { fetchAll },
)
export default class PaymentsList extends ListContainer {
  // Hook to modify fetchAll of ListContainer
  fetchEntityList = (params) => {
    // API needs to be changed, for now replacing store_ with pl_ to use PP API
    const storeId = this.props.id.replace('store_', 'pl_');

    return this.props.fetchAll({
      ...params,
      payment_link_id: storeId,
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
          title="Orders"
          {...restProps}
        />
      </div>
    );
  }
}
