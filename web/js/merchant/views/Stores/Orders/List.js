import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import { SelfServeActionPages } from 'common/constant/enums';
import { withRouter } from 'common/deprecated/withRouter';
import { amount, customer, createdAtShort, status } from 'common/ui/item/pair';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import EntityTable from 'merchant/components/EntityTable';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchPayments as fetchAll } from 'merchant/reducers/collection';

import PaymentsListFilter from './Filter';

const _paymentId = () => {
  return {
    title: 'Payment Id',
    value: (item) => (
      <Link
        to={`/payments/${item.id}#stores?init_point=stores-orders&init_page=${SelfServeActionPages.StoresOrders}`}
        onClick={() => {
          const selfServeInitiateData = {
            selfServeAction: 'Payment Details Fetched',
            page: 'Payments',
            screen: 'Stores',
            props: {
              initiatePoint: 'stores-orders',
              sessionId: window?.session_id,
            },
          };
          selfServeTrackInitiate(selfServeInitiateData);
        }}
      >
        {item.id}
      </Link>
    ),
  };
};

const PaymentsTable = (props) => {
  const paymentColumns = [_paymentId(), amount, customer, createdAtShort, status];

  return <EntityTable title="Payments" columns={paymentColumns} {...props} />;
};

class PaymentsList extends ListContainer {
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
          paginate={this.paginate}
          title="Orders"
          {...restProps}
        />
      </div>
    );
  }
}

export default connect(
  (state) => ({
    ...state.payments,
    id: state.storefront.entity.data.id,
  }),
  { fetchAll },
)(withRouter(PaymentsList));
