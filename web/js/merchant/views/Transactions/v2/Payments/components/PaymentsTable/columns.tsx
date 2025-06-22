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
import MaskedEmail from 'merchant/components/Mask/Email';
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

import { getPaymentStatusVariantMap } from './constants';
import { getPaymentMethod, getSourceChannelType } from './utils';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import { getUser } from 'merchant/store';
import {
  DesktopColumns,
  MobileColumns,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter/types';

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
      <div className="bank-rrn" style={{ pointerEvents: 'none' }}>
        <Text testID="bank-rrn">{rrn || arn || '--'}</Text>
        <Text size="small" color="surface.text.gray.muted">
          {getPaymentMethod(item)}
        </Text>
      </div>
    );
  },
};

export const payerVPA = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Payer VPA
    </Text>
  ),
  value: (item: Item): JSX.Element => {
    const { vpa } = item;
    return (
      <Box>
        <Text testID="payer-vpa">{vpa || '--'}</Text>
      </Box>
    );
  },
};

export const customerDetail = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Customer detail
    </Text>
  ),
  value: ({ contact, email }: Item): JSX.Element => {
    const user = getUser();
    const shouldShowCopyIcon = !user?.isHidePIDetails;
    return (
      <Box display="flex" gap="spacing.2" alignItems="center">
        <Box display="flex" flexDirection="column" gap="spacing.1">
          <Text>{contact ? <MaskedContact contact={contact} /> : '--'}</Text>
          <Text size="small" color="surface.text.gray.muted">
            {email ? <MaskedEmail email={email} /> : '--'}
          </Text>
        </Box>
        <Box as="span" display="flex">
          <CustomClipboard value="" hideTooltip>
            <CopyIcon size="medium" color="interactive.icon.gray.normal" />
          </CustomClipboard>
          <PopoverComponent align="right" theme="light">
            <PopoverBody>
              <Box display="flex" marginBottom="spacing.3">
                <Text size="large" weight="semibold">
                  Copy Customer Detail
                </Text>
              </Box>
              <Box display="flex" marginBottom="spacing.1" gap="spacing.2">
                <Text color="surface.text.gray.muted">Email :</Text>
                {email ? (
                  <>
                    <Text color="surface.text.gray.subtle">
                      <MaskedEmail email={email} />
                    </Text>
                    {shouldShowCopyIcon ? (
                      <CustomClipboard value={email}>
                        <CopyIcon />
                      </CustomClipboard>
                    ) : null}
                  </>
                ) : (
                  <Text color="surface.text.gray.subtle">--</Text>
                )}
              </Box>
              <Box display="flex" gap="spacing.2">
                <Text color="surface.text.gray.muted">Contact :</Text>
                {contact ? (
                  <>
                    <Text color="surface.text.gray.subtle">
                      <MaskedContact contact={contact} />
                    </Text>
                    {shouldShowCopyIcon ? (
                      <CustomClipboard value={contact}>
                        <CopyIcon />
                      </CustomClipboard>
                    ) : null}
                  </>
                ) : (
                  <Text color="surface.text.gray.subtle">--</Text>
                )}
              </Box>
            </PopoverBody>
          </PopoverComponent>
        </Box>
      </Box>
    );
  },
};

export const createdOn = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      Created on
    </Text>
  ),
  value: ({ created_at }: Item): JSX.Element => {
    return (
      <Box pointerEvents="none">
        <CreatedOn created_at={created_at} />
      </Box>
    );
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
      <Box display="flex" flexDirection="column" pointerEvents="none">
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
      </Box>
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
      return (
        <Box pointerEvents="none">
          <Text>--</Text>
        </Box>
      );
    }
    const { variant, content } = getPaymentStatusVariantMap(window.rzp_org?.business_name)[status];
    return (
      <Box pointerEvents="none">
        <Status variant={variant} content={content} status={status} isFailedIconEnabled />
      </Box>
    );
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
  value: ({ id, status, notes }: Item): JSX.Element => {
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
        notes={notes}
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
const jnkMobileColumns = [mobileAmount, bankRRN, status, actions];

export const getMobileColumns = (
  { isJnKOmniEnabled }: MobileColumns = {
    isJnKOmniEnabled: false,
  },
) => {
  if (isJnKOmniEnabled) {
    return jnkMobileColumns;
  }
  return mobileColumns;
};

export const posOrderId = {
  title: (
    <Text size="medium" weight="semibold" color="surface.text.gray.normal">
      POS Order ID
    </Text>
  ),
  value: (item: Item): JSX.Element => {
    const external_ref_id1 = item.notes?.external_ref_id1;
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

export const desktopColumns = [
  paymentId,
  bankRRN,
  customerDetail,
  createdOn,
  amount,
  status,
  actions,
];

const jnkDesktopColumns = [paymentId, bankRRN, amount, payerVPA, createdOn, status, actions];

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

export const getDesktopColumns = ({
  isOmniView,
  shouldShowCustomTransactionTabView,
  selectedColumnsList,
  shouldDisplayOptimizerColumn,
  isJnKOmniEnabled,
  isPosOrderIDEnabled,
}: DesktopColumns) => {
  const isFailedPaymentView = window.location.pathname.includes(FAILED_PAYMENTS);
  let updatedDesktopColumns = isFailedPaymentView ? failedPaymentDesktopColumns : desktopColumns;

  if (isJnKOmniEnabled) {
    return jnkDesktopColumns;
  }

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

  if (isPosOrderIDEnabled) {
    return vasDesktopColumns;
  }

  return updatedDesktopColumns;
};
