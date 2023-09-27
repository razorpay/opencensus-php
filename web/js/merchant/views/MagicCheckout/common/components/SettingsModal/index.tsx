import React, { useEffect, useState } from 'react';
import {
  Box,
  Button,
  TextInput,
  SearchIcon,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
} from '@razorpay/blade/components';
import debounce from 'common/utils/debounce';
import { SettingsModalProps } from './types';
import { ModalItems } from './styles';

function SettingsModal({
  header,
  isOpen,
  handleDismiss,
  variant,
  searchPlaceholder,
  searchFn,
  entityName = '',
  confirmAction,
  itemClassName = '',
  disableConfirmButton = false,
  isLoading,
  children,
}: SettingsModalProps): JSX.Element {
  const [validationState, setValidationState] = useState<'none' | 'error' | 'success'>('none');
  const [inputVal, setInputVal] = useState(entityName);

  useEffect(() => {
    setInputVal(entityName);
  }, [entityName]);

  const debouncedSearchFn = debounce(searchFn, 500);
  const handleChange = (e) => {
    debouncedSearchFn(e.value);
  };

  const handleNameChange = (e) => {
    setInputVal(e.value);
    setValidationState('none');
  };

  const handleClick = () => {
    if (inputVal === '') {
      setValidationState('error');
      return;
    }
    confirmAction(inputVal);
  };
  return (
    <Modal isOpen={isOpen} onDismiss={handleDismiss} size="medium">
      <ModalHeader title={header} />
      <ModalBody>
        <Box width="50%">
          <TextInput
            label={`${variant[0].toUpperCase() + variant.slice(1)} name`}
            placeholder={`Enter ${variant.toLowerCase()} name`}
            name="variantName"
            labelPosition="left"
            value={inputVal}
            onChange={handleNameChange}
            validationState={validationState}
            errorText="Required"
          />
        </Box>
        <Box marginTop="spacing.4">
          <TextInput
            label={`Select ${searchPlaceholder}`}
            placeholder={`Search ${searchPlaceholder}`}
            name="productsSearch"
            onChange={handleChange}
            icon={SearchIcon}
          />

          <ModalItems className={`items ${itemClassName}`}>{children}</ModalItems>
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button
            isDisabled={isLoading || disableConfirmButton}
            onClick={handleDismiss}
            variant="secondary"
          >
            Cancel
          </Button>
          <Button
            testID="confirm-button"
            isLoading={isLoading}
            isDisabled={isLoading || disableConfirmButton}
            onClick={handleClick}
          >
            Confirm
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
}

export default SettingsModal;
