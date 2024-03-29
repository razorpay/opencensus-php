import { Box, CopyIcon, Heading, Text, VisuallyHidden } from '@razorpay/blade/components';
import React from 'react';

import { Amount } from '@dashboard/shared-ui/components';
import maskContact from '@dashboard/shared-utils/maskContact';
import { User } from '@dashboard/shared-utils/typings';
import { Item } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';
import CreatedOn from 'apps/self-serve/src/App/Transactions/v2/common/components/CreatedOn';
import Details from 'apps/self-serve/src/App/Transactions/v2/common/components/Details';
// eslint-disable-next-line
import CustomClipboard from '@dashboard/shared-ui/Clipboard/Custom';

import Status from 'apps/self-serve/src/App/Transactions/v2/common/components/Status';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { getCreatedOnTime } from 'apps/self-serve/src/App/Transactions/v2/common/utils';
import { getPaymentMethod, getSourceChannelType } from './utils';
import { paymentStatusVariantMap } from './constants';

const { FAILED_PAYMENTS, PAYMENTS } = TransactionsEntityRoute;

export const paymentId = {
  title: <Heading size="large">Payment ID</Heading>,
  value: ({ id }: { id: Item['id'] }): JSX.Element => (
    <Box display="flex" testID="payment-id" columnGap="spacing.2">
      <Text>{id}</Text>
      <CustomClipboard value={id}>
        <CopyIcon size="medium" color="feedback.icon.neutral.intense" />
      </CustomClipboard>
    </Box>
  ),
};

export const omniPaymentId = {
  title: <Heading size="large">Payment ID</Heading>,
  value: ({
    id,
    source_channel,
  }: {
    id: Item['id'];
    source_channel: Item['source_channel'];
  }): JSX.Element => (
    <Box display="block" columnGap="spacing.2" testID="source-channel">
      {paymentId.value({ id })}
      <Text size="small" color="surface.text.gray.muted">
        {getSourceChannelType(source_channel)}
      </Text>
    </Box>
  ),
};

export const bankRRN = {
  title: <Heading size="large">Bank RRN</Heading>,
  value: (item: Item): JSX.Element => {
    const { acquirer_data: { arn, rrn } = {} } = item;
    return (
      <div className="bank-rrn">
        <Text testID="bank-rrn">{rrn || arn || '--'}</Text>
        <Text size="small" color="surface.text.gray.muted">
          {getPaymentMethod(item)}
        </Text>
      </div>
    );
  },
};

export const customerDetail = {
  title: <Heading size="large">Customer detail</Heading>,
  value: ({ contact }: Item, _: any, extraData: { user: User }): JSX.Element => {
    const { user } = extraData;
    return <Text>{contact ? maskContact(contact, user?.isHidePIDetails) : '--'}</Text>;
  },
};

export const createdOn = {
  title: <Heading size="large">Created on</Heading>,
  value: ({ created_at }: Item): JSX.Element => {
    return <CreatedOn created_at={created_at} />;
  },
};

export const amount = {
  title: <Heading size="large">Amount</Heading>,
  value: ({ amount, currency }: Item): JSX.Element => {
    return (
      <Amount
        isAffixSubtle={false}
        value={amount}
        currency={currency}
        type="body"
        size="medium"
        weight="semibold"
      />
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
          isAffixSubtle={false}
          value={amount}
          currency={currency}
          type="body"
          size="medium"
          weight="semibold"
        />
        <Text size="small" color="surface.text.gray.muted">
          {getCreatedOnTime({ created_at })} &bull; {getPaymentMethod(item)}
        </Text>
      </>
    );
  },
};

export const status = {
  title: <Heading size="large">Status</Heading>,
  value: ({ status }: Item): JSX.Element => {
    if (!paymentStatusVariantMap[status]) {
      return <Text>--</Text>;
    }
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
        <Heading size="large">Actions</Heading>
      </VisuallyHidden>
    </Box>
  ),
  value: ({ id, status }: Item): JSX.Element => {
    const currentPath = window.location.pathname.includes(FAILED_PAYMENTS)
      ? FAILED_PAYMENTS
      : PAYMENTS;
    const initiatePage = TransactionsPagesMap[currentPath];
    return (
      <Details
        isDisabled={!paymentStatusVariantMap[status]}
        itemId={id}
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

export const getDesktopColumns = (shouldDisplaySourceChannel: boolean) => {
  if (shouldDisplaySourceChannel) {
    return [omniPaymentId, ...desktopColumns.slice(1, desktopColumns.length)];
  }
  return desktopColumns;
};
