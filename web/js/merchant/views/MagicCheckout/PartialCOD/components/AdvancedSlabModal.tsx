import React, { useEffect, useState } from 'react';
import {
  ActionList,
  ActionListItem,
  Box,
  Button,
  Dropdown,
  DropdownOverlay,
  Modal,
  ModalBody,
  ModalFooter,
  ModalHeader,
  PercentIcon,
  RupeeIcon,
  SelectInput,
  Text,
  TextInput,
} from '@razorpay/blade/components';
import { validateCreateAdvancedSlab } from 'merchant/views/MagicCheckout/PartialCOD/helpers/validations';
import { NEW_PREPAID_AMOUNT_ITEM } from 'merchant/views/MagicCheckout/PartialCOD/constants';
import {
  AdvancedSlabModalDataType,
  AdvancedSlabModalProps,
  PREPAID_PAYMENY_AMOUNT_ITEM_TYPE,
  PrepaidPaymentAmountItem,
  PrepaidPaymentAmountItemType,
} from 'merchant/views/MagicCheckout/PartialCOD/types';
import RiskCategoryDropdown from 'merchant/views/MagicCheckout/PartialCOD/components/RiskCategoryDropdown';
import { i18nifyConvertToMinorUnit } from 'merchant/views/Transactions/v2/common/utils';
import { initializeSlabData } from '../helpers/utils';

const AdvancedSlabModal: React.FC<AdvancedSlabModalProps> = ({
  configData = NEW_PREPAID_AMOUNT_ITEM,
  onConfirm = () => {},
  isOpen = false,
  onClose = () => {},
}) => {
  const [slabData, setSlabData] = useState<AdvancedSlabModalDataType>(
    initializeSlabData(configData),
  );

  useEffect(() => {
    setSlabData(initializeSlabData(configData));
  }, [configData]);

  const [errors, setErrors] = useState({
    min_order_amount: '',
    max_order_amount: '',
    customer_risk_category: '',
    value: '',
  });
  const [loading, setLoading] = useState<boolean>(false);

  const updateSlabData = (field: keyof PrepaidPaymentAmountItem, value: any) => {
    setSlabData((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  const updateRulesData = (field: keyof typeof slabData.rules, value: any) => {
    setSlabData((prev) => ({
      ...prev,
      rules: {
        ...prev.rules,
        [field]: value,
      },
    }));
  };

  const handleConfirm = () => {
    const tConfigData: PrepaidPaymentAmountItem = {
      type: slabData.type,
      value:
        slabData.type === PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT
          ? i18nifyConvertToMinorUnit(Number(slabData.value))
          : Number(slabData.value),
      rules: {
        min_order_amount: i18nifyConvertToMinorUnit(Number(slabData.rules.min_order_amount)),
        max_order_amount: slabData.rules.max_order_amount
          ? i18nifyConvertToMinorUnit(Number(slabData.rules.max_order_amount))
          : undefined,
        customer_risk_category: slabData.rules.customer_risk_category,
      },
    };

    if (validateCreateAdvancedSlab(tConfigData, setErrors)) {
      setLoading(true);
      onConfirm(tConfigData, {
        onSuccess: () => {
          onClose();
          setSlabData(initializeSlabData(NEW_PREPAID_AMOUNT_ITEM));
        },
        onEnd: () => setLoading(false),
      });
    }
  };

  return (
    <Modal size="medium" isOpen={isOpen} onDismiss={onClose}>
      <ModalHeader title="New Partial COD Slab" />
      <ModalBody>
        <Box display="grid" gap="spacing.7" padding="spacing.4">
          <Box display="grid" gap="spacing.8">
            <Box display="flex" width="100%" alignItems="center">
              <Box width="120px" marginRight="spacing.4">
                <Text weight="semibold" color="surface.text.gray.subtle">
                  Rule
                </Text>
              </Box>
              <Box
                display="grid"
                gap="spacing.6"
                flexGrow={1}
                backgroundColor="surface.background.gray.subtle"
                padding="spacing.4"
                borderRadius="medium"
                gridTemplateColumns={{ base: '1fr', l: '1fr 1fr' }}
              >
                <Box display="flex" alignItems="center" gap="spacing.5" flexGrow={1}>
                  <Text weight="semibold" color="surface.text.gray.subtle">
                    Min ₹
                  </Text>
                  <Box flexGrow={1}>
                    <TextInput
                      label=""
                      name="min"
                      type="number"
                      value={slabData.rules.min_order_amount}
                      onChange={({ value }) => updateRulesData('min_order_amount', value)}
                      errorText={errors.min_order_amount}
                      validationState={errors.min_order_amount ? 'error' : 'none'}
                    />
                  </Box>
                </Box>
                <Box display="flex" alignItems="center" gap="spacing.5" flexGrow={1}>
                  <Text weight="semibold" color="surface.text.gray.subtle">
                    Max ₹
                  </Text>
                  <Box flexGrow={1}>
                    <TextInput
                      label=""
                      name="max"
                      labelPosition="left"
                      type="number"
                      value={slabData.rules.max_order_amount}
                      onChange={({ value }) => updateRulesData('max_order_amount', value)}
                      errorText={errors.max_order_amount}
                      validationState={errors.max_order_amount ? 'error' : 'none'}
                    />
                  </Box>
                </Box>
              </Box>
            </Box>
            <RiskCategoryDropdown
              label="Customer Risk"
              value={slabData.rules.customer_risk_category}
              onChange={(newRiskCategories) =>
                updateRulesData('customer_risk_category', newRiskCategories)
              }
              labelPosition="left"
              validationState={errors.customer_risk_category ? 'error' : 'none'}
            />

            <Box
              display="grid"
              width="100%"
              alignItems="center"
              gridTemplateColumns="120px 1fr"
              gap="spacing.4"
            >
              <Text weight="semibold" color="surface.text.gray.subtle">
                Amount to pay
              </Text>
              <Box display="grid" gridTemplateColumns="3fr 2fr" gap="spacing.6">
                <Dropdown selectionType="single">
                  <SelectInput
                    label=""
                    name="action"
                    defaultValue={PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE}
                    value={slabData.type}
                    onChange={({ values }) =>
                      updateSlabData('type', values[0] as PrepaidPaymentAmountItemType)
                    }
                  />
                  <DropdownOverlay>
                    <ActionList>
                      <ActionListItem
                        title="Percentage"
                        value={PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE}
                      />
                      <ActionListItem
                        title="Amount"
                        value={PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT}
                      />
                    </ActionList>
                  </DropdownOverlay>
                </Dropdown>
                <TextInput
                  label=""
                  leadingIcon={
                    slabData.type === PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT
                      ? RupeeIcon
                      : PercentIcon
                  }
                  name="value"
                  placeholder={`Enter ${
                    slabData.type === PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT
                      ? 'amount'
                      : 'percentage'
                  }`}
                  type="number"
                  value={slabData.value}
                  onChange={({ value }) => updateSlabData('value', value)}
                  errorText={errors.value}
                  validationState={errors.value ? 'error' : 'none'}
                />
              </Box>
            </Box>
          </Box>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end" gap="spacing.5">
          <Button variant="tertiary" onClick={onClose}>
            Cancel
          </Button>
          <Button variant="primary" onClick={handleConfirm} isLoading={loading}>
            Confirm
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default AdvancedSlabModal;
