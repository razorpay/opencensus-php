import React from 'react';
import { ChevronDownIcon, IconButton, Text } from '@razorpay/blade/components';

import { ListItemCard } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/ListItemCard';
import {
  SortableList,
  SortableListItemData,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/components/SortableList';
import { SortableListItem } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/SortableListItem';
import { PREVIEW_SCREEN } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import { CustomPaymentBlockForm } from './CustomPaymentBlockForm';
import { CustomPaymentBlock } from './CustomPaymentBlocks';

export type SortableCustomPaymentBlocksListProps = {
  list: SortableListItemData<CustomPaymentBlock>[];
  newBlockKey: string;
};

export function SortableCustomPaymentBlocksList({
  list,
  newBlockKey,
}: SortableCustomPaymentBlocksListProps) {
  const blocks = list;
  const {
    values,
    handleSelectedConfigChange,
    handleSelectedPaymentOptionChange,
    handlePreviewScreenChange,
    handleCurrentExpandedCustomBlockChange,
  } = useCheckoutEditor();

  const selectedConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
  const currentExpandedBlockKey = values[CHECKOUT_EDITOR_FIELDS.CURRENT_EXPANDED_CUSTOM_BLOCK];
  const sequence = selectedConfig.checkout_config?.display?.sequence ?? [];

  function updateCustomBlocksSequence(updatedCustomBlocksOrder: string[]) {
    const standardBlocksSequence = sequence.filter((block) => !block.includes('block.'));
    const updatedCustomBlocksSequence = updatedCustomBlocksOrder.map((block) => `block.${block}`);
    const updatedSequence = [...updatedCustomBlocksSequence, ...standardBlocksSequence];
    handleSelectedConfigChange({
      ...selectedConfig,
      checkout_config: {
        ...selectedConfig.checkout_config,
        display: {
          ...selectedConfig?.checkout_config?.display,
          sequence: updatedSequence,
        },
      },
    });
    // adding home so as to reset the prefill
    handleSelectedPaymentOptionChange({
      name: 'home',
      isCustomBlock: false,
    });
  }

  function updateSortableList(blocks: SortableListItemData<CustomPaymentBlock>[]) {
    const updatedCustomBlocksOrder = blocks.map((block) => block.item.slug);
    updateCustomBlocksSequence(updatedCustomBlocksOrder);
  }

  function handleShowCustomBlockDetails(blockDetails: CustomPaymentBlock) {
    handlePreviewScreenChange(PREVIEW_SCREEN.METHODS);
    handleCurrentExpandedCustomBlockChange(blockDetails.slug);
    const block = selectedConfig?.checkout_config?.display?.blocks?.[blockDetails.slug];
    handleSelectedPaymentOptionChange({
      name: block?.name ?? '',
      isCustomBlock: true,
    });
  }

  function handleCustomBlockClick(blockDetails: CustomPaymentBlock) {
    handlePreviewScreenChange(PREVIEW_SCREEN.METHODS);
    const block = selectedConfig?.checkout_config?.display?.blocks?.[blockDetails.slug];
    handleSelectedPaymentOptionChange({
      name: block?.name ?? '',
      isCustomBlock: true,
    });
  }

  return (
    <SortableList list={list} listUpdater={updateSortableList}>
      {blocks.map((listItem) => {
        const { item: block, id } = listItem;
        const { description, slug, name } = block;
        return (
          <SortableListItem showDragHandle={slug !== currentExpandedBlockKey} key={id} id={id}>
            {slug !== currentExpandedBlockKey ? (
              <ListItemCard
                isHoverable={true}
                onItemClick={() => handleCustomBlockClick(block)}
                id={id}
                isDraggable={true}
                showDragIcon={true}
                accessibilityLabel={slug}
                Title={
                  <Text size="medium" weight="medium" color="surface.text.gray.normal">
                    {name}
                  </Text>
                }
                Description={
                  <Text size="small" weight="regular" color="interactive.text.positive.normal">
                    {description}
                  </Text>
                }
                RightComponent={
                  <IconButton
                    icon={ChevronDownIcon}
                    size="large"
                    onClick={() => handleShowCustomBlockDetails(block)}
                    accessibilityLabel="Edit Custom Block"
                  />
                }
              />
            ) : (
              <CustomPaymentBlockForm blockKey={slug} isNew={slug === newBlockKey} />
            )}
          </SortableListItem>
        );
      })}
    </SortableList>
  );
}
