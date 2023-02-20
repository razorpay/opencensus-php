import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import { fetchPayments as fetchAll } from 'merchant/reducers/collection';
import ListContainer from 'merchant/containers/ListContainer';
import PaymentsListFilter from './PaymentsListFilter';
import { paymentId, amount, customer, createdAtShort, status } from 'common/ui/item/pair';
import EntityTable from 'merchant/components/EntityTable';
import track from 'merchant/views/PaymentPages/PaymentPages/Details/track';
import { makeIdLink } from 'merchant/views/Transactions/Payments/Utils';
import { SelfServeActionPages } from 'common/constant/enums';

// wrapper to trigger analytics event on click
const _paymentId = () => {
  return {
    title: paymentId.title,
    value: (item) => {
      const intermediateElement = makeIdLink('payment')(
        item,
        SelfServeActionPages.PaymentpagesPayments,
      );

      return <div onClick={track.paymentIdClick}>{intermediateElement}</div>;
    },
  };
};

const PaymentsTable = (props) => {
  const paymentColumns = [_paymentId(), amount, customer, createdAtShort, status];

  return <EntityTable title="Payments" columns={paymentColumns} {...props} />;
};

@withRouter
@connect((state) => state.payments, { fetchAll })
export default class PaymentsList extends ListContainer {
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
