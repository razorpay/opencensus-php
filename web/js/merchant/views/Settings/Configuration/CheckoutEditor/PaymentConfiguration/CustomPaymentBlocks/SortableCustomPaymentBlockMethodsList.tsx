import React from 'react';
import { IconButton, Switch, Text, TrashIcon } from '@razorpay/blade/components';
import isEqual from 'lodash/isEqual';
import {
  PaymentConfigCustomBlock,
  PaymentConfigInstrument,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/index';

import { getSingleInstrumentBlockDetails } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/AddSingleInstrument/helpers';
import { CustomPaymentBlockMethod } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/CustomPaymentBlockForm';
import { ListItemCard } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/ListItemCard';
import {
  SortableList,
  SortableListItemData,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/components/SortableList';
import { SortableListItem } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/SortableListItem';
import { StyledButton } from 'merchant/views/Settings/Configuration/CheckoutEditor/components/styles';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';
import { nonNullable } from 'merchant/views/Settings/Configuration/CheckoutEditor/helpers/index';
import { PREVIEW_SCREEN } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';
import _track from './track';

export function SortableCustomPaymentBlockMethodsList({
  blockKey,
  list,
  onUpdateSortList,
  handleOpenModal,
}: {
  blockKey: string;
  list: SortableListItemData<CustomPaymentBlockMethod>[];
  onUpdateSortList: (updatedSequence: string[]) => void;
  handleOpenModal: (slug: string) => void;
}) {
  const blocks = list;
  const {
    values,
    handleSelectedConfigChange,
    handleSelectedPaymentOptionChange,
    handlePreviewScreenChange,
  } = useCheckoutEditor();
  const selectedConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];

  function findSingleInstrumentBlock(
    method: string,
    code: string,
    instruments: PaymentConfigInstrument[],
  ) {
    const singleMatchingInstrument = instruments.find((instrument) => {
      const { isSingleInstrument, code: instrumentCode } =
        getSingleInstrumentBlockDetails(instrument);
      return method === instrument.method && isSingleInstrument && code === instrumentCode;
    });
    return singleMatchingInstrument ?? null;
  }

  function updateCustomBlockMethodsSequence(updatedCustomBlockMethodsSequence: string[]) {
    const oldBlock = selectedConfig.checkout_config?.display?.blocks?.[blockKey] ?? {
      name: '',
      instruments: [],
    };
    const oldBlockInstruments = oldBlock.instruments;
    const newBlockInstruments = updatedCustomBlockMethodsSequence
      .map((method) => {
        const [methodName, code = ''] = method.split('-');
        if (code) {
          // if code is there, then it surely is a single instrument
          return findSingleInstrumentBlock(methodName, code, oldBlockInstruments);
        } else {
          // if code is not there, then select a non single instrument
          return (
            oldBlockInstruments.find((instrument) => {
              const { isSingleInstrument } = getSingleInstrumentBlockDetails(instrument);
              return !isSingleInstrument && instrument.method === method;
            }) ?? null
          );
        }
      })
      .filter(nonNullable);

    const updatedBlock: PaymentConfigCustomBlock = {
      ...oldBlock,
      instruments: newBlockInstruments,
    };
    handleSelectedConfigChange({
      ...selectedConfig,
      checkout_config: {
        ...selectedConfig.checkout_config,
        display: {
          ...selectedConfig?.checkout_config?.display,
          blocks: {
            ...selectedConfig?.checkout_config?.display?.blocks,
            [blockKey]: updatedBlock,
          },
        },
      },
    });

    handleSelectedPaymentOptionChange({
      name: updatedBlock.name,
      isCustomBlock: true,
    });
  }

  function updateSortableList(sortedBlocks: SortableListItemData<CustomPaymentBlockMethod>[]) {
    const newCustomBlockMethodsSequence = sortedBlocks.map((block) => block.item.slug);
    const oldCustomBlockMethodsSequence = list.map((block) => block.item.slug);
    updateCustomBlockMethodsSequence(newCustomBlockMethodsSequence);
    onUpdateSortList(newCustomBlockMethodsSequence);
    _track.customPaymentBlockMethodReordered(
      oldCustomBlockMethodsSequence,
      newCustomBlockMethodsSequence,
    );
  }

  function handleCustomBlockMethodVisibilityChange(method: string, isVisible: boolean) {
    const oldBlock = selectedConfig.checkout_config?.display?.blocks?.[blockKey] ?? {
      name: '',
      instruments: [],
    };
    const oldBlockInstruments = oldBlock.instruments;
    if (isVisible) {
      const instrument: PaymentConfigInstrument = {
        method,
      };
      const instrumentsToBeAdded = [instrument];

      // in custom blocks of checkout, emi and cardless_emi are separate
      // but as per our logic, emi and cardless_emi should come and go together
      if (method === 'emi') {
        const cardlessEMIInstrument: PaymentConfigInstrument = {
          method: 'cardless_emi',
        };
        instrumentsToBeAdded.push(cardlessEMIInstrument);
      }
      handleSelectedConfigChange({
        ...selectedConfig,
        checkout_config: {
          ...selectedConfig.checkout_config,
          display: {
            ...selectedConfig?.checkout_config?.display,
            blocks: {
              ...selectedConfig?.checkout_config?.display?.blocks,
              [blockKey]: {
                ...oldBlock,
                instruments: [...oldBlockInstruments, ...instrumentsToBeAdded],
              },
            },
          },
        },
      });
      handleSelectedPaymentOptionChange({
        name: oldBlock.name,
        isCustomBlock: true,
      });
      handlePreviewScreenChange(PREVIEW_SCREEN.METHODS);
    } else {
      let newBlockInstruments = oldBlockInstruments.filter(
        (instrument) => instrument.method !== method,
      );

      // in custom blocks of checkout, emi and cardless_emi are separate
      // but as per our logic, emi and cardless_emi should come and go together
      if (method === 'emi') {
        newBlockInstruments = newBlockInstruments.filter(
          (instrument) => instrument.method !== 'cardless_emi',
        );
      }
      const newBlock: PaymentConfigCustomBlock = {
        ...oldBlock,
        instruments: newBlockInstruments,
      };
      handleSelectedConfigChange({
        ...selectedConfig,
        checkout_config: {
          ...selectedConfig.checkout_config,
          display: {
            ...selectedConfig?.checkout_config?.display,
            blocks: {
              ...selectedConfig?.checkout_config?.display?.blocks,
              [blockKey]: newBlock,
            },
          },
        },
      });
    }
    _track.customPaymentBlockMethodVisibilityToggled(oldBlock, {
      methodName: method,
      isVisible,
    });
  }

  function handleCustomBlockMethodClick(block) {
    const oldBlock = selectedConfig.checkout_config?.display?.blocks?.[blockKey] ?? {
      name: '',
      instruments: [],
    };
    const { slug, isSingleInstrument } = block;
    if (!isSingleInstrument) {
      handleOpenModal(slug);
    }
    handleSelectedPaymentOptionChange({
      name: oldBlock.name,
      isCustomBlock: true,
    });
    handlePreviewScreenChange(PREVIEW_SCREEN.METHODS);
    _track.customPaymentBlockMethodClicked(block);
  }

  function removeSingleInstrument(slug: string) {
    const oldBlock = selectedConfig.checkout_config?.display?.blocks?.[blockKey] ?? {
      name: '',
      instruments: [],
    };
    const oldBlockInstruments = oldBlock.instruments;
    const [method, code] = slug.split('-');

    const singleMatchingInstrument = findSingleInstrumentBlock(method, code, oldBlockInstruments);

    const newBlockInstruments = oldBlockInstruments.filter(
      (instrument) => !isEqual(instrument, singleMatchingInstrument),
    );

    const newBlock: PaymentConfigCustomBlock = {
      ...oldBlock,
      instruments: newBlockInstruments,
    };
    handleSelectedConfigChange({
      ...selectedConfig,
      checkout_config: {
        ...selectedConfig.checkout_config,
        display: {
          ...selectedConfig?.checkout_config?.display,
          blocks: {
            ...selectedConfig?.checkout_config?.display?.blocks,
            [blockKey]: newBlock,
          },
        },
      },
    });
    _track.customPaymentBlockSingleInstrumentRemoved(singleMatchingInstrument);
  }
  return (
    <SortableList list={list} listUpdater={updateSortableList}>
      {blocks.map((listItem) => {
        const { item: block, id } = listItem;
        const { isVisible, description, slug, name, isSingleInstrument } = block;
        return (
          <SortableListItem showDragHandle={true} key={id} id={id}>
            <ListItemCard
              onItemClick={() => handleCustomBlockMethodClick(block)}
              isHoverable={true}
              id={id}
              isDraggable={isVisible}
              showDragIcon={true}
              accessibilityLabel={slug}
              backgroundColor="surface.background.gray.moderate"
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
                    isVisible ? 'interactive.text.positive.normal' : 'surface.text.gray.muted'
                  }
                >
                  {description}
                </Text>
              }
              RightComponent={
                <StyledButton>
                  {isSingleInstrument ? (
                    <IconButton
                      icon={TrashIcon}
                      accessibilityLabel="delete single instrument"
                      onClick={() => removeSingleInstrument(slug)}
                    />
                  ) : (
                    <Switch
                      accessibilityLabel={`toggle-${slug}`}
                      size="small"
                      value={slug}
                      isChecked={isVisible}
                      onChange={(value) => {
                        handleCustomBlockMethodVisibilityChange(slug, value.isChecked);
                        if (value.isChecked) {
                          handleCustomBlockMethodClick(block);
                        }
                      }}
                    />
                  )}
                </StyledButton>
              }
            />
          </SortableListItem>
        );
      })}
    </SortableList>
  );
}
