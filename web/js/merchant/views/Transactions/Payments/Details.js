import React, { Component } from 'react';
import { compose } from 'redux';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import Amount from 'common/ui/Amount';
import PaymentDetails from 'merchant/views/Transactions/Payments/components/PaymentDetails';
// eslint-disable-next-line import/no-cycle
import DisputeDetails from 'merchant/views/Transactions/Disputes/Details';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import * as PaymentActions from 'merchant/reducers/payments/details';
import * as ModalActions from 'merchant_common/reducers/modals';
import RefundModal from 'merchant/views/Transactions/Payments/components/RefundModal';
import { fetchSettlementAmount } from 'merchant/reducers/home';
import { expandSlider, compactSlider as fnCompactSlider } from 'merchant_common/reducers/slider';
import PaymentTransferNew from 'merchant/views/Marketplace/Transfers/New';
import {
  getKeysSeparatedByPipe,
  getEventCategoryFromPath,
  getCommonAnalyticsProperties,
} from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import DualDetailView, { PrimaryView, SecondaryView } from 'common/new-ui/DualDetailView';
import { updateItemInPayments } from 'merchant/reducers/collection';

class PaymentDetailsContainer extends Component {
  constructor(props) {
    super(props);
    this.state = {};
    this.transfersView = React.createRef();
  }

  static contextTypes = {
    confirm: PropTypes.func,
  };

  fetchData = (id) => {
    this.props.resetPayment();

    this.props.fetchItem(id).then((payment) => {
      if (payment.amount_refunded !== 0) {
        this.props.fetchRefunds(payment);
      }

      if (payment.method === 'bank_transfer') {
        this.props.fetchBankTransfer(payment);
      } else if (payment.method === 'upi') {
        this.props.fetchUPITransfer(payment);
      }

      if (['created', 'authorized', 'failed'].indexOf(payment.status) < 0) {
        this.props.fetchTransfers(payment);
      }
    });
  };

  checkSecView(props) {
    if (!props.entity_name) {
      this.props.compactSlider();

      // To avoid not toggling issue when browser back btn is clicked when secondary view is overlayed in dual view while small-screen
      if (this.transfersView) {
        this.transfersView.current.classList.add('toggle-slider');
      }
    } else {
      this.props.expandSlider();
      // To avoid not toggling issue when browser back btn is clicked when secondary view is overlayed in dual view while small-screen
      if (this.transfersView) {
        this.transfersView.current.classList.remove('toggle-slider');
      }
    }
  }

  componentDidMount() {
    const { closeUrl, id } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);

    if (eventCategory)
      window.rzpAnalytics({
        eventCategory,
        eventAction: 'Open Details - Payments',
        eventLabel: `payment_id=${id}`,
      });

