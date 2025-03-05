import React from 'react';
import { Box, Link, Text } from '@razorpay/blade/components';

import { isMethodOnlyBlock } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/block';
import { DEFAULT_STANDARD_BLOCKS_SEQUENCE } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';
import _track from './track';

export type StandardPaymentBlocksHeaderProps = {
  isAllBlocksVisible: boolean;
};

export function StandardPaymentBlocksHeader({
  isAllBlocksVisible,
}: StandardPaymentBlocksHeaderProps) {
  const { values, handleSelectedConfigChange } = useCheckoutEditor();
  const selectedConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];

  function handleToggleAllBlocksActivation(isAllBlocksVisible: boolean) {
    if (isAllBlocksVisible) {
      // remove all standard blocks from sequence
      // add all standard blocks to hide
      // make show_default_blocks false
      const updatedSequence =
        selectedConfig.checkout_config?.display?.sequence?.filter((block) =>
          block.includes('block.'),
        ) ?? [];
      const hiddenBlocks = DEFAULT_STANDARD_BLOCKS_SEQUENCE.map((blockName) => {
        return { method: blockName };
      });
      handleSelectedConfigChange({
        ...selectedConfig,
        checkout_config: {
          ...selectedConfig.checkout_config,
          display: {
            ...selectedConfig.checkout_config?.display,
            hide: hiddenBlocks,
            sequence: updatedSequence,
            preferences: {
              show_default_blocks: false,
            },
          },
        },
      });
      _track.hideAllClicked();
    } else {
      // remove all method-only blocks from hide
      // we won't remove partial method blocks from hide (eg. {method: 'netbanking', banks: ['HDFC']} won't be removed)
      // make show_default_blocks true
      // remove all standard blocks from sequence
      const hiddenBlocks = selectedConfig.checkout_config?.display?.hide;
      const updatedHiddenBlocks = hiddenBlocks?.filter((block) => !isMethodOnlyBlock(block));
      handleSelectedConfigChange({
        ...selectedConfig,
        checkout_config: {
          ...selectedConfig.checkout_config,
          display: {
            ...selectedConfig.checkout_config?.display,
            hide: updatedHiddenBlocks,
            preferences: {
              show_default_blocks: true,
            },
          },
        },
      });
      _track.enableAllClicked();
    }
  }

  const title = 'All standard payment blocks';
  const description = 'Enable payment blocks like cards, UPI, etc';

  return (
    <Box display="flex" flexDirection="column" gap="spacing.6">
      <Box display="flex">
        <Box display="flex" flexDirection="column" flexGrow="1">
          <Text variant="body" size="medium" weight="medium" color="surface.text.gray.normal">
            {title}
          </Text>
          <Text variant="body" size="small" weight="regular" color="surface.text.gray.muted">
            {description}
          </Text>
        </Box>
        <Link
          variant="button"
          onClick={() => handleToggleAllBlocksActivation(isAllBlocksVisible)}
          iconPosition="right"
        >
          {isAllBlocksVisible ? 'Disable all' : 'Enable all'}
        </Link>
      </Box>
    </Box>
  );
}
