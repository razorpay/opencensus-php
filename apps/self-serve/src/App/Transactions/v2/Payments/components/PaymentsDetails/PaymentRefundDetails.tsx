// TODO: Fix the imports, currently out of scope
// @ts-nocheck
import {
  Box,
  Button,
  Card,
  CardBody,
  Divider,
  Heading,
  Text,
  useTheme,
} from '@razorpay/blade/components';
import { AnyAction, Dispatch, bindActionCreators, compose } from 'redux';
import { connect } from 'react-redux';
import React from 'react';
import {
  fetchInstantRefundFeeFn,
  fetchTransfersFn,
  refundPaymentFn,
} from 'apps/self-serve/src/App/Transactions/model';
import RefundModal from 'apps/self-serve/src/App/Transactions/v1/Payments/components/RefundModalNew';
import { deepClone } from '@dashboard/shared-utils/rzp-utils';
import * as PaymentActions from 'merchant/reducers/payments/details';
import Amount from 'common/ui/Amount';
import { withRouter } from 'shell/deprecated/withRouter';
import styled from 'styled-components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { useStore } from 'shell/commonStore';
import Tooltip from './Tooltip';
import {
  isIssueRefundDisabled,
  isPaymentEligibleForRefundAsPerStatus,
  isPaymentEligibleForRefund,
  hasPaymentOpenNonFraudDisputes,
  isGatewaySupportingRefund,
  isPaymentThroughSeamlessProviders,
  onCopy,
  getTime as useTime,
} from './utils';
import {
  IPaymentDetails,
  IPaymentIdRefundDetail,
  ICurrentBalance,
  IPaymentIdRefundDetails,
} from './types';
import {
  BoxContainer,
  CardWrapper,
  CopyWrapper,
  RowsWrapper,
  RowWrapper,
  SectionFooter,
  SectionHeader,
  StyledAmountWrapper,
} from './styled';
import { REFUND_ELIBILITY_TEXT } from './constants';
import type { RouteComponentProps } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';
import { trackDetailsClick } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import RefundMiniTimeline from 'apps/self-serve/src/App/Transactions/v2/Refunds/components/RefundMiniTimeline';
import { Currency } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';

const StyledRefundTimelineContainer = styled.div<{ marginTop: string }>`
  margin-top: ${({ marginTop }: { marginTop: string }) => marginTop};
  margin-left: -20px;
`;

interface IPaymentRefundDetails {
  paymentDetails: IPaymentDetails | null;
  paymentIdRefundDetails: IPaymentIdRefundDetails;
  user: any;
  fetchCurrentBalance: () => Promise<ICurrentBalance>;
  fetchRefundFee: () => Promise<Record<string, string>>;
  reFetchPageDetails: (id: string) => void;
  match: RouteComponentProps<{ id: string }>['match'];
}
interface PaymentRefundContentType {
  enableBorderTopRadius?: boolean;
  enableBorderBottomRadius?: boolean;
  showFooter: boolean;
  refund: IPaymentIdRefundDetail;
  acquirerData: IPaymentDetails['acquirer_data'];
  currency: Currency;
  transactionIDActual: string;
}

const PaymentRefundContent = ({
  enableBorderTopRadius,
  enableBorderBottomRadius,
  showFooter,
  refund,
  acquirerData,
  currency,
  transactionIDActual,
}: PaymentRefundContentType): JSX.Element => {
  const bankCode = acquirerData.rrn || acquirerData.arn;

  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isDesktop = matchedBreakpoint === 'xl';
  const createdAt = useTime(refund.created_at).join(', ');

  const getRefundSpeed = () => refund.speed[0].toUpperCase() + refund.speed.slice(1);

  return (
    <Box testID={`payment-refunded-${refund.id}`}>
      <CardWrapper
        enableBorderTopRadius={enableBorderTopRadius}
        enableBorderBottomRadius={enableBorderBottomRadius}
      >
        <Card padding="spacing.5" elevation="none">
          <CardBody>
            <RowsWrapper>
              <RowWrapper>
                <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                  Refund ID <Tooltip type="refundId" size="small" />
                </Text>
                <CopyWrapper
                  onClick={onCopy('Refund ID', { transactionIDActual, refundId: refund.id }).bind(
                    null,
                    refund.id,
                  )}
                >
                  <Text type="normal" variant="body" size="medium" weight="bold" contrast="low">
                    {refund.id}
                  </Text>
                </CopyWrapper>
              </RowWrapper>
              <Divider contrast="low" dividerStyle="solid" thickness="thick" variant="normal" />
              <RowWrapper>
                <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                  ARN/RRN <Tooltip type="rrnARN" size="small" />
                </Text>
                {bankCode ? (
                  <CopyWrapper
                    onClick={onCopy('ARN/RRN', { transactionIDActual, rrnNumber: bankCode }).bind(
                      null,
                      bankCode,
                    )}
                  >
                    <Text
                      type="normal"
                      variant="body"
                      size="medium"
                      weight="regular"
                      contrast="low"
                    >
                      {bankCode}
                    </Text>
                  </CopyWrapper>
                ) : (
                  <Text type="normal" variant="body" size="medium" weight="regular" contrast="low">
                    --
                  </Text>
                )}
              </RowWrapper>
              <Divider contrast="low" dividerStyle="solid" thickness="thick" variant="normal" />
              <RowWrapper>
                <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                  Amount
                </Text>
                <StyledAmountWrapper data-testid="amount" type="regular" fontSize="14">
                  <Amount value={refund.amount} currency={currency} />
                </StyledAmountWrapper>
              </RowWrapper>
              <Divider contrast="low" dividerStyle="solid" thickness="thick" variant="normal" />
              <RowWrapper>
                <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                  Refund speed
                </Text>
                <Text type="normal" variant="body" size="medium" weight="regular" contrast="low">
                  {getRefundSpeed()}
                </Text>
              </RowWrapper>
              <Divider contrast="low" dividerStyle="solid" thickness="thick" variant="normal" />
              <RowWrapper>
                <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                  Issued on
                </Text>
                <Text type="normal" variant="body" size="medium" weight="regular" contrast="low">
                  {createdAt}
                </Text>
              </RowWrapper>
              <Divider contrast="low" dividerStyle="solid" thickness="thick" variant="normal" />
              <RowWrapper>
                <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                  Timeline
                </Text>
                <StyledRefundTimelineContainer marginTop={isDesktop ? '-15px' : '0px'}>
                  <RefundMiniTimeline refund={refund} />
                </StyledRefundTimelineContainer>
              </RowWrapper>
            </RowsWrapper>
          </CardBody>
        </Card>
      </CardWrapper>
      {/* Show only if payment is intiated */}
      {showFooter ? (
        <SectionFooter>
          <Text type="subtle" variant="body" size="small" weight="regular" contrast="low">
            *Refund amount is deducted from your Razorpay current balance after getting processed
          </Text>
        </SectionFooter>
      ) : null}
    </Box>
  );
};