    this.props.fetchMerchantManualAction(id);
  }

  componentWillUnmount() {
    const { closeUrl, id } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);

    if (eventCategory)
      window.rzpAnalytics({
        eventCategory,
        eventAction: 'Close Details - Payments',
        eventLabel: `payment_id=${id}`,
      });
  }

  componentWillMount() {
    this.fetchData(this.props.id);
    this.props.fetchSettlementAmount();
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchData(nextProps.id);
    }
  }

  fetchCardDetails = (payment) => {
    return this.props.fetchCardDetails(payment);
  };

  goToLink = (link) => {
    if (this.props.isOpenedInDualMode && link !== 'transfers/new') {
      this.props.history.push(`/${link}`);
    }
    // Don't do anything if dual view already opened
    else if (!this.props.entity_name) {
      this.props.history.push(`/payments/${this.props.payment.id}/${link}`);
    }
  };

  confirmCapture = (payment) => {
    const { closeUrl } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);

    window.rzpAnalytics({
      eventCategory,
      eventAction: 'Open Form - Capture',
      eventLabel: `payment_id=${payment.id}`,
    });

    this.context
      .confirm({
        header: 'Are you sure you want to capture this payment?',
        message: () => (
          <div class="text-semi-muted">
            <p>
              The payment amount is{' '}
              <b>
                <Amount value={payment.capturableAmount} currency={payment.currency} />
              </b>
            </p>
          </div>
        ),
        affirmativeLabel: 'Yes, Capture',
        affirmativePendingLabel: 'Capturing...',
        abortLabel: "No, don't!",
        abort: () => {
          analyticsTrack({
            objectName: 'capture payment confirmation popup',
            actionName: 'clicked',
            screen: 'home page',
            properties: {
              ...payment.analyticsPayload(),
              action: 'no',
              location: 'payments',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
        },
        action: () => {
          window.rzpAnalytics({
            eventCategory,
            eventAction: 'Capture - Payment',
            eventLabel: `payment_id=${payment.id}`,
          });
          analyticsTrack({
            objectName: 'capture payment confirmation popup',
            actionName: 'clicked',
            screen: 'home page',
            properties: {
              ...payment.analyticsPayload(),
              action: 'yes',
              location: 'payments',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          return this.props
            .capturePayment(payment)
            .then(() => {
              analyticsTrack({
                objectName: 'capture payment',
                actionName: 'status',
                screen: 'home page',
                properties: {
                  ...payment.analyticsPayload(),
                  paymentStatus: payment.status,
                  status: 'success',
                  location: 'payments',
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
              this.props.showNotification({
                type: 'success',
                message: 'Payment Captured',
                closeTimeout: 5000,
              });
            })
            .catch(({ errors }) => {
              analyticsTrack({
                objectName: 'capture payment',
                actionName: 'status',
                screen: 'home page',
                properties: {
                  ...payment.analyticsPayload(),
                  status: 'failure',
                  failureReason: errors[0],
                  location: 'payments',
                  ...getCommonAnalyticsProperties(window.rzp_user),
                },
              });
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

  secClose = (closeTransferDetails) => {
    const { history, location } = this.props;

    history.push(
      location.pathname.replace(!closeTransferDetails ? /\/[^/]+\/[^/]+\/?$/ : /\/[^/]+\/?$/, ''),
    );
  };

  onCreateTransfer = () => {
    this.secClose();
    this.props.fetchItem(this.props.id).then((payment) => {
      this.props.updateItemInPayments(payment);
      this.props.fetchTransfers(payment);
    });
  };

  onTransferReverse = () => {
    this.props.fetchItem(this.props.id).then((payment) => {
      this.props.updateItemInPayments(payment);
    });
  };

  onPaymentRefund = () => {
    this.props.fetchItem(this.props.id).then((payment) => {
      this.props.updateItemInPayments(payment);
      this.props.fetchRefunds(payment);
    });
  };

  onUpdateReferenceId = () => {
    this.props.fetchItem(this.props.id).then((payment) => {
      this.props.updateItemInPayments(payment);
    });
  };

  openRefundModal = (payment, refunds) => {
    this.props.openModal({
      component: (
        <RefundModal
          refunds={refunds}
          fetchMerchantBalance={this.props.fetchCurrentBalance}
          fetchRefundFee={this.props.fetchRefundFee}
          payment={payment}
          onRefund={this.onPaymentRefund}
          onMount={this.onRefundModalMount}
          onUnmount={this.onRefundModalUnmount}
          afterRefund={this.afterRefund}
        />
      ),
      size: 'small',
    });
  };

  onRefundModalMount = (payment) => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Payments',
      eventAction: 'Open Form - Refund',
      eventLabel: `payment_id=${payment.id}`,
    });
  };

  onRefundModalUnmount = (payment) => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Payments',
      eventAction: 'Close Form - Refund',
      eventLabel: `payment_id=${payment.id}`,
    });
  };

  afterRefund = ({ amount, partial, payment }) => {
    const label = {
      payment_id: payment.id,
      partial_payment_enabled: partial || payment.amount_refunded > 0.0,
    };
    if (partial) {
      label.partial_payment_enabled = partial;
    }
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Payments',
      eventAction: 'Refund - Payment',
      eventLabel: getKeysSeparatedByPipe(label),
      eventValue: amount,
    });
  };

  onRefundDetailsToggleClick = (payment, speed_requested) => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Payments',
      eventAction: 'See - Payment Refund Details',
      eventLabel: `payment_id=${payment.id}`,
      speed_requested,
    });
  };

  viewSettlementOverview = () => {
    window.rzpAnalytics({
      eventCategory: 'Settlement Revamp',
      eventAction: 'View - Settlements Breakup',
      eventLabel: `Settled on`,
    });
  };

  render() {
    const {
      loading,
      error,
      payment,
      refunds,
      transfers,
      bankTransfer,
      upiTransfer,
      config,
      entity_name,
      merchantManualAction,
    } = this.props;
    let statusMsg = {};

    const { card = {} } = payment;

    if (error) {
      statusMsg = {
        type: 'error',
        message: this.props.error,
      };
    }

    return (
      <DualDetailView secondaryView={entity_name}>
        <PrimaryView>
          <PaymentDetails
            payment={payment}
            card={card}
            bankTransfer={bankTransfer}
            upiTransfer={upiTransfer}
            refunds={refunds}
            transfers={transfers}
            isLoading={loading}
            statusMsg={statusMsg}
            onToggleCardDetails={this.fetchCardDetails}
            confirmCapture={this.confirmCapture}
            goToLink={this.goToLink}
            openRefundModal={this.openRefundModal}
            onRefundDetailsToggleClick={this.onRefundDetailsToggleClick}
            onUpdateReferenceId={this.onUpdateReferenceId}
            isRoleAllowedEdit={this.props.user.isAllowedEdit('payments')}
            viewSettlementOverview={this.viewSettlementOverview}
            config={config}
            user={this.props.user}
            onClose={this.props.onCloseSecView}
            merchantManualAction={merchantManualAction}
            settlement_amount={this.props.settlement_amount}
          />
        </PrimaryView>
        <SecondaryView entityName="disputes">
          <DisputeDetails id={this.props.entity_id} onCloseSecView={() => this.secClose(null)} />
        </SecondaryView>
        <SecondaryView entityName="transfers">
          <ShowWhen
            apiFeatureEnabled="Marketplace"
            additionalCondition={(user) => user.isAllowedView('payments')}
          >
            <PaymentTransferNew
              paymentId={payment && payment.id}
              onClose={() => this.secClose(null)}
              onCreate={this.onCreateTransfer}
              ref={(instance) => (this.transfersView = instance)}
              isDirectTransferEnabled={this.props.user.isDirectTransferEnabled}
            />
          </ShowWhen>
        </SecondaryView>
      </DualDetailView>
    );
  }
}

export default compose(
  withRouter,
  connect(
    (state) => {
      return {
        ...state.payment,
        user: state.session.user,
        config: state.config.config,
        settlement_amount: state.home.settlement_amount,
      };
    },
    {
      fetchSettlementAmount,
      expandSlider,
      compactSlider: fnCompactSlider,
      updateItemInPayments,
      ...ModalActions,
      ...PaymentActions,
      ...NotificationsActions,
    },
  ),
)(PaymentDetailsContainer);
