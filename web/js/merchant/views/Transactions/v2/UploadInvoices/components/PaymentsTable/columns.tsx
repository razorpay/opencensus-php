import React from 'react';
import { Box, Text, Link, Amount, AmountProps, CopyIcon } from '@razorpay/blade/components';

// eslint-disable-next-line
import CustomClipboard from 'common/ui/Clipboard/Custom';

import SenderDetails from 'merchant/views/Transactions/v1/B2bPayments/components/SenderDetails';
import { status } from 'merchant/views/Transactions/v2/Payments/components/PaymentsTable/columns';
import { getPaymentMethod } from 'merchant/views/Transactions/v2/Payments/components/PaymentsTable/utils';
import { Item } from 'merchant/views/Transactions/v2/UploadInvoices/types';

import InvoiceActions from './InvoiceActions';
import BuyerAddressActions from './BuyerAddressActions';

const Title = ({ title }: { title: string }) => (
  <Text size="medium" weight="semibold" color="surface.text.gray.normal">
    {title}
  </Text>
);

export const paymentId = {
  title: <Title title="Payment ID" />,
  value: ({ id }: { id: Item['id'] }): JSX.Element => (
    <Box display="flex" testID="payment-id" columnGap="spacing.2">
      <Link variant="button">{id}</Link>
      <CustomClipboard value={id}>
        <CopyIcon size="medium" color="feedback.icon.neutral.intense" />
      </CustomClipboard>
    </Box>
  ),
};

export const amount = {
  title: <Title title="Amount" />,
  value: ({ currency, amount }: Item): JSX.Element => {
    return <Amount currency={currency as AmountProps['currency']} value={amount} />;
  },
};

export const method = {
  title: <Title title="Method" />,
  value: ({ method, wallet }: Item): JSX.Element => {
    return (
      <Text color="surface.text.gray.subtle">{getPaymentMethod({ method, wallet } as Item)}</Text>
    );
  },
};

export const senderDetails = {
  title: <Title title="Sender details" />,
  value: ({ sender_address }: Item): JSX.Element => {
    return <SenderDetails name={sender_address?.name} country={sender_address?.country} />;
  },
};

export const invoice = {
  title: <Title title="Invoice" />,
  value: (item): JSX.Element => <InvoiceActions item={item} />,
};

export const buyerAddress = {
  title: <Title title="Buyer address" />,
  value: ({ id, status, sender_address }: Item): JSX.Element => (
    <BuyerAddressActions id={id} status={status} senderDetails={sender_address} />
  ),
};

export const columns = [paymentId, amount, method, senderDetails, status, invoice, buyerAddress];
