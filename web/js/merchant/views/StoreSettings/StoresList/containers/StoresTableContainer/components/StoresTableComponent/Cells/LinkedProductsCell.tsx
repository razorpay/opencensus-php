import React from 'react';
import { Box, Badge } from '@razorpay/blade/components';

import { LINKED_PRODUCTS_MAP } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/constants';

import type { StoreLinkedProductsType } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/types';

type LinkedProductsCellProps = {
  products: StoreLinkedProductsType[];
};

const LinkedProductsCell = ({ products }: LinkedProductsCellProps): React.ReactElement => {
  return (
    <Box display="flex" gap="spacing.4" whiteSpace="normal">
      {(products || []).map((product: string) => (
        <Badge key={product}>{LINKED_PRODUCTS_MAP[product]?.label}</Badge>
      ))}
    </Box>
  );
};

export default LinkedProductsCell;
