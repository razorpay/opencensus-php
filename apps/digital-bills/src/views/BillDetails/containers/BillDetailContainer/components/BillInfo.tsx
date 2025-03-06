import React from 'react';
import {
  BillIcon,
  Box,
  Button,
  Link,
  SendIcon,
  Text,
  TrashIcon,
  Amount,
  CopyIcon,
  useToast,
} from '@razorpay/blade/components';
import moment from 'moment';

import { BillTransactionTypes } from '@apps/digital-bills/src/utils/constants';
import getBillUrl from '@apps/digital-bills/src/utils/helpers/getBillUrl';
import { useBillDetailsStore } from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/stores/billDetailsStore';

type BillInfoProps = {
  userInfo: {
    contactNo: string;
    email: string;
  };
  billInfo: {
    amount: number;
    transactionType: string;
    invoiceNo: string;
    billId: string;
    timestamp: string;
    legacyEntityId: string | null;
  };
};

const BillInfo = (props: BillInfoProps): React.ReactElement => {
  const { userInfo, billInfo } = props;
  const { contactNo, email } = userInfo;
  const { amount, invoiceNo, billId, timestamp, transactionType, legacyEntityId } = billInfo;

  const { show } = useToast();
  const { onDeleteModalOpen, onResendModalOpen } = useBillDetailsStore();

  return (
    <Box
      display="grid"
      gap="spacing.4"
      alignItems="center"
      gridTemplateColumns="1fr 2fr"
      marginY="spacing.3"
      padding="spacing.6"
    >
      {/* Bill Details Row */}
      <Text weight="semibold" size="large">
        Bill Details
      </Text>
      <Box display="flex" gap="spacing.4">
        <Button
          icon={TrashIcon}
          variant="tertiary"
          onClick={onDeleteModalOpen}
          testID="delete-btn"
          size="small"
        />
        <Button
          icon={SendIcon}
          variant="tertiary"
          onClick={onResendModalOpen}
          testID="resend-bill-btn"
          size="small"
        >
          Resend Bill
        </Button>
      </Box>
      {/* Bill Link Row */}
      <Text>Bill Link:</Text>
      <Box display="flex" gap="spacing.3" alignItems="center">
        <Link
          href={`${legacyEntityId ? getBillUrl(legacyEntityId) : '#'}`}
          target="_blank"
          icon={BillIcon}
          variant="anchor"
        >
          Click here to view the Bill
        </Link>
        <Link
          testID="copy-icon"
          icon={CopyIcon}
          onClick={(): void => {
            navigator.clipboard
              .writeText(`${legacyEntityId ? getBillUrl(legacyEntityId) : '#'}`)
              .then(() => {
                show({
                  type: 'informational',
                  content: 'Bill url copied successfully!',
                });
              })
              .catch(() => {
                show({
                  type: 'informational',
                  content: 'Something went wrong copying bill url!',
                  color: 'negative',
                });
              });
          }}
        />
      </Box>
      {/* Contact Number Row */}
      <Text>Contact No</Text>
      <Text weight="semibold">{contactNo}</Text>
      {/* Email address row */}
      <Text>Email:</Text>
      <Text weight="semibold">{email}</Text>
      {/* Amount row */}
      <Text>Amount:</Text>
      <Amount weight="semibold" value={amount} isAffixSubtle={false} />
      {/* Type of transaction row */}
      <Text>Type of Transaction:</Text>
      <Text weight="semibold">{BillTransactionTypes[transactionType]?.label ?? '-'}</Text>
      {/* Invoice number row */}
      <Text>Invoice No:</Text>
      <Text weight="semibold">#{invoiceNo}</Text>
      {/* Bill ID row */}
      <Text>Bill ID:</Text>
      <Text weight="semibold">{billId}</Text>
      {/* Bill Date row */}
      <Text>Date:</Text>
      <Text weight="semibold">{moment(timestamp).format('DD/MM/YYYY')}</Text>
      {/* Bill Time row */}
      <Text>Time:</Text>
      <Text weight="semibold">{moment(timestamp).format('h:mm A')}</Text>
    </Box>
  );
};

export default BillInfo;
