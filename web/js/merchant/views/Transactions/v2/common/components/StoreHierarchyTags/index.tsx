import React from 'react';
import { Box, Text, Tag } from '@razorpay/blade/components';

const StoreTags = ({ storeId, flatStores, clearStoreId }) => {
  if (!storeId || storeId.length === 0) return null;

  return (
    <Box display="flex" alignItems="center" flexDirection="row" flexWrap="wrap">
      <Text color="surface.text.gray.subtle" as="span" marginTop="spacing.1">Store:</Text>
      {storeId.map(id => {
        const store = flatStores.find(store => store.store_id === id || store.group_id === id);
        return (
          <Tag
            key={id}
            marginLeft="spacing.3"
            size="medium"
            marginTop="spacing.2"
            onDismiss={() => clearStoreId(id)}
          >
            {store?.name || id}
          </Tag>
        );
      })}
    </Box>
  );
};

export default StoreTags;
