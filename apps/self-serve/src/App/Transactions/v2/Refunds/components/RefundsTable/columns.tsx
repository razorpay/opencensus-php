// TODO: Fix imports, currently its out of scope from phase 1;
// @ts-nocheck
import { Box, Text, VisuallyHidden } from '@razorpay/blade/components';
import React from 'react';

import {
  amount,
  createdOn,
  paymentId,
} from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsTable/columns';
import { Item } from 'apps/self-serve/src/App/Transactions/v2/Refunds/types';
import Details from 'apps/self-serve/src/App/Transactions/v2/common/components/Details';
import Status from 'apps/self-serve/src/App/Transactions/v2/common/components/Status';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { getCreatedOnTime } from 'apps/self-serve/src/App/Transactions/v2/common/utils';
import { Amount } from '@dashboard/shared-ui/components';
import { refundsStatusVariantMap } from './constants';
import { getSourceChannelType } from './utils';

const { REFUNDS } = TransactionsEntityRoute;

const refundId = {
  ...paymentId,
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Refund ID
    </Text>
  ),
};

const omniRefundId = {
  ...paymentId,
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Refund ID
    </Text>
  ),
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
          isAffixSubtle={false}
          value={amount}
          currency={currency}
          type="body"
          size="medium"
          weight="semibold"
        />
        <Text size="small" color="surface.text.gray.muted">
          {getCreatedOnTime({ created_at })}
        </Text>
      </>
    );
  },
};

const status = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Status
    </Text>
  ),
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
        <Text size="medium" weight="semibold" color="surface.text.gray.normal">
          Actions
        </Text>
      </VisuallyHidden>
    </Box>
  ),
  value: (item: Item): JSX.Element => {
    return (
      <Details itemId={item.id} baseUrl={REFUNDS} initiatePage={TransactionsPagesMap[REFUNDS]} />
    );
  },
};

export const mobileColumns = [mobileAmount, status, actions];
export const desktopColumns = [refundId, _paymentId, createdOn, amount, status, actions];
export const getDesktopColumns = (isOmniView?: boolean) => {
  if (isOmniView) {
    return [omniRefundId, ...desktopColumns.slice(1, desktopColumns.length)];
  }
  return desktopColumns;
};
