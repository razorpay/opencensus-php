import React from 'react';
import {
  Box,
  CopyIcon,
  Text,
  VisuallyHidden,
  BankIcon,
  UserXIcon,
  BriefcaseIcon,
} from '@razorpay/blade/components';

import Amount from 'common/ui/Amount';
import MaskedContact from 'merchant/components/Mask/Contact';
import { createCustomColumnView } from 'merchant/views/Transactions/utils';
import PaymentOptimizerProvider from 'merchant/views/Transactions/v2/Payments/components/PaymentOptimizerProvider';
import { FailureCategoryMapping } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/utils';
import { Item } from 'merchant/views/Transactions/v2/Payments/types';
import CreatedOn from 'merchant/views/Transactions/v2/common/components/CreatedOn';
import Details from 'merchant/views/Transactions/v2/common/components/Details';
// eslint-disable-next-line
import CustomClipboard from 'common/ui/Clipboard/Custom';

import Status from 'merchant/views/Transactions/v2/common/components/Status';
import {
  TransactionsEntityRoute,
  TransactionsPagesMap,
} from 'merchant/views/Transactions/v2/common/constants';
import { getCreatedOnTime } from 'merchant/views/Transactions/v2/common/utils';

import { paymentStatusVariantMap } from './constants';
import { getPaymentMethod, getSourceChannelType } from './utils';

const { FAILED_PAYMENTS, PAYMENTS } = TransactionsEntityRoute;

export const FailureTypeTableMapping = {
  customer: {
    title: 'Customer drop-offs',
    icon: UserXIcon,
  },
  bank: {
    title: 'Banking Failure',
    icon: BankIcon,
  },
  business_and_others: {
    title: 'Business Failure/Others',
    icon: BriefcaseIcon,
  },
};

const PAYMENT_ID = 'Payment ID';

export const paymentId = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      {PAYMENT_ID}
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
      {PAYMENT_ID}
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
  value: ({ contact }: Item): JSX.Element => (
    <Text>{contact ? <MaskedContact contact={contact} /> : '--'}</Text>
  ),
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
    if (!paymentStatusVariantMap[status]) {
      return <Text>--</Text>;
    }
    const { variant, content } = paymentStatusVariantMap[status];
    return <Status variant={variant} content={content} status={status} isFailedIconEnabled />;
  },
};

export const reason = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Reason
    </Text>
  ),
  value: ({ error_source }: Item): JSX.Element => {
    if (!error_source || !FailureCategoryMapping[error_source]) {
      return <Text>--</Text>;
    }

    const { title, icon: Icon } = FailureTypeTableMapping[FailureCategoryMapping[error_source]];

    return (
      <Box display="flex" gap="spacing.3" alignItems="center">
        <Box display="flex">
          <Icon size="medium" color="feedback.icon.neutral.intense" />
        </Box>
        <Text>{title}</Text>
      </Box>
    );
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
        isDisabled={!paymentStatusVariantMap[status]}
        itemId={id}
        baseUrl={PAYMENTS}
        initiatePage={initiatePage}
      />
    );
  },
};

export const optimizer = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Payment provider
    </Text>
  ),
  value: (item: Item) => <PaymentOptimizerProvider item={item} />,
};

export const generateDynamicComponentV2 = (columnName: string) => ({
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      {columnName}
    </Text>
  ),
  value: (item: Item): JSX.Element => {
    return <Text>{item.notes?.[columnName] || '--'}</Text>;
  },
});

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

export const failedPaymentDesktopColumns = [
  amount,
  status,
  reason,
  customerDetail,
  paymentId,
  bankRRN,
  createdOn,
  actions,
];

export const getDesktopColumns = (
  isOmniView: boolean,
  shouldShowCustomTransactionTabView: boolean,
  selectedColumnsList: string[],
  shouldDisplayOptimizerColumn: boolean,
) => {
  const isFailedPaymentView = window.location.pathname.includes(FAILED_PAYMENTS);
  let updatedDesktopColumns = isFailedPaymentView ? failedPaymentDesktopColumns : desktopColumns;

  if (isOmniView) {
    updatedDesktopColumns = updatedDesktopColumns.map((column) =>
      column.title?.props?.children === PAYMENT_ID ? omniPaymentId : column,
    );
  }

  if (shouldDisplayOptimizerColumn) {
    updatedDesktopColumns = updatedDesktopColumns
      .slice(0, 1)
      .concat(optimizer, updatedDesktopColumns.slice(1));
  }

  if (shouldShowCustomTransactionTabView) {
    updatedDesktopColumns = createCustomColumnView(
      updatedDesktopColumns,
      selectedColumnsList,
      true,
    );
  }

  return updatedDesktopColumns;
};
