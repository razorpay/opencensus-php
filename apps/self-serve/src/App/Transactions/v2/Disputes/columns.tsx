import { Amount } from '@dashboard/shared-ui/components';
import { Box, CopyIcon, GlobeIcon, Text, VisuallyHidden } from '@razorpay/blade/components';
import { getFlagOfCountry } from '@razorpay/i18nify-js';
import React from 'react';

import CustomClipboard from '@dashboard/shared-ui/Clipboard/Custom';
import { daysFromToday, titleCase } from '@dashboard/shared-utils/rzp-utils';
import { disputesStatusVariantMap } from 'apps/self-serve/src/App/Transactions/v2/Disputes/constants';
import { amount } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsTable/columns';
import Details from 'apps/self-serve/src/App/Transactions/v2/common/components/Details/Details';
import Status from 'apps/self-serve/src/App/Transactions/v2/common/components/Status';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { getCreatedOnTime } from 'apps/self-serve/src/App/Transactions/v2/common/utils';

const { DISPUTES } = TransactionsEntityRoute;

export const _disputeId = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal" textAlign="left">
      Dispute ID
    </Text>
  ),
  value: ({ id }) => {
    return (
      <Box display="flex" testID="payment-id" columnGap="spacing.2">
        <Text>{id ?? ''}</Text>
        <CustomClipboard value={id}>
          <CopyIcon size="medium" color="feedback.icon.neutral.intense" />
        </CustomClipboard>
      </Box>
    );
  },
};

export const daysLeftInExpiry = (expiresOn) => {
  const daysLeft = daysFromToday(expiresOn);
  if (daysLeft < 0) {
    return <Text>Passed</Text>;
  } else if (daysLeft === 0) {
    return <Text color="feedback.text.negative.intense">Today</Text>;
  } else if (daysLeft === 1) {
    return <Text>Tomorrow</Text>;
  } else {
    return <Text>{getCreatedOnTime({ created_at: expiresOn })}</Text>;
  }
};

export const stage = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Stage
    </Text>
  ),
  value: (item) => <Text>{titleCase(item.phase ?? '')}</Text>,
};

export const respondBy = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Respond By
    </Text>
  ),
  value: (item) => (
    <Text> {item.status === 'open' ? daysLeftInExpiry(item.respond_by) : '--'}</Text>
  ),
};

export const raisedOn = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Raised on
    </Text>
  ),
  value: (item) => <Text>{getCreatedOnTime({ created_at: item.created_at })}</Text>,
};

export const flag = {
  title: '',
  value: (item) =>
    item?.international ? (
      <GlobeIcon />
    ) : (
      <img src={getFlagOfCountry('IN')?.['4X3']} alt="national" />
    ),
};

export const _status = (isMobileView = false) => {
  return {
    title: (
      <Text
        size="medium"
        weight="semibold"
        color="surface.text.gray.normal"
        textAlign={isMobileView ? 'center' : 'left'}
      >
        Status
      </Text>
    ),
    value: ({ status }): JSX.Element => {
      if (!disputesStatusVariantMap[status]) {
        return <Text textAlign={isMobileView ? 'center' : 'left'}>--</Text>;
      }
      const { variant, content } = disputesStatusVariantMap[status];

      return (
        <Box
          display="flex"
          alignItems={isMobileView ? 'center' : 'left'}
          justifyContent={isMobileView ? 'center' : 'left'}
        >
          <Status variant={variant} content={content} status={status} />
        </Box>
      );
    },
  };
};

const actions = {
  title: (
    <Box
      width={{
        base: 'spacing.0',
        l: 'spacing.9',
      }}
    >
      <VisuallyHidden>
        <Text size="medium" weight="semibold" color="surface.text.gray.normal">
          Actions
        </Text>
      </VisuallyHidden>
    </Box>
  ),
  value: (item) => {
    return (
      <Details itemId={item.id} baseUrl={DISPUTES} initiatePage={TransactionsPagesMap[DISPUTES]} />
    );
  },
};

const mobileAmount = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal" textAlign="center">
      Amount
    </Text>
  ),
  value: ({ amount, currency }) => {
    return (
      <Box textAlign="center">
        <Amount
          isAffixSubtle={false}
          value={amount}
          currency={currency}
          type="body"
          size="medium"
          weight="semibold"
        />
      </Box>
    );
  },
};

export const mobileColumns = [_disputeId, mobileAmount, _status(true), actions];

export const desktopColumns = [
  flag,
  _disputeId,
  amount,
  stage,
  raisedOn,
  respondBy,
  _status(),
  actions,
];
