import React, { useState } from 'react';
import { Box, ChevronDownIcon, ChevronUpIcon, Link } from '@razorpay/blade/components';

import { SortableListItemData } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/SortableList';

import { nonNullable } from 'merchant/views/Settings/Configuration/CheckoutEditor/helpers/index';
import { SortableStandardBlocksList } from './SortableStandardBlocksList';

export type StandardPaymentBlock = {
  slug: string;
  name: string;
  description: string;
  isVisible: boolean;
};

export type StandardPaymentBlocksListProps = {
  visibleBlocks: StandardPaymentBlock[];
  hiddenBlocks: StandardPaymentBlock[];
};

export function StandardPaymentBlocksList({
  visibleBlocks,
  hiddenBlocks,
}: StandardPaymentBlocksListProps) {
  const [shouldShowAllBlocks, setShouldShowAllBlocks] = useState(true);
  const allBlocks = [...visibleBlocks, ...hiddenBlocks];

  const displayBlocks = shouldShowAllBlocks
    ? allBlocks
    : visibleBlocks.length > 0 // if there are no visible blocks, then show some hidden blocks
    ? visibleBlocks
    : hiddenBlocks.slice(0, 2);

  const displayBlocksOrder = displayBlocks.map((block) => block.slug);
  const displayBlocksOrderMap = new Map(displayBlocks.map((value) => [value.slug, value]));

  const [displayBlocksOrderState, setDisplayBlocksOrderState] = useState(displayBlocksOrder);

  const sortableBlocksList: SortableListItemData<StandardPaymentBlock>[] = displayBlocksOrderState
    ?.map((block) => {
      const displayBlock = displayBlocksOrderMap.get(block);
      if (displayBlock) {
        return {
          item: displayBlock,
          id: `${displayBlock.slug}-${displayBlock.isVisible}`,
        };
      }
      return null;
    })
    .filter(nonNullable);

  const sortableBlocksListIDKey = sortableBlocksList.map((block) => block.id).join('-');

  function toggleSetShouldShowAllBlocks() {
    setShouldShowAllBlocks(!shouldShowAllBlocks);
  }

  function onUpdateSortOrder(updatedSequence: string[]) {
    setDisplayBlocksOrderState(updatedSequence);
  }

  return (
    <Box display="flex" flexDirection="column" gap="spacing.6">
      <Box display="flex" flexDirection="column" gap="spacing.4">
        <SortableStandardBlocksList
          key={sortableBlocksListIDKey}
          list={sortableBlocksList}
          allBlocks={allBlocks}
          onUpdateSortList={onUpdateSortOrder}
        />
      </Box>
      {hiddenBlocks.length > 0 && (
        <Box display="flex" justifyContent="flex-end">
          <Link
            variant="button"
            accessibilityLabel={shouldShowAllBlocks ? 'Hide' : 'Show all'}
            size="medium"
            icon={shouldShowAllBlocks ? ChevronUpIcon : ChevronDownIcon}
            iconPosition="right"
            onClick={toggleSetShouldShowAllBlocks}
          >
            {shouldShowAllBlocks ? 'Hide' : 'Show all'}
          </Link>
        </Box>
      )}
    </Box>
  );
}
