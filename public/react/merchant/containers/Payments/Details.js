import React, { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import Amount from 'rzp/ui/Amount';
import PaymentDetails from 'merchant/components/Payments/PaymentDetails';
import * as NotificationsActions from 'rzp/modules/notifications';
import * as PaymentActions from 'merchant/modules/payments/details';
import * as ModalActions from 'rzp/modules/modals';
import RefundModal from './RefundModal';

@connect(state => state.payment, {
  ...ModalActions,
  ...PaymentActions,
  ...NotificationsActions,
})
export default class PaymentDetailsContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  componentWillMount() {
    this.props.fetchItem(this.props.id).then(() => {
      this.props.fetchRefunds(this.props.payment);
    });
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id).then(payment => {
        this.props.fetchRefunds(payment);
      });
    }
  }

  fetchCardDetails = payment => {
    return this.props.fetchCardDetails(payment);
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

  openRefundModal = payment => {
    this.props.openModal({
      component: <RefundModal payment={payment} />,
    });
  };

  render() {
    let { loading, error, payment, card, refunds } = this.props;
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: this.props.error,
      };
    }

    return (
      <PaymentDetails
        payment={payment}
        card={card}
        refunds={refunds}
        isLoading={loading}
        statusMsg={statusMsg}
        onToggleCardDetails={this.fetchCardDetails}
        confirmCapture={this.confirmCapture}
        openRefundModal={this.openRefundModal}
      />
    );
  }
}
