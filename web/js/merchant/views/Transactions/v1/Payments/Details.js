/* eslint-disable import/order */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import ShowWhen from 'merchant/components/ShowWhen';
import Amount from 'common/ui/Amount';
import PaymentDetails from 'merchant/views/Transactions/v1/Payments/components/PaymentDetails';
import { compose, bindActionCreators } from 'redux';
// eslint-disable-next-line import/no-cycle
import {
  getKeysSeparatedByPipe,
  getEventCategoryFromPath,
  getCommonAnalyticsProperties,
} from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import DualDetailView, { PrimaryView, SecondaryView } from 'common/new-ui/DualDetailView';
import { updateItemInPayments } from 'merchant/reducers/collection';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { fetchSettlementAmount } from 'merchant/reducers/home';
import { fetchTerminalProviders } from 'merchant/reducers/navigator/details';
import * as PaymentActions from 'merchant/reducers/payments/details';
import { fetchIsAdminAsMerchant } from 'merchant/reducers/profile';
import PaymentTransferNew from 'merchant/views/Marketplace/Transfers/New';
import { fetchBankSettleStatus, customSettlementEnabled } from 'merchant/views/Settlements/v2/util';
// eslint-disable-next-line import/no-cycle
import DisputeDetails from 'merchant/views/Transactions/v1/Disputes/Details';
import CollectEzetapKeys from 'merchant/views/Transactions/v1/Payments/components/CollectEzetapKeys';
import RefundModal from 'merchant/views/Transactions/v1/Payments/components/RefundModal';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { expandSlider, compactSlider as fnCompactSlider } from 'merchant_common/reducers/slider';

// eslint-disable-next-line react/no-unsafe
class PaymentDetailsContainer extends Component {
  constructor(props) {
    super(props);
    this.state = {
      customSettlementLoading: false,
      adminAsMerchant: false,
      bankSettleStatus: '',
      showCustomSettlDetails: false,
    };
    this.transfersView = React.createRef();
  }

  static contextTypes = {
    confirm: PropTypes.func,
  };

  checkFeatureFlags = () => {
    const { user } = this.props;
    const isCustomSettlement = customSettlementEnabled(user);
    if (isCustomSettlement) {
      this.getCustomSettleDetails();
    }
  };

  getCustomSettleDetails = () => {
    const { showNotification, fetchIsAdminAsMerchant, payment } = this.props;
    const setlID = payment?.transaction?.settlement?.id?.replace('setl_', '');
    const promiseList = [];
    this.setState({
      customSettlementLoading: true,
    });
    promiseList.push(fetchIsAdminAsMerchant());
    promiseList.push(fetchBankSettleStatus(setlID));
    return Promise.all(promiseList)
      .then((response) => {
        const [adminAsMerchantResp, bankSettleStatusResp] = response;
        const adminAsMerchant = adminAsMerchantResp?.data?.is_admin_as_merchant ?? false;
        const bankSettleStatus = bankSettleStatusResp?.data?.org_settlement?.status ?? '';
        this.setState({
          customSettlementLoading: false,
          adminAsMerchant,
          bankSettleStatus,
          showCustomSettlDetails: true,
        });
      })
      .catch(() => {
        this.setState({
          customSettlementLoading: false,
        });
        showNotification({
          type: 'error',
          message: 'Something went wrong, please try again later',
        });
      });
  };

