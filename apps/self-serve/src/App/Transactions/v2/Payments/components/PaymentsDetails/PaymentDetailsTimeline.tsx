// TODO: Fix the imports, currently out of scope
// @ts-nocheck
import React, { useEffect, useState } from 'react';
import { compose, bindActionCreators, AnyAction, Dispatch } from 'redux';
import { connect } from 'react-redux';
import {
  Box,
  Card,
  CardBody,
  Spinner,
  Link,
  Text,
  RefreshIcon,
  Divider,
  Button,
  Heading,
} from '@razorpay/blade/components';
import RefundModal from 'apps/self-serve/src/App/Transactions/v1/Payments/components/RefundModalNew';
import * as PaymentActions from 'merchant/reducers/payments/details';
import { deepClone } from '@dashboard/shared-utils/rzp-utils';
import {
  fetchInstantRefundFeeFn,
  fetchTransfersFn,
  refundPaymentFn,
  fetchBankTransfer,
  fetchPaymentIdTimelineData,
} from 'apps/self-serve/src/App/Transactions/model';
import { useStore } from 'shell/commonStore';
import {
  getSettlementTimelineData,
  getDisputesTimelineData,
  getRefundsTimelineData,
  getPaymentTimelineData,
  isIssueRefundDisabled,
} from './utils';
import { IPaymentDetails, IPaymentIdRefundDetail, IBankTransfer } from './types';
import RefundIcon from 'apps/self-serve/src/assets/refund.svg';
import { trackDetailsClick } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import { IconBackground } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/Timeline/styled';
import { PaymentsTimeline } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';
import { TimelineJourneyPoint } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/Timeline/types';
import TimeLine from 'apps/self-serve/src/App/Transactions/v2/Payments/components/Timeline';

interface PaymentDetailsTimelineProps {
  paymentIdDetails: IPaymentDetails;
  paymentIdRefundDetails: IPaymentIdRefundDetail[];
  fetchCurrentBalance: () => Promise<Record<string, string>>;
  fetchRefundFee: () => Promise<Record<string, string>>;
  reFetchPageDetails: (id: string) => void;
  user: any;
}
function PaymentDetailsTimeline({
  paymentIdDetails,
  paymentIdRefundDetails,
  fetchCurrentBalance,
  fetchRefundFee,
  reFetchPageDetails,
  user,
}: PaymentDetailsTimelineProps): JSX.Element {
  const openModal = useStore((state) => state.openModal);
  const [paymentTimelineData, setpaymentTimelineData] = useState<PaymentsTimeline | null>(null);
  const [didPaymentTimelineDataError, setdidPaymentTimelineDataError] = useState<boolean>(false);
  const [timelineData, settimelineData] = useState<TimelineJourneyPoint[]>([]);
  const [bankTransfer, setbankTransfer] = useState<IBankTransfer | null>(null);

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
    settimelineData(() => [...paymentIdTimelineData]);
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

  const fetchBankTransferFn = async () => {
    const response = await fetchBankTransfer(paymentIdDetails.id);
    setbankTransfer(response.data);
  };

  useEffect(() => {
    fetchPaymentsTimelineDataFn();
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

  const openIssueRefundModal = () => {
    const _payment = deepClone(paymentIdDetails);
    _payment.refund = refundPaymentFn(paymentIdDetails.id);
    _payment.fetchTransfers = fetchTransfersFn(paymentIdDetails.id);
    _payment.fetchInstantRefundFee = fetchInstantRefundFeeFn;
    trackDetailsClick({
      objectName: 'Issue Refund',
      properties: {
        latestTransactionStatus: _payment.status,
      },
    });

    openModal({
      component: (
        <RefundModal
          fetchMerchantBalance={fetchCurrentBalance}
          fetchRefundFee={fetchRefundFee}
          payment={_payment}
          onRefund={onRefundSuccess}
        />
      ),
      size: 'small',
    });
  };

  return (
    <Card elevation="none" testID="payment-details-timeline">
      <CardBody>
        {paymentTimelineData ? (
          <Box>
            <Box marginLeft="-8px">
              <Heading weight="bold" color="surface.text.normal.lowContrast" size="medium">
                Timeline
              </Heading>
            </Box>
            <TimeLine
              data={timelineData}
              bankTransfer={bankTransfer}
              paymentIdDetails={paymentIdDetails}
              fetchPaymentsTimelineData={fetchPaymentsTimelineDataFn}
              reFetchPageDetails={reFetchPageDetails}
            />
          </Box>
        ) : didPaymentTimelineDataError ? (
          <Box>
            <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
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
        {!isIssueRefundDisabled(paymentIdDetails, user) ? (
          <>
            <Divider dividerStyle="dashed" />
            <Box display="flex" flexDirection="row" padding="12px" marginLeft="-16px" width="100%">
              <Box marginTop="spacing.1">
                <IconBackground status="captured">
                  <img src={RefundIcon} alt="refund-icon" style={{ padding: '1px' }} />
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

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators(
    {
      ...PaymentActions,
    },
    dispatch,
  );

export default compose(
  connect(
    (state: any) => ({
      user: state.session.user,
    }),
    mapDispatchToProps,
  ),
)(PaymentDetailsTimeline);
