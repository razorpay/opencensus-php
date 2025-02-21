import React, { useMemo } from 'react';
import { Box } from '@razorpay/blade/components';
import { PaymentConfigInstrument } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/index';

import { isMethodOnlyBlock } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/block';
import { allMethodGetters } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/methods';
import { DEFAULT_STANDARD_BLOCKS_SEQUENCE } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';
import { nonNullable } from 'merchant/views/Settings/Configuration/CheckoutEditor/helpers/index';

import { StandardPaymentBlocksHeader } from './StandardPaymentBlocksHeader';
import { StandardPaymentBlocksList } from './StandardPaymentBlocksList';

function sortAndFilterStandardBlocks(
  sequence: string[],
  hiddenBlocks: Array<PaymentConfigInstrument> = [],
  showDefaultBlocks: boolean,
  enabledInstruments: any[],
) {
  const hiddenBlockMethodNames = hiddenBlocks
    .map((block) => {
      // only hide those blocks whose only key is method
      // eg : { method: 'upi', flows: ['qr']} should not be hidden
      // as only one flow is hidden. not the entire method
      if (isMethodOnlyBlock(block)) {
        return block.method;
      }
      return null;
    })
    .filter(nonNullable);

  // if showDefaultBlocks is false, then all standard blocks are hidden
  // else compute hidden blocks from hide array
  // always maintain the order of standard blocks as order of DEFAULT_STANDARD_BLOCKS_SEQUENCE
  const hiddenBlockNames = !showDefaultBlocks
    ? DEFAULT_STANDARD_BLOCKS_SEQUENCE
    : DEFAULT_STANDARD_BLOCKS_SEQUENCE.filter((blockName) =>
        hiddenBlockMethodNames.includes(blockName),
      );

  const visibleBlockNames = DEFAULT_STANDARD_BLOCKS_SEQUENCE.filter(
    (blockName) => !hiddenBlockNames.includes(blockName),
  );

  const standardPaymentBlockNamesInSequence = sequence.filter(
    (blockName) => !blockName.includes('block.'),
  );

  // order them such that blocks in sequence are visible first
  const orderedVisibleBlockNames = Array.from(
    new Set([...standardPaymentBlockNamesInSequence, ...visibleBlockNames]),
  );

  // only include filtered instruments that are in default sequence and not hidden
  const visibleBlockInstruments = orderedVisibleBlockNames
    .map((blockName) => {
      const block = enabledInstruments.find((instrument) => instrument.name === blockName);
      if (block) {
        return {
          slug: block.name,
          name: block.title,
          description: block.description,
          isVisible: true,
        };
      }
      return null;
    })
    .filter(nonNullable);

  // only include instruments that are hidden
  const hiddenBlockInstruments = hiddenBlockNames
    .map((blockName) => {
      const block = enabledInstruments.find((instrument) => instrument.name === blockName);
      if (block) {
        return {
          slug: block.name,
          name: block.title,
          description: block.description,
          isVisible: false,
        };
      }
      return null;
    })
    .filter(nonNullable);

  return {
    visibleBlockInstruments,
    hiddenBlockInstruments,
  };
}

export function StandardPaymentBlocks({
  isRazorpayConfigSelected,
}: {
  isRazorpayConfigSelected: boolean;
}) {
  const { values } = useCheckoutEditor();

  const selectedConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
  const sequence = selectedConfig?.checkout_config?.display?.sequence ?? [];
  const hiddenBlocks = selectedConfig?.checkout_config?.display?.hide ?? [];
  const shouldShowDefaultBlocks =
    selectedConfig?.checkout_config?.display?.preferences?.show_default_blocks ?? true;
  const allMethodDetails = values[CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS];

  const enabledInstruments = useMemo(() => {
    return allMethodGetters
      .map((methodGetter) => {
        const methodDetails = methodGetter(allMethodDetails);
        if (methodDetails.isEnabled) {
          return methodDetails;
        }
        return null;
      })
      .filter(nonNullable);
  }, [allMethodDetails]);

  const { visibleBlockInstruments, hiddenBlockInstruments } = sortAndFilterStandardBlocks(
    sequence,
    hiddenBlocks,
    shouldShowDefaultBlocks,
    enabledInstruments,
  );

  return isRazorpayConfigSelected ? (
    <Box display="flex" flexDirection="column" gap="spacing.4">
      <StandardPaymentBlocksList
        visibleBlocks={visibleBlockInstruments}
        hiddenBlocks={hiddenBlockInstruments}
      />
    </Box>
  ) : (
    <Box
      display="flex"
      padding="spacing.5"
      flexDirection="column"
      gap="spacing.4"
      backgroundColor="surface.background.gray.moderate"
      borderRadius="large"
    >
      <StandardPaymentBlocksHeader isAllBlocksVisible={hiddenBlockInstruments.length === 0} />
      <StandardPaymentBlocksList
        visibleBlocks={visibleBlockInstruments}
        hiddenBlocks={hiddenBlockInstruments}
      />
    </Box>
  );
}
