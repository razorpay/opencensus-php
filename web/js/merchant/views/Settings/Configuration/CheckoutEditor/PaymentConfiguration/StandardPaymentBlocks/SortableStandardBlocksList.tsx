import React from 'react';
import { Switch, Text } from '@razorpay/blade/components';
import { PaymentConfigInstrument } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/index';

import { ListItemCard } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/ListItemCard';
import {
  SortableList,
  SortableListItemData,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/components/SortableList';
import { SortableListItem } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/SortableListItem';
import { StyledButton } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/styles';
import {
  DEFAULT_PAYMENT_CONFIG,
  PREVIEW_SCREEN,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import { StandardPaymentBlock } from './StandardPaymentBlocksList';

export function SortableStandardBlocksList({
  list,
  onUpdateSortList,
}: {
  list: SortableListItemData<StandardPaymentBlock>[];
  onUpdateSortList: (updatedSequence: string[]) => void;
}) {
  const {
    values,
    handleSelectedConfigChange,
    handleSelectedPaymentOptionChange,
    handlePreviewScreenChange,
  } = useCheckoutEditor();
  const selectedConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
  const sequence = selectedConfig.checkout_config?.display?.sequence ?? [];
  const isRazorpayConfig = selectedConfig?.config_id === DEFAULT_PAYMENT_CONFIG.config_id;

  function handleBlockVisibilityChange(
    slug: string,
    isVisible: boolean,
    itemAllBlocksIndex: number,
  ) {
    const hiddenBlocks = list
      .filter((listItem) => listItem.item.isVisible === false)
      .map((listItem) => {
        return {
          method: listItem.item.slug,
        } as PaymentConfigInstrument;
      });
    if (isVisible) {
      // remove from hidden blocks if present
      // make show_default_blocks true
      let updatedHiddenBlocks = hiddenBlocks.filter((block) => block.method !== slug);
      if (slug === 'emi') {
        updatedHiddenBlocks = updatedHiddenBlocks.filter(
          (block) => block.method !== 'cardless_emi',
        );
      }

      // add block into sequence at appropriate index
      const oldStandardBlocksSequence = list
        .filter((listItem) => listItem.item.isVisible)
        .map((listItem) => listItem.item.slug);
      // find where to insert in visible array
      const insertIndex = oldStandardBlocksSequence.findIndex((visibleItem) => {
        const visibleItemAllBlocksIndex = list.findIndex(
          (allItem) => allItem.item.slug === visibleItem,
        );
        return visibleItemAllBlocksIndex > itemAllBlocksIndex;
      });
      // If no position found all existing visible items come before, append to end
      const finalInsertIndex = insertIndex === -1 ? oldStandardBlocksSequence.length : insertIndex;
      // Create new sequence with item inserted at correct position
      const updatedStandardBlocksSequence = [
        ...oldStandardBlocksSequence.slice(0, finalInsertIndex),
        slug,
        ...oldStandardBlocksSequence.slice(finalInsertIndex),
      ];

      const oldSequence = selectedConfig.checkout_config?.display?.sequence ?? [];
      const customBlocksSequence = oldSequence.filter((block) => block.includes('block.'));

      const updatedSequence = [...customBlocksSequence, ...updatedStandardBlocksSequence];

      handleSelectedConfigChange({
        ...selectedConfig,
        checkout_config: {
          ...selectedConfig.checkout_config,
          display: {
            ...selectedConfig?.checkout_config?.display,
            hide: updatedHiddenBlocks,
            sequence: updatedSequence,
            preferences: {
              show_default_blocks: true,
            },
          },
        },
      });

      // select this method in checkout
      handleSelectedPaymentOptionChange({
        name: slug,
        isCustomBlock: false,
      });
      handlePreviewScreenChange(PREVIEW_SCREEN.METHODS);
    } else {
      // add to hidden blocks
      hiddenBlocks.push({ method: slug });

      // remove from sequence if present
      const sequence = selectedConfig.checkout_config?.display?.sequence ?? [];
      const updatedSequence = sequence.filter((blockName) => blockName !== slug);
      handleSelectedConfigChange({
        ...selectedConfig,
        checkout_config: {
          ...selectedConfig.checkout_config,
          display: {
            ...selectedConfig?.checkout_config?.display,
            hide: hiddenBlocks,
            sequence: updatedSequence,
            preferences: {
              show_default_blocks: !(hiddenBlocks.length === list.length),
            },
          },
        },
      });

      // remove that method from prefill as there is a bug in mweb view in checkout
      // if method is hidden but in prefill, then mweb still shows that method
      // adding home so as to reset the prefill
      handleSelectedPaymentOptionChange({
        name: 'home',
        isCustomBlock: false,
      });
    }
  }

  function updateStandardBlocksSequence(updatedStandardBlocksSequence: string[]) {
    const customBlocksSequence = sequence.filter((block) => block.includes('block.'));
    const updatedSequence = [...customBlocksSequence, ...updatedStandardBlocksSequence];
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

  function updateSortableList(blocks: SortableListItemData<StandardPaymentBlock>[]) {
    const updatedStandardBlocksSequence = blocks.map((block) => block.item.slug);
    const visibleBlocksSequence = blocks
      .filter((block) => block.item.isVisible)
      .map((block) => block.item.slug);
    updateStandardBlocksSequence(visibleBlocksSequence);
    onUpdateSortList(updatedStandardBlocksSequence);
  }

  function selectPaymentMethod(method: string, shouldOpenPreview: boolean) {
    handlePreviewScreenChange(PREVIEW_SCREEN.METHODS);
    if (shouldOpenPreview) {
      handleSelectedPaymentOptionChange({
        name: method,
        isCustomBlock: false,
      });
    }
  }

  return (
    <SortableList list={list} listUpdater={updateSortableList}>
      {list.map((listItem, itemIndex) => {
        const { item: block, id } = listItem;
        const { isVisible, description, slug, name } = block;
        return (
          <SortableListItem showDragHandle={true} key={id} id={id}>
            <ListItemCard
              onItemClick={() => selectPaymentMethod(block.slug, isVisible)}
              id={id}
              isHoverable={true}
              isDraggable={isVisible}
              showDragIcon={!isRazorpayConfig}
              accessibilityLabel={slug}
              backgroundColor={
                isRazorpayConfig
                  ? 'surface.background.gray.moderate'
                  : 'surface.background.gray.intense'
              }
              Title={
                <Text size="medium" weight="medium" color="surface.text.gray.normal">
                  {name}
                </Text>
              }
              Description={
                <Text
                  size="small"
                  weight="regular"
                  color={
                    isRazorpayConfig
                      ? 'surface.text.gray.subtle'
                      : isVisible
                      ? 'interactive.text.positive.normal'
                      : 'surface.text.gray.muted'
                  }
                >
                  {description}
                </Text>
              }
              RightComponent={
                isRazorpayConfig ? null : (
                  <StyledButton>
                    <Switch
                      accessibilityLabel={`toggle-${slug}`}
                      size="small"
                      value={slug}
                      isChecked={isVisible}
                      onChange={(value) => {
                        handleBlockVisibilityChange(slug, value.isChecked, itemIndex);
                      }}
                    />
                  </StyledButton>
                )
              }
            />
          </SortableListItem>
        );
      })}
    </SortableList>
  );
}
