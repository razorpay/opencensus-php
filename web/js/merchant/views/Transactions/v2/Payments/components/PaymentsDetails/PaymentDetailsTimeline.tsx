import React, { useEffect, useState } from 'react';
import {
  Box,
  Button,
  Card,
  CardBody,
  Divider,
  Heading,
  Link,
  RefreshIcon,
  Spinner,
  Text,
  RotateCounterClockWiseIcon,
} from '@razorpay/blade/components';
import RefundIcon from 'assets/transactions/refund.svg';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';

import { useI18Service } from 'common/i18';
import { useSplitzService } from 'common/splitz';
import { deepClone } from 'common/utils/rzp-utils';
import * as PaymentActions from 'merchant/reducers/payments/details';
import {
  fetchBankTransfer,
  fetchInstantRefundFeeFn,
  fetchPaymentIdTimelineData,
  fetchTransactionTimelineDataFn,
  fetchTransfersFn,
  refundPaymentFn,
} from 'merchant/views/Transactions/model';
import RefundModal from 'merchant/views/Transactions/v1/Payments/components/RefundModalNew';
import RefundModalRevamp from 'merchant/views/Transactions/v2/Payments/components/PaymentRefund';
import TimeLine from 'merchant/views/Transactions/v2/Payments/components/Timeline';
import { IconBackground } from 'merchant/views/Transactions/v2/Payments/components/Timeline/styled';
import {
  SkipTimelineTransactions,
  TimelineJourneyPoint,
} from 'merchant/views/Transactions/v2/Payments/components/Timeline/types';
import { PaymentsTimeline } from 'merchant/views/Transactions/v2/Payments/types';
import { trackDetailsClick } from 'merchant/views/Transactions/v2/common/tracking';
import {
  isRefundRevampEnabled,
  isSettlementRetryTimelineEnabled,
} from 'merchant/views/Transactions/v2/common/utils';
import * as ModalActions from 'merchant_common/reducers/modals';

import { IBankTransfer, IPaymentDetails, IPaymentIdRefundDetail } from './types';
import {
  getDisputesTimelineData,
  getPaymentTimelineData,
  getRefundsTimelineData,
  getSettlementTimelineData,
  isIssueRefundDisabled,
} from './utils';

