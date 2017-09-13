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
import PaymentTransferNew from 'merchant/containers/Payments/Transfer/New';
import PaymentTransferDetails from 'merchant/containers/Payments/Transfer/Details';

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
    this.props.fetchItem(id).then(() => {
      const additionReqs = [this.props.fetchRefunds(this.props.payment)];

      if (this.props.payment.method === 'card') {
        additionReqs.push(this.props.fetchCardDetails(this.props.payment));
      }

      return Promise.all(additionReqs).then(function() {
        console.log(arguments);
      });
    });

    this.checkSecView(this.props.entity_name);
  };

  componentWillMount() {
    this.fetchData(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id).then(payment => {
        this.props.fetchRefunds(payment);
      });
    }

    if (nextProps.entity_name != this.props.entity_name) {
      this.checkSecView(nextProps.entity_name);
    }
  }

  checkSecView(entityName) {
    if (!entityName) {
      this.props.compactSlider();

      // To avoid not toggling issue when browser back btn is clicked when secondary view is overlayed in dual view while small-screen
      if (this.transfersView && findDOMNode(this.transfersView)) {
        findDOMNode(this.transfersView).classList.add('toggle-slider');
      }
    } else {
      this.props.expandSlider();
      this.setState({
        secView: 'new_transfer',
      });
      // To avoid not toggling issue when browser back btn is clicked when secondary view is overlayed in dual view while small-screen
      if (this.transfersView && findDOMNode(this.transfersView)) {
        findDOMNode(this.transfersView).classList.remove('toggle-slider');
      }
    }

    if (nextProps.entity_name != this.props.entity_name) {
      this.checkSecView(nextProps.entity_name);
    }
  }

  checkSecView(entityName) {
    if (!entityName) {
      this.props.compactSlider();

      // To avoid not toggling issue when browser back btn is clicked when secondary view is overlayed in dual view while small-screen
      if (this.transfersView && findDOMNode(this.transfersView)) {
        findDOMNode(this.transfersView).classList.add('toggle-slider');
      }
    } else {
      this.props.expandSlider();
      this.setState({
        secView: 'new_transfer',
      });
      // To avoid not toggling issue when browser back btn is clicked when secondary view is overlayed in dual view while small-screen
      if (this.transfersView && findDOMNode(this.transfersView)) {
        findDOMNode(this.transfersView).classList.remove('toggle-slider');
      }
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

  openRefundModal = payment => {
    this.props.openModal({
      component: <RefundModal payment={payment} />,
    });
  };

  secClose = () => {
    let { compactSlider, history, location } = this.props;
    findDOMNode(this.transfersView).classList.toggle('toggle-slider');

    compactSlider();
    history.push(location.pathname.replace(/\/[^\/]+\/[^\/]+\/?$/, ''));
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
      <div>
        <PaymentDetails
          payment={payment}
          card={card}
          refunds={refunds}
          isLoading={loading}
          statusMsg={statusMsg}
          onToggleCardDetails={this.fetchCardDetails}
          confirmCapture={this.confirmCapture}
          goToLink={this.goToLink}
          openRefundModal={this.openRefundModal}
        />

        <ShowWhen featureEnabled="Marketplace">
          {this.state.secView === 'new_transfer' &&
            <PaymentTransferNew
              paymentId={payment && payment.id}
              onClose={this.secClose}
              ref={c => (this.transfersView = c)}
            />}
        </ShowWhen>

        <ShowWhen featureEnabled="Marketplace">
          {this.state.secView === 'transfer' &&
            <PaymentTransferDetails
              onClose={this.secClose}
              ref={c => (this.transfersView = c)}
            />}
        </ShowWhen>
      </div>
    );
  }
}
