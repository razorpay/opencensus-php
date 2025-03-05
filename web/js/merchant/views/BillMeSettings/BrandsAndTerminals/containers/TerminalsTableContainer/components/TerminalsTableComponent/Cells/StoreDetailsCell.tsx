import React from 'react';
import { Box, Text, Badge } from '@razorpay/blade/components';

import { STORE_TYPE_MAP } from 'merchant/views/BillMeSettings/common/constants';

import type { Store } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/types';

type StoreDetailsCellProps = {
  store: Store;
};

const StoreDetailsCell = ({ store }: StoreDetailsCellProps): React.ReactElement => {
  const { storeInfo, name = '' } = store || {};
  const { storeCode = '', storeType } = storeInfo || {};
  const { label, color } = STORE_TYPE_MAP[storeType] || {};
  return (
    <Box display="flex" flexDirection="column" gap="spacing.2" whiteSpace="normal">
      <Text wordBreak="break-all">
        {storeCode} - {name}
      </Text>
      <Badge color={color || 'neutral'}>{label || '-'}</Badge>
    </Box>
  );
};

export default StoreDetailsCell;