interface PaymentDetailsTimelineProps {
  paymentIdDetails: IPaymentDetails;
  paymentIdRefundDetails: IPaymentIdRefundDetail[];
  fetchCurrentBalance: () => Promise<Record<string, string>>;
  fetchRefundFee: () => Promise<Record<string, string>>;
  openModal: (args: any) => void;
  reFetchPageDetails: (id: string) => void;
  user: any;
}
function PaymentDetailsTimeline({
  paymentIdDetails,
  paymentIdRefundDetails,
  fetchCurrentBalance,
  fetchRefundFee,
  openModal,
  reFetchPageDetails,
  user,
}: PaymentDetailsTimelineProps): JSX.Element {
  const [paymentTimelineData, setpaymentTimelineData] = useState<PaymentsTimeline | null>(null);
  const [skipTransactionTimeline, setSkipTransactionTimeline] =
    useState<SkipTimelineTransactions | null>(null);
  const [didPaymentTimelineDataError, setdidPaymentTimelineDataError] = useState<boolean>(false);
  const [didRetryTimelineDataError, setdidRetryTimelineDataError] = useState<boolean>(false);

  const [timelineData, settimelineData] = useState<TimelineJourneyPoint[]>([]);
  const [bankTransfer, setbankTransfer] = useState<IBankTransfer | null>(null);
  const splitz = useSplitzService();
  const { isConfigTagEnabled } = useI18Service();

  /**
   * If Splitz experiment is enabled
   * & transaction is present in payment details
   * & settled is true
   * & settled_at is present
   * Show Transaction Settlement timeline
   */
  const shouldShowRetryTimeline = (): boolean => {
    const isRetryTimelineEnabled = isSettlementRetryTimelineEnabled(splitz, user);
    const { transaction } = paymentIdDetails;
    if (!transaction) {
      return false;
    }
    const { id, settled: isSettled, settled_at } = transaction;
    return isRetryTimelineEnabled && !!id && !!settled_at && !!!isSettled;
  };

  const updateTimelineWithSettlementData = () => {
    const settlementTimelineData = getSettlementTimelineData(paymentIdDetails);
    settimelineData((state) => [...state, ...settlementTimelineData]);
  };

  const updateTimelineWithPaymentData = () => {
    const paymentIdTimelineData = getPaymentTimelineData(
      paymentIdDetails,
      paymentTimelineData,
      bankTransfer,
    );
    settimelineData((_) => [...paymentIdTimelineData]);
  };

  const updateTimelineWithRefundsData = () => {
    const refundTimelineData = getRefundsTimelineData(paymentIdRefundDetails);
    settimelineData((state) => [...state, ...refundTimelineData]);
  };

  const updateTimelineWithDisputesData = () => {
    const disputesTimelineData = getDisputesTimelineData(paymentIdDetails);
    settimelineData((state) => [...state, ...disputesTimelineData]);
  };

  const fetchPaymentsTimelineDataFn = async (): Promise<void> => {
    try {
      const response = await fetchPaymentIdTimelineData(paymentIdDetails.id);
      setpaymentTimelineData(response.data);
    } catch (_) {
      setdidPaymentTimelineDataError(true);
    }
  };

  const fetchRetryTimeline = async (): Promise<void> => {
    try {
      const retryTransaction = await fetchTransactionTimelineDataFn(
        paymentIdDetails.transaction.id,
      );
      setSkipTransactionTimeline(retryTransaction.data);
    } catch (e) {
      setdidRetryTimelineDataError(true);
    }
  };

  const fetchBankTransferFn = async () => {
    const response = await fetchBankTransfer(paymentIdDetails.id);
    setbankTransfer(response.data);
  };

  useEffect(() => {
    fetchPaymentsTimelineDataFn();
    if (shouldShowRetryTimeline()) fetchRetryTimeline();
    if (paymentIdDetails.method === 'bank_transfer') fetchBankTransferFn();
  }, [paymentIdDetails]);

  const makeTimelineData = () => {
    updateTimelineWithPaymentData();
    updateTimelineWithSettlementData();
    updateTimelineWithRefundsData();
    updateTimelineWithDisputesData();
  };

  useEffect(() => {
    if (paymentIdDetails.method === 'bank_transfer') {
      if (paymentTimelineData && bankTransfer) makeTimelineData();
    } else if (paymentTimelineData) {
      makeTimelineData();
    }
    // everytime source data is updated, rebuild the timeline
  }, [paymentTimelineData, paymentIdDetails, paymentIdRefundDetails, bankTransfer]);

  const reFetchPaymentTimelineData = () => {
    setpaymentTimelineData(null);
    setdidPaymentTimelineDataError(false);
    fetchPaymentsTimelineDataFn();
  };

  const onRefundSuccess = () => {
    reFetchPageDetails(paymentIdDetails.id);
  };

  const isCollectXEnabled = user.isCollectXEnabled;

  const openIssueRefundModal = () => {
    const clonedPaymentDetails = deepClone(paymentIdDetails);
    clonedPaymentDetails.refund = refundPaymentFn(paymentIdDetails.id);
    clonedPaymentDetails.fetchTransfers = fetchTransfersFn(paymentIdDetails.id);
    clonedPaymentDetails.fetchInstantRefundFee = fetchInstantRefundFeeFn;
    trackDetailsClick({
      objectName: 'Issue Refund',
      properties: {
        latestTransactionStatus: clonedPaymentDetails.status,
        method: clonedPaymentDetails.method,
        paymentId: clonedPaymentDetails.id,
      },
    });

    const isRefundModalRevampEnabled = isRefundRevampEnabled(splitz);

    const modalProps = {
      fetchMerchantBalance: fetchCurrentBalance,
      fetchRefundFee,
      payment: clonedPaymentDetails,
      onRefund: onRefundSuccess,
    };

    const RefundModalComponent = isRefundModalRevampEnabled ? RefundModalRevamp : RefundModal;

    openModal({
      isNew: !!isRefundModalRevampEnabled,
      component: <RefundModalComponent {...modalProps} />,
      size: 'small',
    });
  };

  return (
    <Card elevation="none" testID="payment-details-timeline">
      <CardBody>
        {paymentTimelineData ? (
          <Box>
            <Box marginLeft="-8px">
              <Heading weight="semibold" color="surface.text.gray.normal" size="small">
                Timeline
              </Heading>
            </Box>
            <TimeLine
              data={timelineData}
              bankTransfer={bankTransfer}
              paymentIdDetails={paymentIdDetails}
              fetchPaymentsTimelineData={fetchPaymentsTimelineDataFn}
              reFetchPageDetails={reFetchPageDetails}
              skipTransactionTimeline={skipTransactionTimeline}
              shouldShowRetryTimeline={shouldShowRetryTimeline()}
              didRetryTimelineDataError={didRetryTimelineDataError}
            />
          </Box>
        ) : didPaymentTimelineDataError ? (
          <Box>
            <Text variant="body" size="medium" weight="regular" color="surface.text.gray.subtle">
              We couldn’t load your payment timeline. Refresh to try again
            </Text>
            <Box paddingTop="spacing.3">
              <Link icon={RefreshIcon} onClick={reFetchPaymentTimelineData}>
                Refresh
              </Link>
            </Box>
          </Box>
        ) : (
          <Box display="flex" justifyContent="center">
            <Spinner accessibilityLabel="timeline-loader" />
          </Box>
        )}
        {!isIssueRefundDisabled(paymentIdDetails, user) &&
        !user.isJnKOmniEnabled &&
        !isConfigTagEnabled('refunds.refund') &&
        !isCollectXEnabled ? (
          <>
            <Divider dividerStyle="dashed" />
            <Box display="flex" flexDirection="row" padding="12px" marginLeft="-16px" width="100%">
              <Box marginTop="spacing.1">
                <IconBackground status="captured">
                  {user.isCountryIndia ? (
                    <img
                      src={RefundIcon}
                      alt="refund-icon"
                      data-testid="refund-icon"
                      style={{ padding: '1px' }}
                    />
                  ) : (
                    <RotateCounterClockWiseIcon
                      data-testid="RotateCounterClockWiseIcon"
                      color="interactive.icon.information.normal"
                      size="medium"
                    />
                  )}
                </IconBackground>
              </Box>
              <Box marginLeft="spacing.3">
                <Text>Initiate full, partial, or instant refunds to your customers </Text>
              </Box>
            </Box>
            <Box paddingLeft="spacing.9" marginLeft="-16px">
              <Button onClick={openIssueRefundModal}>Issue refund</Button>
            </Box>
          </>
        ) : null}
      </CardBody>
    </Card>
  );
}

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      ...PaymentActions,
    },
    dispatch,
  );

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
    }),
    mapDispatchToProps,
  ),
)(PaymentDetailsTimeline);
