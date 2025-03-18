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
  Text,
} from '@razorpay/blade/components';
import isEqual from 'lodash/isEqual';
import React, { useState } from 'react';
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

  const initialNetbankingConfig: PaymentConfigInstrument[] = currentConfig.filter(
    (item) => item.method === 'netbanking'
  );

  const defaultConfig = { banks: [], method: 'netbanking' } as PaymentConfigInstrument;

  const initialConfig = initialNetbankingConfig.length > 1
    ? initialNetbankingConfig.find(
      (item) => (item?.banks?.length !== 1)
    ) || defaultConfig
    : initialNetbankingConfig[0] || defaultConfig;

  const isInitialConfigExists = initialNetbankingConfig.length > 0;

  const netbnkingModalConfig = {
    ...initialConfig,
    banks:
      // 1. when no netBanking config is provided => initialNetBankingConfig = defaultConfig.banks  
      // 2. when only non single netBanking config is provided => initialNetBankingConfig.banks  
      // 3. when only single netBanking config is provided => initialNetBankingConfig.banks  
      // 4. when single and multiple (no provider provided) exist => allBanks  
      // 5. when single and multiple (provider provided) exist => initialNetBankingConfig.banks 
      (!initialConfig?.banks || initialConfig.banks.length === 0) && isInitialConfigExists
        ? banks.map((provider) => provider.code)
        : initialConfig.banks,
  };
  const [updatedBankConfig, setUpdatedBankConfig] = useState(netbnkingModalConfig);
  const [prevNetbankingConfig, setPrevNetbankingConfig] = useState(netbnkingModalConfig);
  if (!isEqual(prevNetbankingConfig, netbnkingModalConfig)) {
    setUpdatedBankConfig(netbnkingModalConfig);
    setPrevNetbankingConfig(netbnkingModalConfig);
  }
  const [filteredBanks, setFilteredBanks] = useState(banks);
  const [searchFilter, setSearchFilter] = useState<string[]>([]);
  const [searchValue, setSearchValue] = useState<string>('');

  const isConfigChanged = !isEqual(initialConfig, updatedBankConfig)

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

  const handleSave = () => {
    const updatedInstruments = [...currentConfig];
    const existingIndex = updatedInstruments.findIndex(
      (instrument) =>
        instrument.method === updatedBankConfig.method &&
        (initialNetbankingConfig.length > 1
          ? (instrument?.banks?.length ?? 0) === 0 || (instrument?.banks?.length ?? 0) > 1
          : true),
    );

    if (existingIndex !== -1) {
      updatedInstruments[existingIndex] = updatedBankConfig;
    } else {
      updatedInstruments.push(updatedBankConfig);
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
              onChange={(event) => {
                const value = event.value || '';
                setSearchValue(value);
                handleSearch(value.toLowerCase());
              }}
              onClearButtonClick={() => {
                setSearchValue('');
                handleSearch('');
              }}
              placeholder="Search for banks"
              size="large"
            />
            <ChipGroup
              label=""
              position="absolute"
              top="spacing.3"
              right="spacing.7"
              selectionType="multiple"
              onChange={({ values }) => {
                setSearchFilter(values);
                handleSearch(searchValue.toLowerCase());
              }}
            >
              <Chip value="retail">Retail banks</Chip>
              <Chip value="corporate">Corporate banks</Chip>
            </ChipGroup>
          </Box>
          <Box gap="spacing.5" display="flex" flexDirection="column" paddingTop="spacing.7">
            {filteredBanks.length === 0 ? (
              <Box display="flex" justifyContent="center" paddingY="spacing.11">
                <Text weight="semibold" color="surface.text.gray.muted" size='large'>No results found</Text>
              </Box>
            ) :
              (filteredBanks.map((item, index) => (
                <Box testID="ListItemCard" key={index}>
                  <NetBankingListItem
                    item={item}
                    activeBanks={updatedBankConfig}
                    setActiveBanks={setUpdatedBankConfig}
                  />
                </Box>
              )))}
          </Box>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button variant="tertiary" onClick={onClose}>
            Cancel
          </Button>
          <Button isDisabled={!isConfigChanged} onClick={handleSave}>
            Save
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
}
