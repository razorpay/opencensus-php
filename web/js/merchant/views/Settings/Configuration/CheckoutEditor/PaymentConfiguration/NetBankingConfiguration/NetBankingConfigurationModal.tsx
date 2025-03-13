import {
  Box,
  Button,
  Chip,
  ChipGroup,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  SearchInput,
} from '@razorpay/blade/components';
import isEqual from 'lodash/isEqual';
import React, { useState, useEffect } from 'react';
import NetBankingListItem from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/NetBankingConfiguration/NetBankingListItem';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { getNetbanking } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/helpers/methods';
import { PaymentConfigInstrument } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types';

export default function NetBankingConfigurationModal({
  isOpen,
  onClose,
  blockName,
}: {
  isOpen: boolean;
  onClose: () => void;
  blockName: string;
}) {
  const { values, handleSelectedConfigChange } = useCheckoutEditor();
  const allMethodDetails = values[CHECKOUT_EDITOR_FIELDS.ALL_PAYMENT_METHOD_DETAILS];
  const selectedPaymentConfig = values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG];
  const currentConfig =
    selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockName]?.instruments || [];
  const { banks }: { banks: { code: string; name: any; type?: string }[] } =
    getNetbanking(allMethodDetails);

  banks.forEach((item) => {
    item.type = item.code.includes('_C') ? 'corporate' : 'retail';
  });

  const initialObjects: PaymentConfigInstrument[] = currentConfig.filter(
    (item) => item.method === 'netbanking',
  );
  let initialObject = {
    banks: [],
    method: 'netbanking',
  } as PaymentConfigInstrument;

  if (initialObjects.length > 1) {
    initialObject =
      initialObjects.find(
        (item) => (item?.banks?.length ?? 0) === 0 || (item?.banks?.length ?? 0) > 1,
      ) || initialObject;
    initialObject.banks = initialObject.banks || [];
  } else if (initialObjects.length === 1) {
    initialObject = initialObjects[0];
  }
  const isInitialConfigExists =
    currentConfig.filter((item) => item.method === 'netbanking').length > 0;
  const initialFinalCardConfigurationObj = {
    ...initialObject,
    banks:
      (!initialObject?.banks || initialObject.banks.length === 0) && isInitialConfigExists
        ? banks.map((provider) => provider.code)
        : initialObject.banks,
  };
  const [isEnableSaveButton, setIsEnableSaveButton] = useState(false);
  const [prevNetbankingConfig, setPrevNetbankingConfig] = useState(initialFinalCardConfigurationObj);
  const [activeBanks, setActiveBanks] = useState(initialFinalCardConfigurationObj);
  if (!isEqual(prevNetbankingConfig, initialFinalCardConfigurationObj)) {
    setActiveBanks(initialFinalCardConfigurationObj);
    setPrevNetbankingConfig(initialFinalCardConfigurationObj);
  }
  const [filteredBanks, setFilteredBanks] = useState(banks);
  const [searchFilter, setSearchFilter] = useState<string[]>([]);
  const [searchValue, setSearchValue] = useState<string>('');

  // @HarshLileshShah remove this useEffect that you have added
  useEffect(() => {
    setIsEnableSaveButton(!isEqual(initialObject, activeBanks));
  }, [activeBanks, initialObject]);

  const handleSearch = (searchQuery: string) => {
    const filtered = banks.filter((bank) => {
      const isBankTypesMatch = searchFilter.length === 0 || searchFilter.includes(bank?.type || '');
      return (
        (bank.name.toLowerCase().includes(searchQuery) ||
          bank.code.toLowerCase().includes(searchQuery)) &&
        isBankTypesMatch
      );
    });
    setFilteredBanks(filtered);
  };

  // @HarshLileshShah remove this useEffect that you have added
  useEffect(() => {
    handleSearch(searchValue.toLowerCase());
  }, [searchFilter, searchValue]);

  const handleSave = () => {
    const updatedInstruments = [...currentConfig];
    const existingIndex = updatedInstruments.findIndex(
      (instrument) =>
        instrument.method === activeBanks.method &&
        (initialObjects.length > 1
          ? (instrument?.banks?.length ?? 0) === 0 || (instrument?.banks?.length ?? 0) > 1
          : true),
    );

    if (existingIndex !== -1) {
      updatedInstruments[existingIndex] = activeBanks;
    } else {
      updatedInstruments.push(activeBanks);
    }

    const blocks = {
      ...selectedPaymentConfig?.checkout_config?.display?.blocks,
      [blockName]: {
        ...(selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockName] || {}),
        name: selectedPaymentConfig?.checkout_config?.display?.blocks?.[blockName]?.name || '',
        instruments: updatedInstruments,
      },
    };

    handleSelectedConfigChange({
      ...selectedPaymentConfig,
      checkout_config: {
        ...selectedPaymentConfig.checkout_config,
        display: {
          ...selectedPaymentConfig?.checkout_config?.display,
          blocks,
        },
      },
    });
    onClose();
  };

  return (
    <Modal isOpen={isOpen} onDismiss={onClose} size="medium">
      <ModalHeader title="Netbanking" subtitle="Retail and corporate banks" />
      <ModalBody padding="spacing.0">
        <Box paddingY="spacing.6" paddingX="spacing.9">
          <Box position="relative">
            <SearchInput
              label=""
              labelPosition="top"
              name="search"
              onChange={(event) => setSearchValue(event.value || '')}
              onClearButtonClick={() => setSearchValue('')}
              placeholder="Search for banks"
              size="large"
            />
            <ChipGroup
              label=""
              position="absolute"
              top="spacing.3"
              right="spacing.7"
              selectionType="multiple"
              onChange={({ values }) => setSearchFilter(values)}
            >
              <Chip value="retail">Retail banks</Chip>
              <Chip value="corporate">Corporate banks</Chip>
            </ChipGroup>
          </Box>
          <Box gap="spacing.5" display="flex" flexDirection="column" paddingTop="spacing.7">
            {filteredBanks.map((item, index) => (
              <Box testID="ListItemCard" key={index}>
                <NetBankingListItem
                  item={item}
                  activeBanks={activeBanks}
                  setActiveBanks={setActiveBanks}
                />
              </Box>
            ))}
          </Box>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button variant="tertiary" onClick={onClose}>
            Cancel
          </Button>
          <Button isDisabled={!isEnableSaveButton} onClick={handleSave}>
            Save
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
}