function PaymentRefundDetails({
  paymentDetails,
  paymentIdRefundDetails,
  user,
  fetchCurrentBalance,
  fetchRefundFee,
  reFetchPageDetails,
  match: {
    params: { id: transactionIDActual },
  },
}: IPaymentRefundDetails): React.ReactElement {
  const openModal = useStore((state) => state.openModal);
  const { currency, acquirer_data = {}, refund_status } = paymentDetails!;
  const hasFooter = refund_status !== null;
  const subsequentRefunds = paymentIdRefundDetails.slice(1);

  const onRefundSuccess = () => {
    reFetchPageDetails(paymentDetails!.id);
  };

  const openIssueRefundModal = () => {
    const _payment = deepClone(paymentDetails);
    _payment.refund = refundPaymentFn(paymentDetails!.id);
    _payment.fetchTransfers = fetchTransfersFn(paymentDetails!.id);
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

  const getRefundInfoText = () => {
    if (!paymentDetails) return '';

    if (!isPaymentEligibleForRefundAsPerStatus(paymentDetails))
      return REFUND_ELIBILITY_TEXT.STATUS_ELIGIBLITY;
    else if (!isPaymentEligibleForRefund(paymentDetails, user))
      return REFUND_ELIBILITY_TEXT.PAYMENT_NOT_ELIGIBLE;
    else if (hasPaymentOpenNonFraudDisputes(paymentDetails))
      return REFUND_ELIBILITY_TEXT.OPEN_DISPUTES;
    else if (isPaymentThroughSeamlessProviders(paymentDetails))
      return REFUND_ELIBILITY_TEXT.SEAMLESS_PROVIDERS;
    else if (!isGatewaySupportingRefund(paymentDetails)) {
      return REFUND_ELIBILITY_TEXT.GATEWAY_REFUND_NOT_SUPPORTED;
    }

    return 'No refund issued for this payment';
  };

  return (
    <Box testID="payment-refund-details">
      {/* First card */}
      {paymentIdRefundDetails.length >= 0 ? (
        <Box>
          <SectionHeader>
            <Heading type="normal" size="small" weight="bold" contrast="low">
              Refund
            </Heading>
            <Button
              size="small"
              variant="secondary"
              isDisabled={isIssueRefundDisabled(paymentDetails!, user)}
              onClick={openIssueRefundModal}
            >
              Issue refund
            </Button>
          </SectionHeader>
          {paymentIdRefundDetails.length > 0 ? (
            <PaymentRefundContent
              enableBorderBottomRadius={!hasFooter}
              showFooter={hasFooter}
              refund={paymentIdRefundDetails[0]}
              currency={currency}
              acquirerData={acquirer_data}
              transactionIDActual={transactionIDActual}
            />
          ) : (
            <CardWrapper enableBorderBottomRadius>
              <Card padding="spacing.5">
                <CardBody>
                  <Text type="subtle" variant="body" size="medium" weight="regular" contrast="low">
                    {getRefundInfoText()}
                  </Text>
                </CardBody>
              </Card>
            </CardWrapper>
          )}
        </Box>
      ) : null}
      {/* Subsequent cards */}
      {subsequentRefunds.length > 0 ? (
        <>
          {subsequentRefunds.map((subRefund) => (
            <BoxContainer key={subRefund.id} disableMarginTop isRefund>
              <PaymentRefundContent
                enableBorderTopRadius
                enableBorderBottomRadius={!hasFooter}
                showFooter={hasFooter}
                refund={subRefund}
                currency={currency}
                acquirerData={acquirer_data}
                transactionIDActual={transactionIDActual}
              />
            </BoxContainer>
          ))}
        </>
      ) : null}
    </Box>
  );
}

const mapStateToProps = (state: any) => {
  return {
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators(
    {
      ...PaymentActions,
    },
    dispatch,
  );

export default compose<any>(
  withRouter,
  connect(mapStateToProps, mapDispatchToProps),
)(PaymentRefundDetails);