  fetchData = (id) => {
    const {
      resetPayment,
      fetchItem,
      fetchRefunds,
      fetchBankTransfer,
      fetchUPITransfer,
      fetchTransfers,
      user,
    } = this.props;
    resetPayment();

    const dashboardFlag = [];
    if (user?.isPayerNameEnabled) {
      dashboardFlag.push('upi_payer_name');
    }

    fetchItem(id, dashboardFlag).then((payment) => {
      /* istanbul ignore else */
      if (payment.amount_refunded !== 0) {
        fetchRefunds(payment);
      }

      /* istanbul ignore else */
      if (payment.method === 'bank_transfer') {
        fetchBankTransfer(payment);
      } else if (payment.method === 'upi') {
        fetchUPITransfer(payment);
      }

      if (['created', 'authorized', 'failed'].indexOf(payment.status) < 0) {
        fetchTransfers(payment);
      }

      if (payment?.transaction?.settlement?.id) {
        this.checkFeatureFlags();
      }

      const params = new Proxy(new URLSearchParams(window.location.search), {
        get: (searchParams, prop) => searchParams.get(prop),
      });

      const initiatePoint = params?.init_point;
      const initiatePage = params?.init_page;
      const screen = initiatePage?.split('.')[0];
      const page = initiatePage?.split('.')[1];
      const selfServeSuccessData = {
        selfServeAction: 'Payment Details Fetched',
        screen: 'Payment Details',
        props: {},
      };

      if ((initiatePoint, initiatePage)) {
        selfServeSuccessData.props.initiatePoint = initiatePoint;
        if (screen) selfServeSuccessData.screen = screen;
        if (page) selfServeSuccessData.page = page;
        if (window && window.session_id) selfServeSuccessData.props.sessionId = window.session_id;
      }

      selfServeTrackSuccess(selfServeSuccessData);
    });
  };

  componentDidMount() {
    const { closeUrl, id, fetchMerchantManualAction } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);

