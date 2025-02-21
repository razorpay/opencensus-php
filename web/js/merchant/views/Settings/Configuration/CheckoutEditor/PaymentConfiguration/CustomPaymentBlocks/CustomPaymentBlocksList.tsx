import React from 'react';
import { Box } from '@razorpay/blade/components';

import { CustomPaymentBlock } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/CustomPaymentBlocks';
import { SortableCustomPaymentBlocksList } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/SortableCustomBlocksList';
import { SortableListItemData } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/SortableList';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

export type CustomPaymentBlocksListProps = {
  list: CustomPaymentBlock[];
  newBlockKey: string;
};

export function CustomPaymentBlocksList({ list, newBlockKey }: CustomPaymentBlocksListProps) {
  const { values } = useCheckoutEditor();
  const currentExpandedCustomBlock = values[CHECKOUT_EDITOR_FIELDS.CURRENT_EXPANDED_CUSTOM_BLOCK];

  const sortableBlocksList: SortableListItemData<CustomPaymentBlock>[] = list?.map(
    (customBlock) => {
      return {
        item: customBlock,
        id: `${customBlock.slug}-${currentExpandedCustomBlock === customBlock.slug}`, // rerender list when either block is visible or expanded/closed
      };
    },
  );

  const sortableBlocksListIDKey = sortableBlocksList.map((block) => block.id).join('-');
  return (
    <Box display="flex" flexDirection="column" gap="spacing.4">
      <SortableCustomPaymentBlocksList
        key={sortableBlocksListIDKey}
        list={sortableBlocksList}
        newBlockKey={newBlockKey}
      />
    </Box>
  );
}
