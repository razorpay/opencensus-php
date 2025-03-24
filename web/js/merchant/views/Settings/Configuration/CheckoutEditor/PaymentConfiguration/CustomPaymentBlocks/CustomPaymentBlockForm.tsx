import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  Box,
  ChevronUpIcon,
  IconButton,
  Link,
  MenuIcon,
  PlusIcon,
  Text,
  TextInput,
  TrashIcon,
} from '@razorpay/blade/components';
import { PaymentConfigCustomBlock } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types';

import { AddSingleInstrumentModal } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/AddSingleInstrument/AddSingleInstrumentModal';
import {
  getSingleInstrumentBlockDetails,
  getSingleInstrumentTitle,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/AddSingleInstrument/helpers';
import { CustomPaymentBlockMethodsList } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/CustomPaymentBlockMethodsList';
import { allMethodGetters } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/methods';
import { PointerDivWrapper } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/styled';
import { DEFAULT_STANDARD_BLOCKS_SEQUENCE } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';
import { nonNullable } from 'merchant/views/Settings/Configuration/CheckoutEditor/helpers/index';
import { DeleteBlockModal } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/DeleteBlockModal';
import debounce from 'common/utils/debounce';
import { CustomBlockBody } from './styled';
import { getCustomBlockDescription } from './utils';
import _track from './track';
import { CustomPaymentBlock } from './CustomPaymentBlocks';

export type CustomPaymentBlockMethod = {
  slug: string;
  name: string;
  description: string;
  isVisible: boolean;
  isSingleInstrument: boolean;
};

function sortAndFilterCustomBlockMethods(
  block: PaymentConfigCustomBlock | undefined,
  enabledInstruments: any[],
) {
  const instrumentsInBlock = block?.instruments ?? [];
  const visibleBlockMethods = instrumentsInBlock
    .map((instrument) => {
      const { isSingleInstrument, code: instrumentCode } =
        getSingleInstrumentBlockDetails(instrument);
      const methodName = instrument.method;
      const enabledInstrument = enabledInstruments.find(
        (enabledInstrument) => enabledInstrument.name === methodName,
      );
      if (enabledInstrument) {
        const { title, description } = enabledInstrument;
        return {
          slug: isSingleInstrument ? `${methodName}-${instrumentCode}` : methodName,
          name: isSingleInstrument
            ? getSingleInstrumentTitle(instrumentCode, enabledInstrument)
            : title,
          description: isSingleInstrument ? '' : description,
          isVisible: true,
          isSingleInstrument,
        };
      }
      return null;
    })
    .filter(nonNullable);

  const hiddenBlockMethods = DEFAULT_STANDARD_BLOCKS_SEQUENCE.map((blockName) => {
    const isBlockNameVisible = visibleBlockMethods?.find((block) => block.slug === blockName);
    if (isBlockNameVisible) {
      return null;
    } else {
      const enabledInstrument = enabledInstruments.find(
        (enabledInstrument) => enabledInstrument.name === blockName,
      );
      if (enabledInstrument) {
        const { title, description } = enabledInstrument;
        return {
          slug: blockName,
          name: title,
          description,
          isVisible: false,
          isSingleInstrument: false,
        };
      }
      return null;
    }
  }).filter(nonNullable);

  const customBlockMethods = [...visibleBlockMethods, ...hiddenBlockMethods];
  return customBlockMethods;
}

export type CustomPaymentBlockFormProps = {
  blockKey: string;
  isNew: boolean;
};

export function CustomPaymentBlockForm({ blockKey, isNew = false }: CustomPaymentBlockFormProps) {
  const {
    values,
    handleSelectedConfigChange,
    handleCurrentExpandedCustomBlockChange,
    handleSelectedPaymentOptionChange,
  } = useCheckoutEditor();
  const [isAddSingleInstrumentModalOpen, setIsAddSingleInstrumentModalOpen] = useState(false);
  const [isExpanded, setIsExpanded] = useState(false);
  const [isClosing, setIsClosing] = useState(false);
  const customBlockBodyRef = useRef<HTMLDivElement | null>(null);
  const customBlockFormRef = useRef<HTMLDivElement | null>(null);
  const selectedConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
  const block = selectedConfig.checkout_config?.display?.blocks?.[blockKey];
  const allMethodDetails = values[CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS];
  const [isOpenDeleteModal, setIsOpenDeleteModal] = useState(false);
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
  const customBlockMethods = sortAndFilterCustomBlockMethods(block, enabledInstruments);
  const customBlockMethodsListKey = customBlockMethods.length;
  const debouncedTrackCustomBlockRename = debounce(_track.customPaymentBlockRenamed, 1000);

  function handleBlockNameUpdate(value: string) {
    const updatedBlock = {
      instruments: block?.instruments ?? [],
      name: value,
    };
    handleSelectedConfigChange({
      ...selectedConfig,
      checkout_config: {
        ...selectedConfig.checkout_config,
        display: {
          ...selectedConfig.checkout_config?.display,
          blocks: {
            ...selectedConfig.checkout_config?.display?.blocks,
            [blockKey]: updatedBlock,
          },
        },
      },
    });
    handleSelectedPaymentOptionChange({
      name: value,
      isCustomBlock: true,
    });
    debouncedTrackCustomBlockRename(updatedBlock);
  }

  function handleDeleteCustomBlock(key: string) {
    const updatedBlocks = { ...selectedConfig.checkout_config?.display?.blocks };
    delete updatedBlocks[key];
    const oldSequence = selectedConfig?.checkout_config?.display?.sequence ?? [];
    const updatedSequence = oldSequence.filter((blockID) => blockID !== `block.${key}`);
    handleSelectedConfigChange({
      ...selectedConfig,
      checkout_config: {
        ...selectedConfig.checkout_config,
        display: {
          ...selectedConfig.checkout_config?.display,
          blocks: updatedBlocks,
          sequence: updatedSequence,
        },
      },
    });

    handleCurrentExpandedCustomBlockChange('');
    handleSelectedPaymentOptionChange({
      name: 'home',
      isCustomBlock: false,
    });
    setIsOpenDeleteModal(false);
    const customPaymentBlock: CustomPaymentBlock = {
      slug: key,
      name: block?.name ?? '',
      description: getCustomBlockDescription(block?.instruments ?? []),
      instruments: block?.instruments ?? [],
    };
    _track.customPaymentBlockDeleted(customPaymentBlock);
  }

  function handleCustomBlockFormClose() {
    setIsExpanded(false);
    setIsClosing(true);
    const customPaymentBlock: CustomPaymentBlock = {
      slug: blockKey,
      name: block?.name ?? '',
      description: getCustomBlockDescription(block?.instruments ?? []),
      instruments: block?.instruments ?? [],
    };
    _track.customPaymentBlockCollapsed(customPaymentBlock);
  }

  useEffect(() => {
    const blockExpandTimerID = setTimeout(() => {
      setIsExpanded(true);
    }, 0);

    const scrollIntoViewTimerID = setTimeout(() => {
      customBlockFormRef.current?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 500);

    return () => {
      clearTimeout(blockExpandTimerID);
      clearTimeout(scrollIntoViewTimerID);
    };
  }, []);

  useEffect(() => {
    let timerID;
    if (isClosing) {
      timerID = setTimeout(() => {
        handleCurrentExpandedCustomBlockChange('');
        handleSelectedPaymentOptionChange({
          name: 'home',
          isCustomBlock: false,
        });
      }, 400);
    }

    return () => clearTimeout(timerID);
  }, [isClosing, handleCurrentExpandedCustomBlockChange, handleSelectedPaymentOptionChange]);

  return (
    <>
      <Box
        backgroundColor="surface.background.gray.intense"
        paddingY="spacing.4"
        paddingX="spacing.5"
        borderRadius="large"
        display="flex"
        flexDirection="column"
        gap="spacing.7"
        borderWidth="thin"
        borderColor="surface.border.gray.muted"
      >
        <Box
          display="flex"
          borderBottomColor="surface.border.gray.subtle"
          borderBottomWidth="thin"
          paddingBottom="spacing.5"
          gap="spacing.4"
          alignItems="center"
        >
          {isNew ? (
            <Box flexGrow="1">
              <Text size="medium" weight="medium" color="surface.text.gray.normal">
                New payment block
              </Text>
            </Box>
          ) : (
            <Box display="flex" flexGrow="1" gap="spacing.4" alignItems="center">
              <MenuIcon color="surface.icon.gray.disabled" />
              <Box display="flex" flexGrow="1" flexDirection="column">
                <Text size="medium" weight="medium" color="surface.text.gray.normal">
                  {block?.name}
                </Text>
                <Text size="small" weight="regular" color="interactive.text.positive.normal">
                  {getCustomBlockDescription(block?.instruments ?? [])}
                </Text>
              </Box>
            </Box>
          )}
          <IconButton
            ref={customBlockFormRef}
            icon={ChevronUpIcon}
            size="large"
            accessibilityLabel="close custom block"
            onClick={handleCustomBlockFormClose}
          />
        </Box>

        <CustomBlockBody
          ref={customBlockBodyRef}
          height={isExpanded ? customBlockBodyRef?.current?.scrollHeight : '0'}
        >
          <Box display="flex" flexDirection="column" gap="spacing.7" paddingX="spacing.1">
            <Box display="flex" flexDirection="column" gap="spacing.4">
              <Text size="small" color='surface.text.gray.muted' weight='semibold'>Payment block name</Text>
              <TextInput
                accessibilityLabel="Payment block name"
                label=''
                helpText="Use 2-3 words only"
                value={block?.name ?? ''}
                isRequired
                onChange={({ value }) => {
                  handleBlockNameUpdate(value ?? '');
                }}
              />
            </Box>
            <Box display="flex" flexDirection="column" gap="spacing.4">
              <Text size="small" color='surface.text.gray.muted' weight='semibold'>Add payment method or instrument</Text>
              <PointerDivWrapper
                onClick={() => {
                  setIsAddSingleInstrumentModalOpen(true);
                }}
              >
                <Box
                  borderStyle="dashed"
                  paddingY="spacing.4"
                  paddingX="spacing.5"
                  borderRadius="large"
                  borderColor="surface.border.gray.subtle"
                  borderWidth="thin"
                  display="flex"
                  gap="spacing.4"
                >
                  <PlusIcon size="large" color="surface.icon.primary.normal" />
                  <Text size="medium" weight="medium" color="surface.text.primary.normal">
                    Add a single payment instrument
                  </Text>
                </Box>
              </PointerDivWrapper>
              <CustomPaymentBlockMethodsList
                blockKey={blockKey}
                list={customBlockMethods}
                key={customBlockMethodsListKey}
              />
            </Box>
            <Box display="flex" justifyContent="flex-end">
              <Link
                variant="button"
                color="negative"
                icon={TrashIcon}
                onClick={() => setIsOpenDeleteModal(true)}
              >
                Delete block
              </Link>
            </Box>
          </Box>
        </CustomBlockBody>
      </Box>
      <DeleteBlockModal
        isOpen={isOpenDeleteModal}
        onClose={() => setIsOpenDeleteModal(false)}
        blockName={block?.name}
        onDelete={() => handleDeleteCustomBlock(blockKey)}
      />
      {isAddSingleInstrumentModalOpen && (
        <AddSingleInstrumentModal
          onClose={() => {
            setIsAddSingleInstrumentModalOpen(false);
          }}
          blockID={blockKey}
        />
      )}
    </>
  );
}
