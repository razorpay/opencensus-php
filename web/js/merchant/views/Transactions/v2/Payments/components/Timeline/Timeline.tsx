import React, { useState, useEffect } from 'react';
import {
  Box,
  Text,
  Link,
  Button,
  Collapsible,
  CollapsibleLink,
  CollapsibleBody,
  useTheme,
  ChevronRightIcon,
} from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import User from 'common/typings/User';
import Amount from 'common/ui/Amount';
import { merchantFetch } from 'merchant/utils/ajax';
import { ERROR_DESCRIPTION_CONTENT_MAP } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/constants';
import {
  IPaymentDetails,
  IBankTransfer,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/types';
import { shouldShowCapturePaymentButton } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/utils';
import TransactionsTimeline from 'merchant/views/Transactions/v2/Payments/components/TransactionTimeline';
import RefundMiniTimeline from 'merchant/views/Transactions/v2/Refunds/components/RefundMiniTimeline';
import { trackDetailsClick } from 'merchant/views/Transactions/v2/common/tracking';
import { showNotification } from 'merchant_common/reducers/notifications';

import {
  StyledJourneyMetadata,
  StyledTimelineContainer,
  StyledText,
  StyledJourneyStatus,
  StyledGradientBox,
  StyledRefundTimelineWrapper,
  IconBackground,
  StyledStatusSubText,
  getStatusIcon,
} from './styled';
import { TimelineJourneyPoint, SkipTimelineTransactions } from './types';
import { getHumanReadableTimestamp } from './utils';

import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { POS_TRANSACTION_CHANNEL } from 'merchant/views/Transactions/constants';

interface EntityStatusTimelineProps extends RouteComponentProps {
  data: TimelineJourneyPoint[];
  bankTransfer: IBankTransfer;
  paymentIdDetails: IPaymentDetails;
  user: User;
  fetchPaymentsTimelineData: () => Promise<void>;
  reFetchPageDetails: (id: string) => void;
  showNotification: (args: any) => void;
  skipTransactionTimeline: SkipTimelineTransactions;
  shouldShowRetryTimeline: boolean;
  didRetryTimelineDataError: boolean;
}
const EntityStatusTimeline = ({
  data,
  bankTransfer,
  history,
  paymentIdDetails,
  user,
  fetchPaymentsTimelineData,
  reFetchPageDetails,
  showNotification,
  skipTransactionTimeline,
  shouldShowRetryTimeline,
  didRetryTimelineDataError,
}: EntityStatusTimelineProps): JSX.Element => {
  const [timelineData, setTimelineData] = useState<TimelineJourneyPoint[]>([]);
  const [isTimelineCollapsed, setisTimelineCollapsed] = useState<boolean>(true);
  const [isCapturePaymentLoading, setisCapturePaymentLoading] = useState<boolean>(false);
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedDeviceType === 'mobile';

  const openAndShowTimeline = (item): void => {
    if (item.id === 0) {
      setisTimelineCollapsed(!isTimelineCollapsed);
      const objectName = !isTimelineCollapsed ? 'Show Timeline' : 'Collapse Timeline';
      const refundStatus = timelineData.find(({ entity }) => entity === 'Refund')?.status;
      trackDetailsClick({ objectName, properties: { refundStatus } });
    }
  };

  useEffect(() => {
    setTimelineData(data);
  }, [data]);

  useEffect(() => {
    let mobileViewTimeline: TimelineJourneyPoint[] = [];

    if (isMobile && data.length > 2) {
      mobileViewTimeline.push({
        id: 0,
        entity: null,
        status: isTimelineCollapsed ? 'show' : 'hide',
        title: isTimelineCollapsed ? 'Show timeline' : 'Collapse timeline',
        timestamp: null,
        metadata: {},
      });
      if (isTimelineCollapsed) {
        // showing only last 2 journey's in the timeline
        mobileViewTimeline.push(...data.slice(data.length - 2));
      } else {
        // showing entire timeline
        mobileViewTimeline = [...mobileViewTimeline, ...data];
      }

      setTimelineData(mobileViewTimeline);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isTimelineCollapsed, isMobile, data]);

  /**
   *
   * source: web/js/merchant/models/Payment.js
   * function: didDeserialize
   */
  const getCapturableAmount = () => {
    let amount = paymentIdDetails.amount;
    if (paymentIdDetails.fee_bearer == 'customer') {
      amount = amount - paymentIdDetails.fee;
    }
    return amount;
  };

  const reFetchOnSuccess = () => {
    reFetchPageDetails(paymentIdDetails.id);
    fetchPaymentsTimelineData();
  };

  const onPaymentCaptureClick = async () => {
    const { id, currency, status } = paymentIdDetails;
    const url = `payments/${id}/capture`;
    const data = {
      amount: getCapturableAmount(), // get capturable amount
      currency,
    };
    trackDetailsClick({
      objectName: 'Capture Payment',
      properties: {
        latestTransactionStatus: status,
      },
    });
    setisCapturePaymentLoading(true);
    try {
      await merchantFetch({
        url,
        method: 'POST',
        data,
      });
      reFetchOnSuccess();
    } catch (error: any) {
      showNotification({
        type: 'error',
        message: error?.errors?.join(' '),
      });
    } finally {
      setisCapturePaymentLoading(false);
    }
  };

  const getPaymentsJourneyMeta = (journeyPoint: TimelineJourneyPoint): JSX.Element => {
    const paymentSourceChannel = journeyPoint?.metadata?.payment?.source_channel;
    const isCapturePaymentDisabled = paymentSourceChannel === POS_TRANSACTION_CHANNEL;

    return (
      <StyledJourneyMetadata>
        {journeyPoint.status === 'not-authorized' && (
          <Text size="small" color="surface.text.gray.subtle" weight="regular">
            Amount yet to be authenticated by the bank
          </Text>
        )}
        {journeyPoint.status === 'not-captured' && (
          <Box marginBottom="spacing.3">
            <Text size="small" color="surface.text.gray.subtle" weight="regular">
              Amount yet to be manually captured
            </Text>
            {shouldShowCapturePaymentButton(user, journeyPoint, bankTransfer) ? (
              <Box paddingTop="spacing.4">
                <Button
                  size="medium"
                  type="button"
                  variant="primary"
                  onClick={onPaymentCaptureClick}
                  isLoading={isCapturePaymentLoading}
                  isDisabled={isCapturePaymentDisabled}
                >
                  Capture payment
                </Button>
              </Box>
            ) : null}
          </Box>
        )}
        {journeyPoint.timestamp && (
          <Text size="small" color="surface.text.gray.subtle" weight="regular">
            {getHumanReadableTimestamp(journeyPoint.timestamp)}
          </Text>
        )}
        {journeyPoint.status === 'failed' ? (
          <>
            {journeyPoint.metadata?.failureReason ? (
              <StyledGradientBox>
                <Text size="small" color="surface.text.gray.subtle" weight="regular">
                  {ERROR_DESCRIPTION_CONTENT_MAP[journeyPoint.metadata.failureReason]
                    ? ERROR_DESCRIPTION_CONTENT_MAP[journeyPoint.metadata.failureReason]
                    : journeyPoint.metadata.failureReason}
                </Text>
              </StyledGradientBox>
            ) : null}
            <Box paddingTop="spacing.2" paddingBottom="spacing.2">
              <Text size="small" color="surface.text.gray.subtle" weight="regular">
                If the amount was deducted from the customer’s bank account, it will be credited to
                them within 5-7 working days
              </Text>
            </Box>
          </>
        ) : null}
        {journeyPoint.status === 'auth-failed' ? (
          <StyledGradientBox>
            <Text size="small" color="surface.text.gray.subtle" weight="regular">
              This payment will be refunded within 72 hours
            </Text>
          </StyledGradientBox>
        ) : null}
      </StyledJourneyMetadata>
    );
  };

  const getSettlementJourneyMeta = (journeyPoint: TimelineJourneyPoint): JSX.Element => {
    return (
      <StyledJourneyMetadata>
        <Box paddingBottom="spacing.2" display="flex" gap="spacing.2" flexDirection="column">
          {journeyPoint.id ? (
            <Text size="small" color="surface.text.gray.subtle" weight="regular">
              ID: {journeyPoint.id}
            </Text>
          ) : null}
          {journeyPoint.utr ? (
            <Text size="small" color="surface.text.gray.subtle" weight="regular">
              UTR number: {journeyPoint.utr}
            </Text>
          ) : null}
          <Text size="small" color="surface.text.gray.subtle" weight="regular">
            Net amount:{' '}
            <Amount
              value={journeyPoint.metadata.amount}
              currency={(user as Record<string, any>).merchant.currency}
            />
          </Text>
        </Box>
        {journeyPoint.timestamp && (
          <Text size="small" color="surface.text.gray.subtle" weight="regular">
            {getHumanReadableTimestamp(journeyPoint.timestamp, true)}
          </Text>
        )}

        <Box paddingTop="spacing.3" marginBottom="spacing.8">
          <Link
            iconPosition="right"
            icon={ChevronRightIcon}
            variant="button"
            onClick={() => {
              history.push(`/settlements/${journeyPoint.metadata.settlementId}`);
              trackDetailsClick({
                objectName: 'View Settlement Details',
                properties: {
                  settlementStatus: journeyPoint.status,
                },
              });
            }}
          >
            View details
          </Link>
        </Box>
      </StyledJourneyMetadata>
    );
  };

  const getRefundsJourneyMeta = (journeyPoint: TimelineJourneyPoint): JSX.Element => {
    const refund = journeyPoint.metadata.refund;
    const amount = refund?.amount;
    const currency = refund?.currency;

    return (
      <StyledJourneyMetadata>
        {amount && (
          <Box paddingBottom="spacing.2">
            <Text size="small" color="surface.text.gray.subtle" weight="regular">
              Amount: <Amount value={amount} currency={currency} />
            </Text>
          </Box>
        )}
        {refund?.created_at && (
          <Text size="small" color="surface.text.gray.subtle" weight="regular">
            Issued on {getHumanReadableTimestamp(refund?.created_at)}
          </Text>
        )}
        {journeyPoint.metadata.failureReason && (
          <StyledGradientBox>
            <Text size="small" color="surface.text.gray.subtle" weight="regular">
              {journeyPoint.metadata.failureReason}
            </Text>
          </StyledGradientBox>
        )}
        <Box testID="collapsible-refunds-timeline" paddingTop="spacing.3" marginBottom="spacing.2">
          <Collapsible
            direction="bottom"
            onExpandChange={() => {
              trackDetailsClick({
                objectName: 'View Refund Timeline',
                properties: { refundStatus: journeyPoint.status },
              });
            }}
          >
            <CollapsibleLink>View timeline</CollapsibleLink>
            <CollapsibleBody>
              <StyledRefundTimelineWrapper>
                <RefundMiniTimeline refund={journeyPoint.metadata.refund} />
              </StyledRefundTimelineWrapper>
            </CollapsibleBody>
          </Collapsible>
        </Box>
      </StyledJourneyMetadata>
    );
  };

  const getDisputesJourneyMeta = (journeyPoint: TimelineJourneyPoint): JSX.Element => {
    return (
      <StyledJourneyMetadata>
        {journeyPoint.timestamp && (
          <Text size="small" color="surface.text.gray.subtle" weight="regular">
            Created on {getHumanReadableTimestamp(journeyPoint.timestamp)}
          </Text>
        )}
        {journeyPoint.metadata?.amount && journeyPoint.metadata?.currency ? (
          <Text size="small" color="surface.text.gray.subtle" weight="regular">
            Dispute Amount :&nbsp;
            <Amount
              value={journeyPoint.metadata.amount}
              currency={journeyPoint.metadata.currency}
            />
          </Text>
        ) : null}
        <Box paddingTop="spacing.3" marginBottom="spacing.8">
          <Link
            iconPosition="right"
            icon={ChevronRightIcon}
            variant="button"
            onClick={() => {
              trackDetailsClick({
                objectName: 'View Dispute Details',
                properties: {
                  disputeStatus: journeyPoint.status,
                },
              });
              history.push(`/disputes/${journeyPoint.metadata.disputeId}`);
            }}
          >
            View details
          </Link>
        </Box>
      </StyledJourneyMetadata>
    );
  };

  const renderRetryTimeLine = () => {
    if (!skipTransactionTimeline) return null;
    return (
      <Box display="flex" flexDirection="column" position="relative" marginTop="30px">
        <Box position="absolute" top="-25px" left="-9px">
          <IconBackground status="not-captured">{getStatusIcon('not-captured')}</IconBackground>
        </Box>
        <StyledJourneyStatus>
          <StyledText>Settlement</StyledText>
          <StyledStatusSubText>(To be processed)</StyledStatusSubText>
        </StyledJourneyStatus>
        <StyledJourneyMetadata>
          {skipTransactionTimeline?.eligible_at ? (
            <Text size="small" color="surface.text.gray.subtle" weight="regular">
              To be deposited by: {getHumanReadableTimestamp(skipTransactionTimeline.eligible_at)}
            </Text>
          ) : null}
          <TransactionsTimeline skips={skipTransactionTimeline?.skips} />
        </StyledJourneyMetadata>
      </Box>
    );
  };

  const renderTimelineJourneyMeta = (journeyPoint: TimelineJourneyPoint): JSX.Element => {
    switch (journeyPoint.entity) {
      case 'Payment':
        return getPaymentsJourneyMeta(journeyPoint);
      case 'Settlement':
        return getSettlementJourneyMeta(journeyPoint);
      case 'Refund':
        return getRefundsJourneyMeta(journeyPoint);
      case 'Dispute':
        return getDisputesJourneyMeta(journeyPoint);
      default:
        return <StyledJourneyMetadata />;
    }
  };
  return (
    <Box
      display="flex"
      alignItems="flex-start"
      gap="spacing.4"
      padding="spacing.7"
      paddingBottom="spacing.0"
      marginLeft="-24px"
      testID="timeline"
    >
      <StyledTimelineContainer>
        <Box display="flex" flexDirection="column">
          {timelineData.map((each, index) => (
            <Box
              display="flex"
              flexDirection="column"
              position="relative"
              key={`${each.id}_${index}`}
              marginTop="30px"
            >
              <Box position="absolute" top="-25px" left="-9px">
                <IconBackground status={each.status} onClick={openAndShowTimeline.bind(null, each)}>
                  {getStatusIcon(each.status)}
                </IconBackground>
              </Box>
              <StyledJourneyStatus onClick={openAndShowTimeline.bind(null, each)}>
                <StyledText>{each.title}</StyledText>
                {each.metadata?.statusInfo ? (
                  <StyledStatusSubText>({each.metadata.statusInfo})</StyledStatusSubText>
                ) : null}
              </StyledJourneyStatus>
              {renderTimelineJourneyMeta(each)}
            </Box>
          ))}
          {shouldShowRetryTimeline && !didRetryTimelineDataError ? renderRetryTimeLine() : null}
        </Box>
      </StyledTimelineContainer>
    </Box>
  );
};

const mapStateToProps = (state) => ({ user: state.session.user });

export default compose<any>(
  withRouter,
  connect(mapStateToProps, (dispatch) => {
    return bindActionCreators(
      {
        showNotification,
      },
      dispatch,
    );
  }),
)(EntityStatusTimeline);
