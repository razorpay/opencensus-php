import React from 'react';
import { Box, Text, Tag } from '@razorpay/blade/components';

const StoreTags = ({ storeId, flatStores, clearStoreId }) => {
  if (!storeId || storeId.length === 0) return null;

  return (
    <Box marginLeft="spacing.3" display="flex" alignItems="center" flexDirection="row">
      <Text color="surface.text.gray.subtle">Store:</Text>
      <Box display="flex" flexWrap="wrap" gap="spacing.2">
        {storeId.map(id => {
          const store = flatStores.find(store => store.store_id === id || store.group_id === id);
          return (
            <Tag
              key={id}
              marginLeft="spacing.3"
              size="medium"
              onDismiss={() => clearStoreId(id)}
            >
              {store?.name || id}
            </Tag>
          );
        })}
      </Box>
    </Box>
  );
};

export default StoreTags;
