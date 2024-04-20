import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import { fetchStorefrontPayments } from 'merchant/reducers/invoices/list';

import ListContainer from 'merchant/containers/ListContainer';
import PaymentsListFilter from './PaymentsListFilter';
import { paymentId, amount, customer, createdAtShort, status } from 'common/ui/item/pair';
import EntityTable from 'merchant/components/EntityTable';
import track from 'merchant/views/PaymentPages/PaymentPages/Details/track';
import { makeIdLink } from 'merchant/views/Transactions/v1/Payments/Utils';
import { SelfServeActionPages } from 'common/constant/enums';
import { withSplitzService } from 'common/splitz';
import {
  fetchPaymentPagePayments,
  isFetchViaNCA,
} from 'merchant/views/PaymentPages/PaymentPages/utils';
import { onPaginate } from 'merchant/views/Transactions/v2/common/utils';

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

@connect(
  (state) => ({ payments: state.payments, storefrontPayments: state.invoices.storefrontPayments }),
  { fetchPaymentPagePayments, fetchStorefrontPayments },
)
class PaymentsList extends ListContainer {
  // Hook to modify fetchAll of ListContainer
  fetchEntityList = (params) => {
    const {
      splitz,
      isStorefrontPage,
      fetchPaymentPagePayments,
      fetchStorefrontPayments,
      paymentPageId,
    } = this.props;

    if (isStorefrontPage) {
      return fetchStorefrontPayments(paymentPageId, params);
    }

    return fetchPaymentPagePayments('page', splitz, paymentPageId, params);
  };

  render() {
    const { children, isStorefrontPage, payments, storefrontPayments, splitz, ...restProps } =
      this.props;
    const tableData = isStorefrontPage || isFetchViaNCA(splitz) ? storefrontPayments : payments;

    return (
      <div class="content-wrapper">
        {children}

        {/* hiding filters for storefront until backend dev is completed */}
        {!isStorefrontPage && (
          <PaymentsListFilter
            form="paymentListFilter"
            count={this.state.count}
            onSubmit={this.search}
            fetchAll={this.fetchAll}
          />
        )}
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

export default withSplitzService(withRouter(PaymentsList));
