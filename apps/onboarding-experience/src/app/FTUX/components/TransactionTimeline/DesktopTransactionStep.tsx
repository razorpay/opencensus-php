import React from 'react';
import moment from 'moment';
import { Box, ArrowRightIcon, Link, Text, Amount } from '@razorpay/blade/components';
import {
  analyticsTrackWithUserInfo,
  i18CurrencyConversionFromMinorUnitToCommonUnit,
} from '@libs/shared-utils';
import { IMerchantPayments } from '@federated/dashboards/payments/types/payments';

const desktopDateFormat = 'D MMM, ‘YY';

const DesktopTransactionStep = ({
  step,
  isUpcoming,
  isCompleted,
  transaction,
}: {
  step: number;
  isUpcoming: boolean;
  isCompleted: boolean;
  transaction?: IMerchantPayments;
}): React.ReactElement => {
  const createdAt = transaction?.createdAt
    ? moment.unix(Number(transaction.createdAt)).format(desktopDateFormat)
    : '';

  if (isCompleted && !!transaction) {
    return (
      <Box display="flex" flexDirection="column" gap="spacing.2" alignItems="center">
        <Amount
          suffix="decimals"
          currency={transaction.currency}
          value={i18CurrencyConversionFromMinorUnitToCommonUnit(
            Number(transaction.amount),
            transaction.currency,
          )}
          size="medium"
          type="body"
          weight="semibold"
          color="surface.text.gray.subtle"
          isAffixSubtle={false}
        />
        <Text size="xsmall" weight="medium" color="surface.text.gray.muted">
          {createdAt}
        </Text>
        <Link
          size="xsmall"
          icon={ArrowRightIcon}
          iconPosition="right"
          href={`/app/payments/${transaction.id}?init_page=ftux`}
          target="_blank"
          onClick={() => {
            analyticsTrackWithUserInfo({
              objectName: 'FTUX View Transaction Link',
              actionName: 'Clicked',
              screen: 'home page',
              properties: {
                step,
              },
            });
          }}
        >
          View
        </Link>
      </Box>
    );
  }

  return (
    <Box
      display="flex"
      flexDirection="column"
      gap="spacing.2"
      alignItems="center"
      paddingTop="spacing.2"
    >
      <Text size="xsmall" weight="semibold" color="surface.text.gray.muted">
        Transaction {step}
      </Text>
      <Text size="xsmall" weight="regular" color="surface.text.gray.muted">
        {isUpcoming ? 'Upcoming' : 'Pending'}
      </Text>
    </Box>
  );
};

export default DesktopTransactionStep;
