import React, { useState } from 'react';
import { Box } from '@razorpay/blade/components';
import {
  MerchantCheckoutPaymentConfig,
  PaymentConfigInstrument,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/index';
import { v4 as uuid } from 'uuid';

import CreateCustomPaymentBlockHeader from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/CreateCustomPaymentBlockHeader';
import { CustomPaymentBlocksList } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/CustomPaymentBlocksList';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';
import { PREVIEW_SCREEN } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import {
  NEW_CUSTOM_BLOCK_INSTRUMENTS,
  NEW_CUSTOM_BLOCK_NAME,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/constants/custom_block';
import { getCustomBlockDescription } from './utils';
import _track from './track';

export type CustomPaymentBlock = {
  slug: string;
  name: string;
  description: string;
  instruments: PaymentConfigInstrument[];
};

function parseCustomBlocks(config: MerchantCheckoutPaymentConfig, sequence: string[]) {
  const customBlocksMap = config.checkout_config?.display?.blocks ?? {};
  const customBlocksInSequence = sequence
    .filter((block) => block.includes('block.'))
    .map((block) => block.replace('block.', ''));
  const customBlocksList = customBlocksInSequence.map((blockId) => {
    return {
      slug: blockId,
      name: customBlocksMap[blockId]?.name,
      description: getCustomBlockDescription(customBlocksMap[blockId]?.instruments ?? []),
      instruments: customBlocksMap[blockId]?.instruments,
    };
  });
  return customBlocksList;
}

export function CustomPaymentBlocks() {
  const {
    values,
    handleCurrentExpandedCustomBlockChange,
    handleSelectedConfigChange,
    handleSelectedPaymentOptionChange,
    handlePreviewScreenChange,
  } = useCheckoutEditor();

  const [newBlockKey, setNewBlockKey] = useState('');

  const selectedConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
  const sequence = selectedConfig?.checkout_config?.display?.sequence ?? [];

  const customBlocksList = parseCustomBlocks(selectedConfig, sequence);

  function handleCreateNewCustomBlock() {
    const newBlockKey = uuid();
    const updatedSequence = [`block.${newBlockKey}`, ...sequence];
    handleSelectedConfigChange({
      ...selectedConfig,
      checkout_config: {
        ...selectedConfig.checkout_config,
        display: {
          ...selectedConfig.checkout_config?.display,
          blocks: {
            [newBlockKey]: {
              name: NEW_CUSTOM_BLOCK_NAME,
              instruments: NEW_CUSTOM_BLOCK_INSTRUMENTS,
            },
            ...selectedConfig.checkout_config?.display?.blocks,
          },
          sequence: updatedSequence,
        },
      },
    });
    handleCurrentExpandedCustomBlockChange(newBlockKey);
    handleSelectedPaymentOptionChange({
      name: NEW_CUSTOM_BLOCK_NAME,
      isCustomBlock: true,
    });
    handlePreviewScreenChange(PREVIEW_SCREEN.METHODS);
    setNewBlockKey(newBlockKey);
    _track.createNewCustomBlockClicked();
  }

  return (
    <Box
      display="flex"
      padding="spacing.5"
      flexDirection="column"
      gap="spacing.4"
      backgroundColor="surface.background.gray.moderate"
      borderRadius="large"
    >
      <CreateCustomPaymentBlockHeader
        handleCreateNewCustomBlock={handleCreateNewCustomBlock}
        hasMultipleCustomBlocks={customBlocksList.length > 0}
      />
      {customBlocksList.length > 0 && (
        <CustomPaymentBlocksList list={customBlocksList} newBlockKey={newBlockKey} />
      )}
    </Box>
  );
}
