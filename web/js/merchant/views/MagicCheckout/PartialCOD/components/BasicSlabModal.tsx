import React, { useState } from 'react';
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
import { validateBasicSlab } from 'merchant/views/MagicCheckout/PartialCOD/helpers/validations';
import {
  BasicSlabModalProps,
  PREPAID_PAYMENY_AMOUNT_ITEM_TYPE,
  PrepaidPaymentAmountItemType,
} from 'merchant/views/MagicCheckout/PartialCOD/types';
import { i18nifyConvertToMinorUnit } from 'merchant/views/Transactions/v2/common/utils';

const BasicSlabModal: React.FC<BasicSlabModalProps> = ({ onSave, isOpen = false, onClose }) => {
  const [type, setType] = useState<PrepaidPaymentAmountItemType>(
    PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE,
  );
  const [value, setValue] = useState('');
  const [errors, setErrors] = useState({ type: '', value: '' });

  const handleSave = () => {
    if (validateBasicSlab(value, type, setErrors)) {
      onSave(
        type === PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT
          ? i18nifyConvertToMinorUnit(Number(value))
          : Number(value),
        type,
      );
      onClose();
    }
  };

  return (
    <Modal onDismiss={onClose} size="small" isOpen={isOpen}>
      <ModalHeader title="Set a custom value for partial COD" />
      <ModalBody padding="spacing.6">
        <Box display="grid" gap="spacing.6">
          <Dropdown selectionType="single">
            <SelectInput
              label="Type"
              name="action"
              onChange={({ values }) => setType(values[0] as PrepaidPaymentAmountItemType)}
              defaultValue={PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE}
              value={type}
            />
            <DropdownOverlay>
              <ActionList>
                <ActionListItem
                  title="Custom Percentage"
                  value={PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE}
                />
                <ActionListItem
                  title="Custom Amount"
                  value={PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT}
                />
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
          <div>
            <TextInput
              label="Value"
              placeholder={`Enter ${
                type === PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT ? 'amount' : 'percentage'
              }`}
              size="medium"
              type="number"
              value={value}
              leadingIcon={type === PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT ? RupeeIcon : PercentIcon}
              onChange={({ value }) => setValue(value || '')}
              errorText={errors.value}
              validationState={errors.value ? 'error' : 'none'}
            />
            <Text color="surface.text.gray.muted" size="small" marginTop="spacing.2">
              <i>
                This is the percentage of total cart value that will be charged as partial COD
                payment. Cannot be more than 50%
              </i>
            </Text>
          </div>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" justifyContent="flex-end">
          <Button variant="primary" onClick={handleSave}>
            Save
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default BasicSlabModal;
