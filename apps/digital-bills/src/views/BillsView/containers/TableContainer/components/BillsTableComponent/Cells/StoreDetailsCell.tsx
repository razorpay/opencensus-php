import React from 'react';
import { Badge, Box, Text } from '@razorpay/blade/components';

import imagePlaceholder from '@apps/digital-bills/src/assets/icons/image-placeholder.svg';
import { SOURCE_TYPE_MAPPER } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/BillsTableComponent/constants';

import type {
  Store,
  Brand,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

type StoreDetailsCellProps = {
  store: Store;
  brand: Brand;
};

const StoreDetailsCell = ({ store, brand }: StoreDetailsCellProps): React.ReactElement => {
  return (
    <Box marginY="spacing.2" display="flex" alignItems="center">
      <Box marginRight="spacing.3">
        <img
          src={brand?.logo || imagePlaceholder}
          width="24px"
          height="24px"
          alt={`${brand?.name} logo`}
        />
      </Box>
      <Box>
        <Box whiteSpace="normal">
          <Text size="medium" weight="semibold" wordBreak="break-all">
            {`${store?.storeInfo?.storeCode} - ${store?.name}`}
          </Text>
        </Box>
        <Box whiteSpace="normal">
          <Text size="small" marginTop="spacing.2" wordBreak="break-all">
            {store?.address?.displayAddress}
          </Text>
        </Box>
        {store?.platform ? (
          <Badge size="small" color={SOURCE_TYPE_MAPPER[store.platform]?.Variant}>
            {SOURCE_TYPE_MAPPER[store.platform]?.Name}
          </Badge>
        ) : null}
      </Box>
    </Box>
  );
};

export default StoreDetailsCell;
