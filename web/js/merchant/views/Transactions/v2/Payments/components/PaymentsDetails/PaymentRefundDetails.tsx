import React from 'react';
import { Box, Button, Card, CardBody, Divider, Text, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { CurrencyCodeType } from '@razorpay/i18nify-js/currency';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import styled from 'styled-components';

import { withRouter } from 'common/deprecated/withRouter';
import { useSplitzService } from 'common/splitz';
import Amount from 'common/ui/Amount';
import { capitalize, deepClone } from 'common/utils/rzp-utils';
import * as PaymentActions from 'merchant/reducers/payments/details';
import {
  fetchInstantRefundFeeFn,
  fetchTransfersFn,
  refundPaymentFn,
} from 'merchant/views/Transactions/model';
import RefundModal from 'merchant/views/Transactions/v1/Payments/components/RefundModalNew';
import RefundModalRevamp from 'merchant/views/Transactions/v2/Payments/components/PaymentRefund';
import RefundMiniTimeline from 'merchant/views/Transactions/v2/Refunds/components/RefundMiniTimeline';
import { trackDetailsClick } from 'merchant/views/Transactions/v2/common/tracking';
import { isRefundRevampEnabled } from 'merchant/views/Transactions/v2/common/utils';
import * as ModalActions from 'merchant_common/reducers/modals';

import Tooltip from './Tooltip';
import { REFUND_ELIBILITY_TEXT } from './constants';
import {
  BoxContainer,
  CardWrapper,
  CopyWrapper,
  NotesKey,
  RowsWrapper,
  RowWrapper,
  SectionFooter,
  SectionHeader,
  StyledAmountWrapper,
} from './styled';
import {
  IPaymentDetails,
  IPaymentIdRefundDetail,
  ICurrentBalance,
  IPaymentIdRefundDetails,
} from './types';
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

import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import GatewayData from 'merchant/views/Transactions/v1/Refunds/components/GatewayData';
import { User } from 'common/typings';
import PaymentOptimizerDetails from './PaymentOptimizerDetails';

interface IPaymentRefundDetails {
  paymentDetails: IPaymentDetails | null;
  paymentIdRefundDetails: IPaymentIdRefundDetails;
  user: any;
  openModal: (params) => void;
  fetchCurrentBalance: () => Promise<ICurrentBalance>;
  fetchRefundFee: () => Promise<Record<string, string>>;
  reFetchPageDetails: (id: string) => void;
  match: RouteComponentProps<{ id: string }>['match'];
  orgName: string;
  orgFeatures: string[];
  terminalProviders: any;
  shouldShowOptimizerDetails: boolean;
  isIssueRefundHidden: boolean;
}
interface PaymentRefundContentType {
  enableBorderTopRadius?: boolean;
  enableBorderBottomRadius?: boolean;
  showFooter: boolean;
  refund: IPaymentIdRefundDetail;
  currency: CurrencyCodeType;
  transactionIDActual: string;
  orgName: string;
  user: User;
  orgFeatures: string[];
  terminalProviders: any;
  shouldShowOptimizerDetails: boolean;
}

function PaymentRefundDetails({
  paymentDetails,
  paymentIdRefundDetails,
  user,
  openModal,
  fetchCurrentBalance,
  fetchRefundFee,
  reFetchPageDetails,
  match: {
    params: { id: transactionIDActual },
  },
  orgName,
  orgFeatures,
  terminalProviders,
  shouldShowOptimizerDetails,
  isIssueRefundHidden,
}: IPaymentRefundDetails): React.ReactElement {
  const { currency, refund_status } = paymentDetails!;
  const hasFooter = refund_status !== null;
  const subsequentRefunds = paymentIdRefundDetails.slice(1);
  const splitz = useSplitzService();

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

    const isRefundModalRevampEnabled = isRefundRevampEnabled(splitz);
    const modalProps = {
      fetchMerchantBalance: fetchCurrentBalance,
      fetchRefundFee,
      payment: _payment,
      onRefund: onRefundSuccess,
    };
    const RefundModalComponent = isRefundModalRevampEnabled ? RefundModalRevamp : RefundModal;

    openModal({
      isNew: !!isRefundModalRevampEnabled,
      component: <RefundModalComponent {...modalProps} />,
      size: 'small',
    });
  };

  const getRefundInfoText = () => {
    if (!paymentDetails) return '';

    if (isPaymentEligibleForRefundAsPerStatus(paymentDetails) === false)
      return REFUND_ELIBILITY_TEXT.STATUS_ELIGIBLITY;
    else if (isPaymentEligibleForRefund(paymentDetails, user) === false)
      return REFUND_ELIBILITY_TEXT.PAYMENT_NOT_ELIGIBLE;
    else if (hasPaymentOpenNonFraudDisputes(paymentDetails) === true)
      return REFUND_ELIBILITY_TEXT.OPEN_DISPUTES;
    else if (isPaymentThroughSeamlessProviders(paymentDetails))
      return REFUND_ELIBILITY_TEXT.SEAMLESS_PROVIDERS;
    else if (isGatewaySupportingRefund(paymentDetails) === false) {
      return REFUND_ELIBILITY_TEXT.GATEWAY_REFUND_NOT_SUPPORTED;
    }

    return 'No refund issued for this payment';
  };

  return (
    <Box testID="payment-refund-details">
      {/* First card */}
      {paymentIdRefundDetails.length >= 0 && (
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
            {!isIssueRefundHidden ? (
              <Button
                size="small"
                variant="secondary"
                isDisabled={isIssueRefundDisabled(paymentDetails!, user)}
                onClick={openIssueRefundModal}
              >
                Issue refund
              </Button>
            ) : null}
          </SectionHeader>
          {paymentIdRefundDetails.length > 0 ? (
            <PaymentRefundContent
              enableBorderBottomRadius={!hasFooter}
              showFooter={hasFooter}
              refund={paymentIdRefundDetails[0]}
              currency={currency}
              transactionIDActual={transactionIDActual}
              orgName={orgName}
              orgFeatures={orgFeatures}
              user={user}
              terminalProviders={terminalProviders}
              shouldShowOptimizerDetails={shouldShowOptimizerDetails}
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
      )}
      {/* Subsequent cards */}
      {subsequentRefunds.length > 0 && (
        <>
          {subsequentRefunds.map((subRefund) => (
            <BoxContainer key={subRefund.id} disableMarginTop isRefund>
              <PaymentRefundContent
                enableBorderTopRadius
                enableBorderBottomRadius={!hasFooter}
                showFooter={hasFooter}
                refund={subRefund}
                currency={currency}
                transactionIDActual={transactionIDActual}
                orgName={orgName}
                orgFeatures={orgFeatures}
                user={user}
                terminalProviders={terminalProviders}
                shouldShowOptimizerDetails={shouldShowOptimizerDetails}
              />
            </BoxContainer>
          ))}
        </>
      )}
    </Box>
  );
}

const StyledRefundTimelineContainer = styled.div<{ marginTop: string }>`
  margin-top: ${({ marginTop }) => marginTop};
  margin-left: -20px;
`;

function PaymentRefundContent({
  enableBorderTopRadius,
  enableBorderBottomRadius,
  showFooter,
  refund,
  currency,
  transactionIDActual,
  orgName,
  orgFeatures,
  user,
  shouldShowOptimizerDetails,
  terminalProviders,
}: PaymentRefundContentType): JSX.Element {
  const bankCode = refund.acquirer_data?.rrn || refund.acquirer_data?.arn;

  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isDesktop = matchedBreakpoint === 'xl';
  const createdAt = useTime(refund.created_at).join(', ');
  const processedAt = useTime(refund.processed_at).join(', ');

  const getRefundSpeed = () => refund.speed[0].toUpperCase() + refund.speed.slice(1);

  const isLateAuthAttributeEnabled = orgFeatures?.includes('show_refnd_lateauth_param');

  const shouldShowGatewayResponse = user.isOptimizerView();

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
                  Refund ID <Tooltip size="small" type="refundId" />
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
                  ARN/RRN <Tooltip size="small" type="rrnARN" />
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
                  Currency
                </Text>
                <Text
                  variant="body"
                  size="medium"
                  weight="regular"
                  color="surface.text.gray.normal"
                >
                  {refund.currency ?? '--'}
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
              {refund.processed_at ? (
                <>
                  <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                  <RowWrapper>
                    <Text
                      variant="body"
                      size="medium"
                      weight="regular"
                      color="surface.text.gray.subtle"
                    >
                      Processed At
                    </Text>
                    <Text
                      variant="body"
                      size="medium"
                      weight="regular"
                      color="surface.text.gray.normal"
                    >
                      {processedAt}
                    </Text>
                  </RowWrapper>
                </>
              ) : null}
              {isLateAuthAttributeEnabled ? (
                <>
                  <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                  <RowWrapper>
                    <Text
                      variant="body"
                      size="medium"
                      weight="regular"
                      color="surface.text.gray.subtle"
                    >
                      Refund Type
                    </Text>
                    <Text
                      variant="body"
                      size="medium"
                      weight="regular"
                      color="surface.text.gray.normal"
                    >
                      {capitalize(refund.refund_type || '')}
                    </Text>
                  </RowWrapper>
                </>
              ) : null}
              {shouldShowGatewayResponse ? (
                <>
                  <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                  <RowWrapper>
                    <Text
                      variant="body"
                      size="medium"
                      weight="regular"
                      color="surface.text.gray.subtle"
                    >
                      Gateway Response
                    </Text>
                    <GatewayData
                      status={refund.status}
                      value={refund.gateway_data}
                      isTransactionV2={true}
                    />
                  </RowWrapper>
                </>
              ) : null}

              {shouldShowOptimizerDetails && !!refund?.optimizer_provider ? (
                <>
                  <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                  <RowWrapper>
                    <Text
                      variant="body"
                      size="medium"
                      weight="regular"
                      color="surface.text.gray.subtle"
                    >
                      Optimizer details
                    </Text>
                    <PaymentOptimizerDetails
                      payment={refund}
                      terminalProviders={terminalProviders}
                      page="Refund Detail"
                    />
                  </RowWrapper>
                </>
              ) : null}
              {/* {isTxnFeeBreakupEnabled && refund.fees && refund.tax ? (
                <>
                  <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                  <RowWrapper>
                    <Text
                      variant="body"
                      size="medium"
                      weight="regular"
                      color="surface.text.gray.subtle"
                    >
                      Total Fee
                    </Text>
                    <Definition>
                      <Amount value={refund.fees} />
                      <span>
                        Instant refund fee -{' '}
                        <Amount value={refund.fees - refund.tax} currency={refund.currency} />
                      </span>
                      <span>
                        GST - <Amount value={refund.tax} currency={refund.currency} />
                      </span>
                    </Definition>
                  </RowWrapper>
                </>
              ) : null} */}
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

              <Divider dividerStyle="solid" thickness="thick" variant="muted" />
              <RowWrapper>
                <Text
                  variant="body"
                  size="medium"
                  weight="regular"
                  color="surface.text.gray.subtle"
                >
                  Notes
                </Text>
                {Object.keys(refund.notes || {}).length > 0 ? (
                  <Box display="flex" flexDirection="column" gap="spacing.1">
                    {Object.keys(refund.notes).map((key) => (
                      <Text
                        variant="body"
                        size="medium"
                        weight="regular"
                        key={`${key}`}
                        color="surface.text.gray.normal"
                      >
                        <NotesKey>{key} :</NotesKey> {String(refund.notes?.[key] || '--')}
                      </Text>
                    ))}
                  </Box>
                ) : (
                  '--'
                )}
              </RowWrapper>
            </RowsWrapper>
          </CardBody>
        </Card>
      </CardWrapper>
      {/* Show only if payment is intiated */}
      {showFooter && (
        <SectionFooter>
          <Text variant="body" size="small" weight="regular" color="surface.text.gray.subtle">
            *Refund amount is deducted from your {orgName} current balance after getting processed
          </Text>
        </SectionFooter>
      )}
    </Box>
  );
}

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    orgName: state.session.org?.business_name,
    orgFeatures: state.session.org?.features,
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      ...PaymentActions,
    },
    dispatch,
  );

export default compose<any>(
  withRouter,
  connect(mapStateToProps, mapDispatchToProps),
)(PaymentRefundDetails);
