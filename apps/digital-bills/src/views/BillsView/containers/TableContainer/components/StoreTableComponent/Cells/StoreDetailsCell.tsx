import React from 'react';
import { Box, Text } from '@razorpay/blade/components';

import imagePlaceholder from '@apps/digital-bills/src/assets/icons/image-placeholder.svg';
import type { Store } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

type StoreDetailsCellProps = { store: Store };

const StoreDetailsCell = ({ store }: StoreDetailsCellProps): React.ReactElement => {
  const { storeInfo, name = '', brand } = store || {};
  const { storeCode = '' } = storeInfo || {};
  return (
    <Box display="flex" alignItems="center">
      <Box marginRight="spacing.3">
        <img
          src={brand?.logo || imagePlaceholder}
          width="32px"
          height="32px"
          alt={`${name} brand logo`}
        />
      </Box>
      <Box>
        <Text>
          {storeCode} - {name}
        </Text>
        <Text marginTop="spacing.2" color="surface.text.gray.muted">
          {store?.address?.displayAddress}
        </Text>
      </Box>
    </Box>
  );
};

export default StoreDetailsCell;