    /* istanbul ignore else */
    if (eventCategory)
      window.rzpAnalytics?.({
        eventCategory,
        eventAction: 'Open Details - Payments',
        eventLabel: `payment_id=${id}`,
      });
    fetchMerchantManualAction(id);
  }

  componentWillUnmount() {
    const { closeUrl, id } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);

    /* istanbul ignore else */
    if (eventCategory)
      window.rzpAnalytics?.({
        eventCategory,
        eventAction: 'Close Details - Payments',
        eventLabel: `payment_id=${id}`,
      });
  }

  UNSAFE_componentWillMount() {
    const { id, fetchSettlementAmount, user, fetchProviders } = this.props;
    this.fetchData(id);
    fetchSettlementAmount();
    /* istanbul ignore else */
    if (user?.isSingleReconEnabled && user?.isOptimizerEnabled) {
      fetchProviders();
    }
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    const { id } = this.props;
    if (id !== nextProps.id) {
      this.fetchData(nextProps.id);
    }
  }

  goToLink = (link) => {
    const { isOpenedInDualMode, history, entity_name, payment } = this.props;
    /* istanbul ignore else */
    if (isOpenedInDualMode && link !== 'transfers/new') {
      history.push(`/${link}`);
    }
    // Don't do anything if dual view already opened
    else if (!entity_name) {
      history.push(`/payments/${payment?.id}/${link}`);
    }
  };

  confirmCapture = (payment) => {
    const { closeUrl, capturePayment, showNotification } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);

    window.rzpAnalytics?.({
      eventCategory,
      eventAction: 'Open Form - Capture',
      eventLabel: `payment_id=${payment?.id}`,
    });

    this.context
      .confirm({
        header: 'Are you sure you want to capture this payment?',
        message: () => (
          <div className="text-semi-muted">
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
          window.rzpAnalytics?.({
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
          return capturePayment(payment)
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
              showNotification({
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
              showNotification({
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
    const { fetchItem, id, updateItemInPayments, fetchTransfers } = this.props;
    this.secClose();
    fetchItem(id).then((payment) => {
      updateItemInPayments(payment);
      fetchTransfers(payment);
    });
  };

  onPaymentRefund = () => {
    const { fetchItem, id, updateItemInPayments, fetchRefunds } = this.props;
    fetchItem(id).then((payment) => {
      updateItemInPayments(payment);
      fetchRefunds(payment);
    });
  };

  onUpdateReferenceId = () => {
    const { fetchItem, id, updateItemInPayments } = this.props;
    fetchItem(id).then((payment) => {
      updateItemInPayments(payment);
    });
  };

  openRefundModal = (payment, refunds) => {
    const { openModal, fetchCurrentBalance, fetchRefundFee } = this.props;
    openModal({
      component: (
        <RefundModal
          refunds={refunds}
          fetchMerchantBalance={fetchCurrentBalance}
          fetchRefundFee={fetchRefundFee}
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

  /* istanbul ignore next */
  onRefundModalMount = (payment) => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments',
      eventAction: 'Open Form - Refund',
      eventLabel: `payment_id=${payment.id}`,
    });
  };

  /* istanbul ignore next */
  onRefundModalUnmount = (payment) => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments',
      eventAction: 'Close Form - Refund',
      eventLabel: `payment_id=${payment.id}`,
    });
  };

  /* istanbul ignore next */
  afterRefund = ({ amount, partial, payment }) => {
    const label = {
      payment_id: payment.id,
      partial_payment_enabled: partial || payment.amount_refunded > 0.0,
    };
    if (partial) {
      label.partial_payment_enabled = partial;
    }
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments',
      eventAction: 'Refund - Payment',
      eventLabel: getKeysSeparatedByPipe(label),
      eventValue: amount,
    });
  };

  /* istanbul ignore next */
  onRefundDetailsToggleClick = (payment, speed_requested) => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments',
      eventAction: 'See - Payment Refund Details',
      eventLabel: `payment_id=${payment.id}`,
      speed_requested,
    });
  };

  /* istanbul ignore next */
  viewSettlementOverview = () => {
    window.rzpAnalytics?.({
      eventCategory: 'Settlement Revamp',
      eventAction: 'View - Settlements Breakup',
      eventLabel: `Settled on`,
    });
  };

  collectEzetapKeys = () => {
    const { openModal } = this.props;
    openModal({
      component: <CollectEzetapKeys openRefundModal={this.openRefundModal} />,
      size: 'regular',
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
      terminalProviders,
      user,
      org,
      onCloseSecView,
      settlement_amount,
      entity_id,
    } = this.props;
    const { customSettlementLoading, adminAsMerchant, showCustomSettlDetails, bankSettleStatus } =
      this.state;
    let statusMsg = {};

    const { card = {} } = payment;

    if (error) {
      statusMsg = {
        type: 'error',
        message: error,
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
            confirmCapture={this.confirmCapture}
            goToLink={this.goToLink}
            openRefundModal={this.openRefundModal}
            onRefundDetailsToggleClick={this.onRefundDetailsToggleClick}
            onUpdateReferenceId={this.onUpdateReferenceId}
            isRoleAllowedEdit={user?.isAllowedEdit('payments')}
            viewSettlementOverview={this.viewSettlementOverview}
            config={config}
            user={user}
            org={org}
            onClose={onCloseSecView}
            merchantManualAction={merchantManualAction}
            settlement_amount={settlement_amount}
            terminalProviders={terminalProviders}
            customSettlementLoading={customSettlementLoading}
            adminAsMerchant={adminAsMerchant}
            showCustomSettlDetails={showCustomSettlDetails}
            bankSettleStatus={bankSettleStatus}
            collectEzetapKeys={this.collectEzetapKeys}
            fetchEzetapKeys={this.props.fetchEzetapKeys}
            isMobile={this.props.isMobile}
          />
        </PrimaryView>
        <SecondaryView entityName="disputes">
          <DisputeDetails id={entity_id} onCloseSecView={() => this.secClose(null)} />
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
              isDirectTransferEnabled={user?.isDirectTransferEnabled}
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
        org: state.session.org,
        config: state.config.config,
        settlement_amount: state.home.settlement_amount,
        terminalProviders: state.navigator.terminalProviders,
        isMobile: state.app.isMobileResolution,
      };
    },
    (dispatch) => {
      return bindActionCreators(
        {
          fetchSettlementAmount,
          expandSlider,
          compactSlider: fnCompactSlider,
          updateItemInPayments,
          fetchProviders: fetchTerminalProviders,
          fetchIsAdminAsMerchant,
          ...ModalActions,
          ...PaymentActions,
          ...NotificationsActions,
        },
        dispatch,
      );
    },
  ),
)(PaymentDetailsContainer);
