import React from 'react';
import {
  Alert,
  Amount,
  Box,
  Checkbox,
  Divider,
  Link,
  Popover,
  PopoverInteractiveWrapper,
  Text,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { useI18Service } from 'common/i18';

import { RefundInfo, renderHelperAlerts, renderInstantFeeDetails } from './InstantRefundInfo';
import { trackInstantRefundCheckbox } from './analytics';
import { HANDLING_REFUNDS_ERRORS_DOC_URL, REVERSALS_DOCS_URL } from './constants';
import { getMonthsFromDays, PaymentUtils } from './utils';

const InstantRefund = ({
  payment,
  instantFee,
  isInstantRefundChecked,
  user,
  payableAmount,
  onInstantRefundClick,
  reversal,
  currentBalance,
  transfers,
  setReversal,
  org,
  hasEnoughFunds,
}): JSX.Element => {
  const {
    instant_refund_support: instantRefundSupport,
    gateway_refund_support: gatewayRefundSupport,
    payment_age_limit_for_gateway_refund: paymentAgeLimitForGatewayRefund,
  } = payment;

  const paymentUtils = new PaymentUtils(payment);
  const { nonFraudDisputeCount: getNonFraudDisputeCount } = paymentUtils;
  const i18 = useI18Service();

  const isInstantRefundCheckDisabled = !hasEnoughFunds || !instantRefundSupport;
  const nonFraudDisputeCount = getNonFraudDisputeCount();
  const showRefundInfo =
    isInstantRefundChecked && !isInstantRefundCheckDisabled && !currentBalance.loading;

  const handleInstantRefundCheckboxClick = (e) => {
    trackInstantRefundCheckbox(payment, e.isChecked);
    onInstantRefundClick(e.isChecked);
  };
  const handleReversalCheckboxClick = (e) => {
    setReversal(e.isChecked);
  };

  return (
    <Box marginTop="spacing.6">
      <Box display="flex" justifyContent="space-between" flexDirection="row" alignItems="center">
        <Checkbox
          size="medium"
          isChecked={Boolean(isInstantRefundChecked && !isInstantRefundCheckDisabled)}
          isDisabled={isInstantRefundCheckDisabled}
          onChange={handleInstantRefundCheckboxClick}
        >
          Refund instantly
        </Checkbox>
        {renderInstantFeeDetails({
          isInstantRefundChecked,
          isInstantRefundCheckDisabled,
          instantFee,
          payment,
        })}
      </Box>
      {transfers.items.length > 0 ? (
        <Box
          marginTop="spacing.6"
          display="flex"
          justifyContent="space-between"
          flexDirection="row"
          alignItems="center"
        >
          <Checkbox size="medium" isChecked={reversal} onChange={handleReversalCheckboxClick}>
            Reverse all
            <Box as="span" marginLeft="spacing.2" marginRight="spacing.2">
              <a href={REVERSALS_DOCS_URL} target="_blank" rel="noopener noreferrer">
                <Link size="small"> Route Transfers</Link>
              </a>
            </Box>
            as well
          </Checkbox>
        </Box>
      ) : null}

      <Box marginTop="spacing.6">
        {nonFraudDisputeCount ? (
          <Alert
            marginTop="spacing.6"
            marginBottom="spacing.6"
            color="negative"
            emphasis="subtle"
            description={
              <Text color="surface.text.gray.subtle" size="small">
                There {nonFraudDisputeCount > 1 ? 'are' : 'is'} dispute
                {nonFraudDisputeCount > 1 && 's'} raised against this payment. Kindly check the
                dispute details before initiating a refund.
              </Text>
            }
            isDismissible={false}
            isFullWidth
          />
        ) : null}
        {!gatewayRefundSupport ? (
          <Alert
            marginTop="spacing.6"
            marginBottom="spacing.6"
            color="notice"
            emphasis="subtle"
            description={
              <Box display="flex" flexDirection="column" alignItems="flex-start">
                <Text color="surface.text.gray.subtle" size="small">
                  This payment was made more than{' '}
                  {getMonthsFromDays(paymentAgeLimitForGatewayRefund)} months ago,
                  {instantRefundSupport
                    ? ' you can only issue instant refund'
                    : ' refund not supported'}
                  .
                </Text>
                <Box marginTop="spacing.6">
                  <a
                    href={HANDLING_REFUNDS_ERRORS_DOC_URL}
                    rel="noopener noreferrer"
                    target="_blank"
                  >
                    <Link size="small">Learn more</Link>
                  </a>
                </Box>
              </Box>
            }
            isDismissible={false}
            isFullWidth
          />
        ) : null}

        {renderHelperAlerts({
          currentBalance,
          isInstantRefundCheckDisabled,
          user,
          i18,
          instantRefundSupport,
          hasEnoughFunds,
        })}

        {showRefundInfo ? (
          <Popover
            title=" Breakdown"
            content={
              <Box display="flex" flexDirection="column" width="250px">
                <Box display="flex" justifyContent="space-between" flexDirection="row">
                  <Text>Refund Amount</Text>
                  <Amount value={Number(payableAmount)} currency={payment.currency} />
                </Box>
                <Box display="flex" justifyContent="space-between" flexDirection="row">
                  <Text>Instant Refund Fees</Text>
                  <Box>
                    <Text as="span" marginRight="spacing.3">
                      +
                    </Text>
                    <Amount value={instantFee.fee - instantFee.tax} currency={payment.currency} />
                  </Box>
                </Box>
                <Box display="flex" justifyContent="space-between" flexDirection="row">
                  <Text>Taxes</Text>
                  <Box>
                    <Text as="span" marginRight="spacing.3">
                      +
                    </Text>
                    <Amount value={instantFee.tax} currency={payment.currency} />
                  </Box>
                </Box>
                <Divider thickness="thick" marginTop="spacing.4" marginBottom="spacing.4" />
                <Box display="flex" justifyContent="space-between" flexDirection="row">
                  <Text weight="semibold">Amount to be deducted</Text>
                  {instantFee.fee ? (
                    <Amount
                      value={instantFee.fee + Number(payableAmount)}
                      currency={payment.currency}
                    />
                  ) : null}
                </Box>
              </Box>
            }
          >
            <PopoverInteractiveWrapper display="inline-block" width="100%">
              <Box>
                <Alert
                  color="neutral"
                  emphasis="subtle"
                  description={
                    <RefundInfo
                      payment={payment}
                      instantFee={instantFee}
                      payableAmount={payableAmount}
                      user={user}
                      org={org}
                    />
                  }
                  isDismissible={false}
                  isFullWidth
                />
              </Box>
            </PopoverInteractiveWrapper>
          </Popover>
        ) : null}
      </Box>
    </Box>
  );
};

const mapStateToProps = (state) => {
  return {
    ...state.session,
    user: state.session.user,
    org: state.payment.org,
    currentBalance: state.payment.current_balance,
    transfers: state.payment.transfers,
  };
};

export default connect(mapStateToProps, null)(InstantRefund);
