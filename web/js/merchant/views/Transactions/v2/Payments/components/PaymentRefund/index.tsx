import React, { useEffect, useState } from 'react';
import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  RupeesIcon,
  TextArea,
  TextInput,
} from '@razorpay/blade/components';
import { convertToMajorUnit, convertToMinorUnit } from '@razorpay/i18nify-js';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';

import { useI18Service } from 'common/i18';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import {
  fetchItem as fetchPayment,
  fetchRefunds,
  fetchTransfers,
  refundPayment,
} from 'merchant/reducers/payments/details';
import { closeModal } from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import InstantRefund from './InstantRefund';
import {
  sendAnalyticsOnLoad,
  trackOnFetchingRefundFee,
  trackRefundAnalyticsOnRefundClick,
  trackRefundAnalyticsOnSuccess,
  trackRefundError,
} from './analytics';
import { calculateHasEnoughFunds, PaymentUtils } from './utils';

const RefundModal = (props) => {
  const {
    payment,
    user,
    fetchTransfers,
    fetchMerchantBalance,
    transfers,
    refundPayment,
    showNotification,
    closeModal,
    fetchRefundFee,
    onRefund,
    defaultRefundSpeed,
    currentBalance,
  } = props;

  const i18 = useI18Service();

  const [isOpen, setIsOpen] = useState(true);
  const [reversal, setReversal] = useState(null);
  const [payableAmount, setPayableAmount] = useState(
    convertToMajorUnit(payment?.amount - payment?.amount_refunded, {
      currency: payment?.currency,
    }),
  );
  const [comment, setComment] = useState('');
  const [hasAmountError, setAmountError] = useState('');
  const payableamountInMinorUnit = convertToMinorUnit(payableAmount, {
    currency: payment.currency,
  });

  const [isInstantRefundChecked, setInstantRefund] = useState(
    (!showWhenUtil({ featureEnabled: 'disable_instant_refunds' }) &&
      defaultRefundSpeed === 'optimum') ||
      !payment.gateway_refund_support,
  );
  const [instantFee, setInstantFee] = useState({ fee: 0, tax: 0 });
  const [isRefundApiInProgress, setRefundApiInProgress] = useState(false);
  const paymentUtils = new PaymentUtils(payment);
  const {
    isRefundButtonDisabled,
    isPartialPayment,
    amountValidation,
    getRefundFeeForPayableAmount,
  } = paymentUtils;
  const isPartial = isPartialPayment(payableAmount);
  const isInstantRefundFeatureEnabled =
    !showWhenUtil({ featureEnabled: 'disable_instant_refunds' }) &&
    !i18.isConfigTagEnabled('refunds.instant_refunds');

  const hasEnoughFunds = calculateHasEnoughFunds({
    payment,
    user,
    currentBalance,
    isInstantRefundChecked,
    payableAmount,
  });

  const getRefundFee = async (amount) => {
    try {
      const refundAmount = convertToMinorUnit(amount, { currency: payment.currency });
      const response = await fetchRefundFee(payment, refundAmount);

      if (response?.data && response.data.fee !== null && response.data.tax !== null) {
        setInstantFee(response.data);
      }
    } catch (error) {
      console.error('Error fetching refund fee:', error);
    }
  };

  useEffect(() => {
    if (user?.isMarketplaceEnabled) {
      fetchTransfers(payment);
    }
    if (isInstantRefundFeatureEnabled) {
      getRefundFee(payableAmount);
    }
    fetchMerchantBalance();
    sendAnalyticsOnLoad({ payment, defaultRefundSpeed, transfers, hasEnoughFunds });
  }, []);

  const handleRefund = async (speedValue) => {
    const data = {
      amount: payableamountInMinorUnit,
      comment,
      reverse_all: reversal ? '1' : '0',
      speed: speedValue,
      ...(isPartial ? {} : { amount: payment.amount - payment.amount_refunded }),
    };

    trackRefundAnalyticsOnRefundClick({ speedValue, payment, defaultRefundSpeed });
    try {
      await refundPayment(payment, data);
      trackRefundAnalyticsOnSuccess({
        defaultRefundSpeed,
        isPartial,
        comment,
        isInstantRefundChecked,
      });
      showNotification({
        type: 'success',
        message: 'Refund successful',
        closeTimeout: 5000,
      });
      if (onRefund) onRefund();
    } catch (errors) {
      trackRefundError(payment, errors);
      if (errors) {
        showNotification({
          type: 'error',
          message: errors || 'Refund failed. Please try again later.',
          closeTimeout: 5000,
        });
      }
    } finally {
      setRefundApiInProgress(false);
      closeModal();
    }
  };

  const issueRefund = () => {
    setRefundApiInProgress(true);
    const hasAmountErrors = amountValidation(payableAmount);
    const speedRequested = isInstantRefundChecked ? 'optimum' : 'normal';

    if (hasAmountErrors) {
      setAmountError(hasAmountErrors);
      setRefundApiInProgress(false);
      return;
    }

    // For partial refund, if reverse all is checked, we cannot reverse when there is more than 1 transfer on the payment.
    if (isPartial && reversal && transfers.items.length > 1) {
      const errorMsg = `Reversals can't be automated when partially refunding a payment with more than 1 transfer to different linked accounts. Create reversals manually before attempting the refund.`;

      showNotification({
        type: 'error',
        message: errorMsg,
        closeTimeout: 10000,
      });
      setRefundApiInProgress(false);
      return;
    }

    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments',
      eventAction: 'Click - Issue Refund',
      eventLabel: `payment_id=${payment.id}`,
      speed_requested: speedRequested,
    });

    if (Number(payableAmount) >= 1) {
      fetchRefundFee(payment, payableamountInMinorUnit).then(() => {
        trackOnFetchingRefundFee({ speedRequested, payment });
        handleRefund(speedRequested);
      });
    }
  };

  const dismissModal = () => {
    setIsOpen(false);
    closeModal();
  };

  const handleChangeAmount = (e) => {
    const amount = e.value;
    getRefundFeeForPayableAmount(amount, getRefundFee);
    const errors = amountValidation(amount);
    setAmountError(errors);
    setPayableAmount(e.value || '');
  };

  return (
    <Modal isOpen={isOpen} onDismiss={dismissModal} size="small">
      <ModalHeader title="Refund Payment" />
      <ModalBody>
        <Box width="100%">
          <TextInput
            label="Refund Amount"
            placeholder="Enter the refund amount"
            type="number"
            helpText={`This will be a ${
              isPartial ? 'partial refund' : 'full refund. Change amount for a partial refund'
            }`}
            leadingIcon={RupeesIcon}
            errorText={hasAmountError}
            value={String(payableAmount)}
            onChange={handleChangeAmount}
            validationState={!!hasAmountError ? 'error' : 'none'}
          />
          <Box marginTop="spacing.6">
            <TextArea
              label="Comments (Optional)"
              placeholder="Add comments"
              value={comment}
              onChange={({ value }): void => {
                setComment(value || '');
              }}
            />
          </Box>
        </Box>

        {isInstantRefundFeatureEnabled ? (
          <InstantRefund
            payment={payment}
            instantFee={instantFee}
            isInstantRefundChecked={isInstantRefundChecked}
            payableAmount={payableAmount}
            onInstantRefundClick={(isChecked) => setInstantRefund(isChecked)}
            reversal={reversal}
            setReversal={setReversal}
            hasEnoughFunds={hasEnoughFunds}
          />
        ) : null}
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end">
          <Button type="button" variant="secondary" marginX="spacing.6" onClick={dismissModal}>
            Cancel
          </Button>
          <Button
            type="button"
            variant="primary"
            onClick={issueRefund}
            isLoading={isRefundApiInProgress}
            isDisabled={isRefundButtonDisabled({
              payableAmount,
              isInstantRefundChecked,
              isRefundApiInProgress,
            })}
          >
            Issue {isPartial ? 'partial' : 'full'} refund
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

const mapStateToProps = (state) => {
  return {
    ...state.session,
    user: state.session.user,
    refundFee: state.payment.refundFee,
    currentBalance: state.payment.current_balance,
    transfers: state.payment.transfers,
    defaultRefundSpeed: state.config.config.default_refund_speed,
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
      refundPayment,
      fetchPayment,
      fetchRefunds,
      fetchTransfers,
      ...NotificationsActions,
    },
    dispatch,
  );

export default compose(connect(mapStateToProps, mapDispatchToProps))(RefundModal);
