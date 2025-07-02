import { Box, CopyIcon, Text, VisuallyHidden } from '@razorpay/blade/components';
import React from 'react';

import Amount from '@libs/web-nexus/common//ui/Amount';
import { maskContact } from '@libs/shared-utils';
import { PaymentsDashboardUser } from '@libs/shared-types/payments';
import CustomClipboard from '@libs/web-nexus/common/ui/Clipboard/Custom';
import { createCustomColumnView } from '../PaymentsList/utils';
import { getPaymentMethod, getSourceChannelType } from './utils';
import { getPaymentStatusVariantMap } from './constants';
import { Item } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';
import CreatedOn from 'apps/self-serve/src/App/Transactions/v2/common/components/CreatedOn';
import Details from 'apps/self-serve/src/App/Transactions/v2/common/components/Details';
import PaymentOptimizerProvider from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentOptimizerProvider';
// eslint-disable-next-line

import Status from 'apps/self-serve/src/App/Transactions/v2/common/components/Status';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { getCreatedOnTime } from 'apps/self-serve/src/App/Transactions/v2/common/utils';

const { FAILED_PAYMENTS, PAYMENTS } = TransactionsEntityRoute;

export const paymentId = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Payment ID
    </Text>
  ),
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
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Payment ID
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

export const bankRRN = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Bank RRN
    </Text>
  ),
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
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Customer detail
    </Text>
  ),
  value: ({ contact }: Item, _: any, extraData: { user: PaymentsDashboardUser }): JSX.Element => {
    const { user } = extraData || {};
    return <Text>{contact ? maskContact(contact, user?.isHidePIDetails) : '--'}</Text>;
  },
};

export const createdOn = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Created on
    </Text>
  ),
  value: ({ created_at }: Item): JSX.Element => {
    return <CreatedOn created_at={created_at} />;
  },
};

export const amount = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Amount
    </Text>
  ),
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
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Status
    </Text>
  ),
  value: ({ status }: Item): JSX.Element => {
    if (!getPaymentStatusVariantMap(window.rzp_org?.business_name)[status]) {
      return <Text>--</Text>;
    }
    const { variant, content } = getPaymentStatusVariantMap(window.rzp_org?.business_name)[status];
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
        <Text size="medium" weight="semibold" color="surface.text.gray.normal">
          Actions
        </Text>
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
        isDisabled={!getPaymentStatusVariantMap(window.rzp_org?.business_name)[status]}
        itemId={id}
        baseUrl={PAYMENTS}
        initiatePage={initiatePage}
      />
    );
  },
};

export const generateDynamicComponent = (columnName: string) => ({
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      {columnName}
    </Text>
  ),
  value: (item: Item): JSX.Element => {
    return <Text>{item.notes?.[columnName] || '--'}</Text>;
  },
});

export const optimizer = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Payment provider
    </Text>
  ),
  value: (item: Item) => <PaymentOptimizerProvider item={item} />,
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

export const posOrderId = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      POS Order ID
    </Text>
  ),
  value: (item: Item): JSX.Element => {
    const external_ref_id1 = item.notes?.external_ref_id1 || item.notes?.externalRefNumber;
    return (
      <Box display="flex" testID="external-ref-id1" columnGap="spacing.2">
        <Text>{external_ref_id1 || '--'}</Text>
      </Box>
    );
  },
};

export const vasDesktopColumns = [
  paymentId,
  posOrderId,
  bankRRN,
  customerDetail,
  createdOn,
  amount,
  status,
  actions,
];

export const getDesktopColumns = (
  isOmniView: boolean,
  shouldShowCustomTransactionTabView: boolean,
  selectedColumnsList: string[],
  shouldDisplayOptimizerColumn: boolean,
  isPosOrderIDEnabled: boolean,
) => {
  let updatedDesktopColumns = desktopColumns;
  if (isOmniView) {
    updatedDesktopColumns = [omniPaymentId, ...desktopColumns.slice(1, desktopColumns.length)];
  }

  if (shouldDisplayOptimizerColumn) {
    updatedDesktopColumns = updatedDesktopColumns
      .slice(0, 1)
      .concat(optimizer, updatedDesktopColumns.slice(1));
  }

  if (shouldShowCustomTransactionTabView) {
    updatedDesktopColumns = createCustomColumnView(updatedDesktopColumns, selectedColumnsList);
  }

  if (isPosOrderIDEnabled) {
    return vasDesktopColumns;
  }

  return updatedDesktopColumns;
};
