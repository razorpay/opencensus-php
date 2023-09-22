import React from 'react';
import { VisuallyHidden, Box, CopyIcon, Text } from '@razorpay/blade/components';

import Amount from 'common/ui/Amount';
import MaskedContact from 'merchant/components/Mask/Contact';
import { Item } from 'merchant/views/Transactions/v2/Payments/types';
import CreatedOn from 'merchant/views/Transactions/v2/common/components/CreatedOn';
import Details from 'merchant/views/Transactions/v2/common/components/Details';
// eslint-disable-next-line
import CustomClipboard from 'common/ui/Clipboard/Custom';

import Status from 'merchant/views/Transactions/v2/common/components/Status';
import Title from 'merchant/views/Transactions/v2/common/components/Title';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'merchant/views/Transactions/v2/common/constants';
import { getCreatedOnTime } from 'merchant/views/Transactions/v2/common/utils';

import { paymentStatusVariantMap } from './constants';
import { getPaymentMethod } from './utils';

const { FAILED_PAYMENTS, PAYMENTS } = TransactionsEntityRoute;

export const paymentId = {
  title: <Title>Payment ID</Title>,
  value: ({ id }: { id: Item['id'] }): JSX.Element => (
    <Box display="flex" testID="payment-id" columnGap="spacing.2">
      <Text>{id}</Text>
      <CustomClipboard value={id}>
        <CopyIcon size="medium" color="feedback.icon.neutral.lowContrast" />
      </CustomClipboard>
    </Box>
  ),
};

export const bankRRN = {
  title: <Title>Bank RRN</Title>,
  value: (item: Item): JSX.Element => {
    const { acquirer_data: { arn, rrn } = {} } = item;
    return (
      <div className="bank-rrn">
        <Text testID="bank-rrn">{rrn || arn || '--'}</Text>
        <Text size="small" color="surface.text.muted.lowContrast">
          {getPaymentMethod(item)}
        </Text>
      </div>
    );
  },
};

export const customerDetail = {
  title: <Title>Customer detail</Title>,
  value: ({ contact }: Item): JSX.Element => (
    <Text>{contact ? <MaskedContact contact={contact} /> : '--'}</Text>
  ),
};

export const createdOn = {
  title: <Title>Created on</Title>,
  value: ({ created_at }: Item): JSX.Element => {
    return <CreatedOn created_at={created_at} />;
  },
};

export const amount = {
  title: <Title>Amount</Title>,
  value: ({ amount, currency }: Item): JSX.Element => {
    return (
      <Amount size="body-medium-bold" isAffixSubtle={false} value={amount} currency={currency} />
    );
  },
};

export const mobileAmount = {
  ...amount,
  value: (item: Item): JSX.Element => {
    const { amount, currency, created_at } = item;
    return (
      <>
        <Amount
          marginLeft={{
            base: '-4px',
          }}
          size="body-medium-bold"
          isAffixSubtle={false}
          value={amount}
          currency={currency}
        />
        <Text size="small" color="surface.text.muted.lowContrast">
          {getCreatedOnTime({ created_at })} &bull; {getPaymentMethod(item)}
        </Text>
      </>
    );
  },
};

export const status = {
  title: <Title>Status</Title>,
  value: ({ status }: Item): JSX.Element => {
    const { variant, content } = paymentStatusVariantMap[status];
    return <Status variant={variant} content={content} status={status} />;
  },
};

export const actions = {
  title: (
    <Box
      width={{
        base: 'spacing.0',
        l: 'spacing.5',
      }}
    >
      <VisuallyHidden>
        <Title>Actions</Title>
      </VisuallyHidden>
    </Box>
  ),
  value: (item: Item): JSX.Element => {
    const currentPath = window.location.pathname.includes(FAILED_PAYMENTS)
      ? FAILED_PAYMENTS
      : PAYMENTS;
    const initiatePage = TransactionsPagesMap[currentPath];
    return (
      <Details
        itemId={item.id}
        baseUrl={PAYMENTS}
        prevPath={currentPath}
        initiatePage={initiatePage}
      />
    );
  },
};

export const mobileColumns = [mobileAmount, status, actions];
export const desktopColumns = [
  paymentId,
  bankRRN,
  customerDetail,
  createdOn,
  amount,
  status,
  actions,
];
