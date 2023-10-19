import React, { useState } from 'react';
import {
  Box,
  Button,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Text,
  TextInput,
  Checkbox,
} from '@razorpay/blade/components';
import { modalConfig } from './ModalConfig';
import { ActiveModalI } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import { connect } from 'react-redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import { bindActionCreators } from 'redux';
import { Store } from 'common/typings';

interface ModalFormProps {
  entity: ActiveModalI | null;
  showModal: boolean;
  isLoading?: boolean;
  onModalDismiss?: () => void;
  onUpdateClick: (arg: { textInput: string; checkbox: boolean }) => void;
  closeModal: () => void;
  user: Store['session']['user'];
  hasCheckbox: boolean;
}

const ModalForm = ({
  entity,
  showModal,
  isLoading = false,
  onModalDismiss,
  onUpdateClick,
  closeModal,
  user,
  hasCheckbox = false,
}: ModalFormProps) => {
  const [textInput, settextInput] = useState<string | undefined>('');
  const [isChecked, setIsChecked] = useState(true);
  const [hasValidationError, setHasValidationError] = useState(false);
  const validationState = hasValidationError ? 'error' : 'none';

  if (!entity) return null;

  const {
    title = 'Update details',
    label = 'Enter new details',
    bodyText = '',
    ctaText = 'Update',
    checkboxText = '',
    errorText = 'Invalid input',
    isValid = () => true,
  } = modalConfig({ user })[entity?.id] || {};

  const handleUpdateClick = () => {
    if (textInput && isValid(textInput!)) {
      setHasValidationError(false);
      onUpdateClick({ textInput, checkbox: isChecked });
    } else {
      setHasValidationError(true);
    }
  };

  const handleModalDismiss = () => {
    closeModal();
    onModalDismiss?.();
  };

  const isBtnDisabled = (): boolean => {
    if (textInput) {
      return false;
    } else {
      return true;
    }
  };

  return (
    <Modal isOpen={showModal} onDismiss={handleModalDismiss} size="small">
      <ModalHeader title={title} />
      <ModalBody>
        <TextInput
          label={label}
          onChange={(e) => settextInput(e.value)}
          isRequired
          type="text"
          necessityIndicator="required"
          errorText={errorText}
          validationState={validationState}
          showClearButton
          onClearButtonClick={() => settextInput('')}
          autoFocus
        />
        <Box marginTop="spacing.6">
          <Text
            color="surface.text.normal.lowContrast"
            size="medium"
            variant="body"
            weight="regular"
            type="normal"
          >
            {bodyText}
          </Text>
        </Box>
        {hasCheckbox && checkboxText ? (
          <Box marginTop="spacing.7">
            <Checkbox
              isChecked={isChecked}
              onChange={({ isChecked: isCheckedNewValue }) => setIsChecked(isCheckedNewValue)}
            >
              {checkboxText}
            </Checkbox>
          </Box>
        ) : null}
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button isLoading={isLoading} onClick={handleUpdateClick} isDisabled={isBtnDisabled()}>
            {ctaText}
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(ModalForm);
