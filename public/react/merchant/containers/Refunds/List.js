import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import TetherComponent from 'react-tether';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import Header from 'rzp/ui/Header';
import ShowWhen from 'merchant/components/ShowWhen';
import RefundsList from 'merchant/components/Refunds/RefundsList';
import RefundDetails from 'merchant/containers/Refunds/Details';
import PaymentDetails from 'merchant/containers/Payments/Details';
import ListContainer from 'merchant/containers/ListContainer';
import RefundsListFilter from 'merchant/components/Refunds/RefundsListFilter';
import { fetchRefunds } from 'merchant/modules/refunds/list';
import { openSlider } from 'rzp/modules/slider';

@connect(state => state.refunds, { fetchRefunds, openSlider })
export default class RefundsListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchRefunds(params);
  }

  showRefundDetails = refund => {
    this.props.openSlider({
      component: <RefundDetails id={refund.id} />,
      onOpenURL: `/app/refunds/${refund.id}`,
      onCloseURL: '/app/refunds',
    });
  };

  showPaymentDetails = refund => {
    this.props.openSlider({
      component: <PaymentDetails id={refund.payment_id} />,
      onOpenURL: `/app/payments/${refund.payment_id}`,
      onCloseURL: '/app/refunds',
    });
  };

  render() {
    let { loading, refunds, error } = this.props;

    return (
      <div class="content-wrapper">
        <TetherComponent
          target="#transactions-header"
          attachment="top right"
          targetAttachment="top right"
          offset="-8px 20px"
        >
          <div />{/* required by react-tether */}
          <ShowWhen
            featureEnabled="Batchrefunds"
            myRole="owner manager operations admin finance"
          >
            <NavLink
              class="btn btn-primary pull-right"
              to="/app/refunds/batchupload"
            >
              Batch Refunds
            </NavLink>
          </ShowWhen>
        </TetherComponent>

        <RefundsListFilter
          form="refundListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        {error && <Alert type="error" message={error} />}

        <RefundsList
          refunds={refunds}
          isLoading={loading}
          onRefundClick={this.showRefundDetails}
          onPaymentClick={this.showPaymentDetails}
        />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={refunds.length}
          onClick={this.paginate}
        />
      </div>
    );
  }
}
