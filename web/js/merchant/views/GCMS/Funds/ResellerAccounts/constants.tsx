import React from 'react';
import { Text } from '@razorpay/blade/components';

import { idItem } from 'common/ui/item/id';
import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import { ResellersBalance } from 'merchant/views/GCMS/Funds/types';

const resellerAccountsColumns = [
  {
    label: 'Reseller Name',
    value: (item: ResellersBalance): JSX.Element => <Text>{item.merchant_name}</Text>,
  },
  {
    label: 'Reseller ID',
    value: (item: ResellersBalance): JSX.Element => idItem(item.id),
  },
  {
    label: 'Total Available Fund',
    value: (item: ResellersBalance): JSX.Element => {
      return (
        <Text color="surface.text.subtle.lowContrast" testID="fund-amount">
          {getFormattedAmountNew(item.balance, true)}
        </Text>
      );
    },
  },
];

export { resellerAccountsColumns };
