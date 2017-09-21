import React, { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import { findDOMNode } from 'react-dom';

import ShowWhen from 'merchant/components/ShowWhen';
import Amount from 'rzp/ui/Amount';
import PaymentDetails from 'merchant/components/Payments/PaymentDetails';
import * as NotificationsActions from 'rzp/modules/notifications';
import * as PaymentActions from 'merchant/modules/payments/details';
import * as ModalActions from 'rzp/modules/modals';

import RefundModal from './RefundModal';

import { expandSlider, compactSlider } from 'rzp/modules/slider';
import PaymentTransferNew from 'merchant/containers/Marketplace/Transfers/New';
import PaymentTransferDetails from 'merchant/containers/Marketplace/Transfers/Details';

@withRouter
@connect(
  state => {
    return { ...state.payment, user: state.session.user };
  },
  {
    expandSlider,
    compactSlider,
    ...ModalActions,
    ...PaymentActions,
    ...NotificationsActions,
  }
)
export default class PaymentDetailsContainer extends Component {
  state = {};

  static contextTypes = {
    confirm: PropTypes.func,
  };

  fetchData = id => {
    this.props.resetPayment();

    this.props.fetchItem(id).then(payment => {
      if (payment.amount_refunded !== 0) {
        this.props.fetchRefunds(payment);
      }

      if (payment.method === 'card' || payment.method === 'emi') {
        this.props.fetchCardDetails(payment);
      }

      if (this.props.payment.amount_transferred !== 0) {
        this.props.fetchTransfers(payment);
      }
    });
  };

  checkSecView(props) {
    if (!props.entity_name && !props.transfer_id) {
      this.props.compactSlider();

      // To avoid not toggling issue when browser back btn is clicked when secondary view is overlayed in dual view while small-screen
      if (this.transfersView && findDOMNode(this.transfersView)) {
        findDOMNode(this.transfersView).classList.add('toggle-slider');
      }
    } else {
      this.props.expandSlider();
      this.setState({
        secView: props.entity_name ? 'new_transfer' : 'transfer',
      });
      // To avoid not toggling issue when browser back btn is clicked when secondary view is overlayed in dual view while small-screen
      if (this.transfersView && findDOMNode(this.transfersView)) {
        findDOMNode(this.transfersView).classList.remove('toggle-slider');
      }
    }
  }

  componentWillMount() {
    this.fetchData(this.props.id);
    this.checkSecView(this.props);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchData(nextProps.id);
    }

    if (
      nextProps.entity_name !== this.props.entity_name ||
      nextProps.transfer_id !== this.props.entity_id
    ) {
      this.checkSecView(nextProps);
    }
  }

  fetchCardDetails = payment => {
    return this.props.fetchCardDetails(payment);
  };

  goToLink = () => {
    if (!this.props.entity_name) {
      // Don't do anything if dual view already opened
      this.props.history.push(
        `/payments/${this.props.payment.id}/transfers/new`
      );

      if (this.transfersView && findDOMNode(this.transfersView)) {
        findDOMNode(this.transfersView).classList.toggle('toggle-slider');
      }
    }
  };

  confirmCapture = payment => {
    this.context
      .confirm({
        header: 'Are you sure you want to capture this payment?',
        message: () =>
          <div class="text-semi-muted">
            <p>
              The payment amount is{' '}
              <b>
                <Amount value={payment.capturableAmount} />
              </b>
            </p>
          </div>,
        affirmativeLabel: 'Yes, Capture',
        affirmativePendingLabel: 'Capturing...',
        abortLabel: "No, don't!",
        action: () => {
          return this.props
            .capturePayment(payment)
            .then(() => {
              this.props.showNotification({
                type: 'success',
                message: 'Payment Captured',
                closeTimeout: 5000,
              });
            })
            .catch(({ errors }) => {
              this.props.showNotification({
                type: 'error',
                message: errors,
                closeTimeout: 5000,
              });
            });
        },
      })
      .catch(() => {});
  };

  secClose = closeTransferDetails => {
    let { compactSlider, history, location } = this.props;
    findDOMNode(this.transfersView).classList.toggle('toggle-slider');

    compactSlider();
    history.push(
      location.pathname.replace(
        !closeTransferDetails ? /\/[^\/]+\/[^\/]+\/?$/ : /\/[^\/]+\/?$/,
        ''
      )
    );
  };

  onCreateTransfer = () => {
    this.secClose();
    this.props.fetchItem(this.props.id).then(payment => {
      this.props.fetchTransfers(payment);
    });
  };

  onTransferReverse = () => {
    this.props.fetchItem(this.props.id);
  };

  onPaymentRefund = () => {
    this.props.fetchItem(this.props.id).then(payment => {
      this.props.fetchRefunds(payment);
    });
  };

  openRefundModal = payment => {
    this.props.openModal({
      component: (
        <RefundModal payment={payment} onRefund={this.onPaymentRefund} />
      ),
    });
  };

  render() {
    let { loading, error, payment, card, refunds, transfers } = this.props;
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: this.props.error,
      };
    }

    const hasMultiContent =
      this.state.secView === 'new_transfer' ||
      this.state.secView === 'transfer';

    return (
      <div className={`${hasMultiContent ? 'multi-content' : ''}`}>
        <PaymentDetails
          payment={payment}
          card={card}
          refunds={refunds}
          transfers={transfers}
          isLoading={loading}
          statusMsg={statusMsg}
          onToggleCardDetails={this.fetchCardDetails}
          confirmCapture={this.confirmCapture}
          goToLink={this.goToLink}
          openRefundModal={this.openRefundModal}
        />

        <ShowWhen apiFeatureEnabled="Marketplace">
          {this.state.secView === 'new_transfer' &&
            <PaymentTransferNew
              paymentId={payment && payment.id}
              onClose={() => this.secClose(null)}
              onCreate={this.onCreateTransfer}
              ref={c => (this.transfersView = c)}
            />}
        </ShowWhen>

        <ShowWhen apiFeatureEnabled="Marketplace">
          {this.state.secView === 'transfer' &&
            <PaymentTransferDetails
              id={this.props.transfer_id}
              onClose={() => this.secClose(true)}
              ref={c => (this.transfersView = c)}
              onReverse={this.onTransferReverse}
              onRefund={this.onPaymentRefund}
            />}
        </ShowWhen>
      </div>
    );
  }
}
