import React from 'react';
import { Box, Amount } from '@razorpay/blade/components';

import Image from 'common/ui/Image';
import { idItem } from 'common/ui/item/id';
import { ResellersBalance } from 'merchant/views/GCMS/Funds/types';

const RESELLER_NAME = {
  title: 'Reseller Name',
  value: (item: ResellersBalance): JSX.Element => (
    <Box flexDirection="row" display="flex" alignItems="center">
      <Box width="48px" marginRight="spacing.3" testID="renderImage">
        <Image src={item.logo} alt={item.merchant_name} />
      </Box>
      <Box flexGrow={1}>{item.merchant_name}</Box>
    </Box>
  ),
};

const RESELLER_ID = {
  title: 'Reseller ID',
  value: (item: ResellersBalance): JSX.Element => idItem(item.id),
};

const TOTAL_AVAILABLE_FUND = {
  title: 'Total Available Fund',
  value: (item: ResellersBalance): JSX.Element => {
    return <Amount value={item.balance} testID="fund-amount" />;
  },
};

export { RESELLER_NAME, RESELLER_ID, TOTAL_AVAILABLE_FUND };
