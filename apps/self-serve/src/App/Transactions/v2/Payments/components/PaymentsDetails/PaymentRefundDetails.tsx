// TODO: Fix the imports, currently out of scope
// @ts-nocheck
import { deepClone } from '@dashboard/shared-utils/rzp-utils';
import { Box, Button, Card, CardBody, Divider, Text, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import {
  fetchInstantRefundFeeFn,
  fetchTransfersFn,
  refundPaymentFn,
} from 'apps/self-serve/src/App/Transactions/model';
import RefundModal from 'apps/self-serve/src/App/Transactions/v1/Payments/components/RefundModalNew';
import type { RouteComponentProps } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';
import { Currency } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';
import RefundMiniTimeline from 'apps/self-serve/src/App/Transactions/v2/Refunds/components/RefundMiniTimeline';
import { trackDetailsClick } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import Amount from 'common/ui/Amount';
import * as PaymentActions from 'merchant/reducers/payments/details';
import React from 'react';
import { connect } from 'react-redux';
import { AnyAction, Dispatch, bindActionCreators, compose } from 'redux';
import { useStore } from 'shell/commonStore';
import { withRouter } from 'shell/deprecated/withRouter';
import styled from 'styled-components';
import Tooltip from './Tooltip';
import { REFUND_ELIBILITY_TEXT } from './constants';
import {
  BoxContainer,
  CardWrapper,
  CopyWrapper,
  RowWrapper,
  RowsWrapper,
  SectionFooter,
  SectionHeader,
  StyledAmountWrapper,
} from './styled';
import {
  ICurrentBalance,
  IPaymentDetails,
  IPaymentIdRefundDetail,
  IPaymentIdRefundDetails,
} from './types';
import {
  hasPaymentOpenNonFraudDisputes,
  isGatewaySupportingRefund,
  isIssueRefundDisabled,
  isPaymentEligibleForRefund,
  isPaymentEligibleForRefundAsPerStatus,
  isPaymentThroughSeamlessProviders,
  onCopy,
  getTime as useTime,
} from './utils';

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
                <Text
                  variant="body"
                  size="medium"
                  weight="regular"
                  color="surface.text.gray.subtle"
                >
                  Refund ID <Tooltip size="small" />
                </Text>
                <CopyWrapper
                  onClick={onCopy('Refund ID', { transactionIDActual, refundId: refund.id }).bind(
                    null,
                    refund.id,
                  )}
                >
                  <Text
                    variant="body"
                    size="medium"
                    weight="semibold"
                    color="surface.text.gray.normal"
                  >
                    {refund.id}
                  </Text>
                </CopyWrapper>
              </RowWrapper>
              <Divider dividerStyle="solid" thickness="thick" variant="muted" />
              <RowWrapper>
                <Text
                  variant="body"
                  size="medium"
                  weight="regular"
                  color="surface.text.gray.subtle"
                >
                  ARN/RRN <Tooltip size="small" />
                </Text>
                {bankCode ? (
                  <CopyWrapper
                    onClick={onCopy('ARN/RRN', { transactionIDActual, rrnNumber: bankCode }).bind(
                      null,
                      bankCode,
                    )}
                  >
                    <Text
                      variant="body"
                      size="medium"
                      weight="regular"
                      color="surface.text.gray.normal"
                    >
                      {bankCode}
                    </Text>
                  </CopyWrapper>
                ) : (
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.normal"
                  >
                    --
                  </Text>
                )}
              </RowWrapper>
              <Divider dividerStyle="solid" thickness="thick" variant="muted" />
              <RowWrapper>
                <Text
                  variant="body"
                  size="medium"
                  weight="regular"
                  color="surface.text.gray.subtle"
                >
                  Amount
                </Text>
                <StyledAmountWrapper data-testid="amount" type="regular" fontSize="14">
                  <Amount value={refund.amount} currency={currency} />
                </StyledAmountWrapper>
              </RowWrapper>
              <Divider dividerStyle="solid" thickness="thick" variant="muted" />
              <RowWrapper>
                <Text
                  variant="body"
                  size="medium"
                  weight="regular"
                  color="surface.text.gray.subtle"
                >
                  Refund speed
                </Text>
                <Text
                  variant="body"
                  size="medium"
                  weight="regular"
                  color="surface.text.gray.normal"
                >
                  {getRefundSpeed()}
                </Text>
              </RowWrapper>
              <Divider dividerStyle="solid" thickness="thick" variant="muted" />
              <RowWrapper>
                <Text
                  variant="body"
                  size="medium"
                  weight="regular"
                  color="surface.text.gray.subtle"
                >
                  Issued on
                </Text>
                <Text
                  variant="body"
                  size="medium"
                  weight="regular"
                  color="surface.text.gray.normal"
                >
                  {createdAt}
                </Text>
              </RowWrapper>
              <Divider dividerStyle="solid" thickness="thick" variant="muted" />
              <RowWrapper>
                <Text
                  variant="body"
                  size="medium"
                  weight="regular"
                  color="surface.text.gray.subtle"
                >
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
          <Text variant="body" size="small" weight="regular" color="surface.text.gray.subtle">
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
            <Text
              testID="refund-heading"
              weight="semibold"
              size="large"
              color="surface.text.gray.normal"
            >
              Refund
            </Text>
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
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
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
