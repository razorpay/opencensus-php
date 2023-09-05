import React from 'react';
import { VisuallyHidden, Box, Text } from '@razorpay/blade/components';

import Amount from 'common/ui/Amount';
import {
  createdOn,
  amount,
  paymentId,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsTable/columns';
import { Item } from 'merchant/views/Transactions/v2/Refunds/types';
import Details from 'merchant/views/Transactions/v2/common/components/Details';
import Status from 'merchant/views/Transactions/v2/common/components/Status';
import Title from 'merchant/views/Transactions/v2/common/components/Title';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'merchant/views/Transactions/v2/common/constants';
import { getCreatedOnTime } from 'merchant/views/Transactions/v2/common/utils';

import { refundsStatusVariantMap } from './constants';

const { REFUNDS } = TransactionsEntityRoute;

const refundId = {
  ...paymentId,
  title: <Title>Refund ID</Title>,
};

const _paymentId = {
  ...paymentId,
  value: ({ payment_id: id }: Item): JSX.Element => paymentId.value({ id }),
};

// TODO: will be taken in Phase 2 after API changes
// const bankRRN = {
//   title: <Title>RRN/ARN</Title>,
//   value: (item: Item): JSX.Element => {
//     const {
//       acquirer_data: { arn, rrn },
//     } = item;
//     return <Text testID="bank-rrn">{rrn || arn || '--'}</Text>;
//   },
// };

const mobileAmount = {
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
          {getCreatedOnTime({ created_at })}
        </Text>
      </>
    );
  },
};

const status = {
  title: <Title>Status</Title>,
  value: ({ status }: Item): JSX.Element => {
    const { variant, content } = refundsStatusVariantMap[status];
    return <Status variant={variant} content={content} status={status} />;
  },
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
        <Title>Actions</Title>
      </VisuallyHidden>
    </Box>
  ),
  value: (item: Item): JSX.Element => {
    return (
      <Details
        itemId={item.id}
        baseUrl={REFUNDS}
        prevPath={REFUNDS}
        initiatePage={TransactionsPagesMap[REFUNDS]}
      />
    );
  },
};

export const mobileColumns = [mobileAmount, status, actions];
export const desktopColumns = [refundId, _paymentId, createdOn, amount, status, actions];
