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
  PhoneNumberInput,
} from '@razorpay/blade/components';
import { modalConfig } from './ModalConfig';
import {
  ActiveModalI,
  PersonalProfileFields,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import { connect } from 'react-redux';
import * as ModalActions from 'merchant_common/reducers/modals';
import { bindActionCreators } from 'redux';
import { Store } from 'common/typings';
import { CountryCodeType } from '@razorpay/i18nify-js';
import { getUser } from '@apps/shell/src/client/store/commonStore/exposedActions';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from '@libs/shared-utils';

type PhoneNumberEvent = {
  phoneNumber: string;
  dialCode: string;
  country: CountryCodeType;
  value: string;
  name: string;
};

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
  const [textInput, settextInput] = useState<string | undefined | PhoneNumberEvent>('');
  const [isChecked, setIsChecked] = useState(true);
  const [hasValidationError, setHasValidationError] = useState(false);
  const validationState = hasValidationError ? 'error' : 'none';
  const { abExperiments } = useSplitzService();

  const isValidationEnabledForDisplayName = isExperimentEnabled(
    abExperiments?.display_name_validation,
  );

  const isDisplayNameValidationEnabled =
    entity?.id === PersonalProfileFields.DISPLAY_NAME && isValidationEnabledForDisplayName;

  const { merchant } = getUser();

  if (!entity) return null;

  const {
    title = 'Update details',
    label = 'Enter new details',
    bodyText = '',
    ctaText = 'Update',
    checkboxText = '',
    errorText = 'Invalid input',
    isValid = () => true,
    getErrorText,
  } = modalConfig({ user })[entity?.id] || {};

  const handleUpdateClick = () => {
    const valueToValidate =
      typeof textInput === 'object' ? `${textInput.dialCode}${textInput.value}` : textInput || '';
    if (textInput && isValid(valueToValidate, isDisplayNameValidationEnabled)) {
      setHasValidationError(false);
      onUpdateClick({
        textInput: valueToValidate,
        checkbox: isChecked,
      });
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


  const computeErrorText = () => {
    if (isDisplayNameValidationEnabled) {
      const errorText = getErrorText?.(textInput);
      const hasError = errorText?.length > 0;
      return hasError ? errorText : undefined;
    }
    return hasValidationError ? errorText : undefined;
  };

  const computeValidationState = () => {
    if (isDisplayNameValidationEnabled) {
      return computeErrorText() ? 'error' : 'none';
    }
    return validationState;
  };

  const renderInput = () => {
    if (entity?.id === PersonalProfileFields.CONTACT_MOBILE) {
      return (
        <PhoneNumberInput
          label={label}
          value={typeof textInput === 'object' ? textInput.value : ''}
          onChange={(event: PhoneNumberEvent) => {
            settextInput(event);
          }}
          validationState={validationState}
          errorText={hasValidationError ? errorText : undefined}
          isRequired
          defaultCountry={(merchant?.country_code ?? 'IN') as CountryCodeType}
        />
      );
    }

    return (
      <TextInput
        label={label}
        value={typeof textInput === 'string' ? textInput : ''}
        type="text"
        onChange={({ value }) => settextInput(value)}
        validationState={computeValidationState()}
        errorText={computeErrorText()}
        isRequired
        necessityIndicator="required"
        showClearButton
        onClearButtonClick={() => settextInput('')}
        autoFocus
      />
    );
  };

  return (
    <Modal isOpen={showModal} onDismiss={handleModalDismiss} size="small">
      <ModalHeader title={title} />
      <ModalBody>
        <Box display="flex" flexDirection="column" gap="spacing.4">
          <Text>{bodyText}</Text>
          {renderInput()}
          {hasCheckbox && checkboxText ? (
            <Checkbox
              isChecked={isChecked}
              onChange={({ isChecked: isCheckedNewValue }) => setIsChecked(isCheckedNewValue)}
            >
              {checkboxText}
            </Checkbox>
          ) : null}
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          <Button
            variant="tertiary"
            onClick={handleModalDismiss}
            type="button"
            isDisabled={isLoading}
          >
            Cancel
          </Button>
          <Button
            onClick={handleUpdateClick}
            isLoading={isLoading}
            isDisabled={isBtnDisabled() || isLoading}
          >
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
