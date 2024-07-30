import React from 'react';
import {
  Alert,
  Amount,
  Box,
  HelpCircleIcon,
  Popover,
  PopoverInteractiveWrapper,
  Text,
} from '@razorpay/blade/components';
import { Link as RouterLink } from 'react-router-dom';

export const RefundInfo = ({ payment, instantFee, payableAmount, user, org }) => (
  <Box display="flex" alignItems="center" flexDirection="row" flexWrap="wrap">
    <Text size="small" color="surface.text.gray.subtle">
      A total amount of &nbsp;
    </Text>
    <Amount
      value={Number(payableAmount) + instantFee.fee}
      currency={payment.currency}
      size="small"
      weight="semibold"
      color="surface.text.gray.subtle"
    />
    <Text size="small" color="surface.text.gray.subtle">
      &nbsp; will be deducted
    </Text>
    {user?.isSingleReconEnabled &&
    user?.isOptimizerEnabled &&
    payment?.optimizer_provider?.toLowerCase() !== 'razorpay' ? (
      <Text size="small" color="surface.text.gray.subtle" weight="semibold">
        from your {org.business_name} Current balance
      </Text>
    ) : null}
  </Box>
);

export const renderHelperAlerts = ({
  currentBalance,
  isInstantRefundCheckDisabled,
  instantRefundSupport,
  user,
  i18,
  hasEnoughFunds,
}) => {
  if (currentBalance.loading) return null;

  if (isInstantRefundCheckDisabled) {
    if (!hasEnoughFunds) {
      return (
        <Alert
          color="notice"
          emphasis="subtle"
          description={
            <Text size="small" color="surface.text.gray.subtle">
              Your account does not have sufficient balance to instantly refund this payment &nbsp;
              <span>
                {user.isRefundCreditSelfServeEnabled && user.isRefundSourceFallbackEnabled ? (
                  <RouterLink to="/credits" target="_blank" rel="noreferrer noopener">
                    Add Credits&nbsp;
                    <i className="i i-external-link" />
                  </RouterLink>
                ) : (
                  <RouterLink to="/addfunds" target="_blank" rel="noreferrer noopener">
                    Add Funds&nbsp;
                    <i className="i i-external-link" />
                  </RouterLink>
                )}
              </span>
            </Text>
          }
          isDismissible={false}
          isFullWidth
        />
      );
    }

    if (!i18.isConfigTagEnabled('refunds.instant_refunds') && !instantRefundSupport) {
      return (
        <Alert
          color="neutral"
          emphasis="subtle"
          description="Currently, Instant Refunds are available on TPV, netbanking and UPI only."
          isDismissible={false}
          isFullWidth
        />
      );
    }
  }

  return null;
};

export const renderInstantFeeDetails = ({
  isInstantRefundChecked,
  isInstantRefundCheckDisabled,
  instantFee,
  payment,
}) => {
  if (!isInstantRefundChecked) {
    if (!isInstantRefundCheckDisabled) {
      return (
        <Popover
          content={
            <Box>
              <Text as="span" marginRight="spacing.2">
                You can refund this payment instantly for a small fee of{' '}
              </Text>
              <Box as="span">
                <Amount
                  weight="semibold"
                  value={instantFee.fee - instantFee.tax}
                  currency={payment.currency}
                />
              </Box>
              <Text as="span" marginLeft="spacing.2">
                (Plus Taxes)
              </Text>
            </Box>
          }
        >
          <PopoverInteractiveWrapper display="inline-block">
            <HelpCircleIcon color="interactive.icon.gray.normal" size="medium" />
          </PopoverInteractiveWrapper>
        </Popover>
      );
    }
  } else if (!isInstantRefundCheckDisabled) {
    return (
      <Box display="flex" alignItems="center" flexDirection="row">
        <Text marginRight="spacing.3">Fee</Text>
        <Amount value={instantFee.fee} currency={payment.currency} />
      </Box>
    );
  }

  return null;
};
