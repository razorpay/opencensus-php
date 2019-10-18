import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import { fetchPayments as fetchAll } from 'merchant/modules/collection';

import ListContainer from 'merchant/containers/ListContainer';
import PaymentsListFilter from './PaymentsListFilter';

import {
  paymentId,
  amount,
  customer,
  createdAtShort,
  status,
} from 'rzp/ui/item/pair';

import EntityTable from 'merchant/components/EntityTable';

const PaymentsTable = props => {
  let paymentColumns = [paymentId, amount, customer, createdAtShort, status];

  return <EntityTable title="Payments" columns={paymentColumns} {...props} />;
};

@withRouter
@connect(state => state.payments, { fetchAll })
export default class PaymentsList extends ListContainer {
  constructor(props) {
    super(props);
  }

  // Hook to modify fetchAll of ListContainer
  fetchAll = params => {
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
