import React, { useEffect, useState } from 'react';
import {
  Box,
  Button,
  PasswordInput,
  Radio,
  RadioGroup,
  TextInput,
} from '@razorpay/blade/components';

import { User } from 'common/typings';

import SuggestionsBox from './SuggestionsBox';
import useModalComponents from '../hooks/useModalComponents';
import {
  trackAcceptPaymentToggleButtonClick,
  trackLoginRequiredToggleButtonClick,
  trackSubmitWebsiteDetailsVerificationModalLoad,
} from '../tracking';
import {
  BladeFormInputOnEvent,
  Platform,
  RequireCredsValues,
  SuggestionSteps,
  ValidationState,
  MainPageFormData,
  MainFormFields,
} from '../types';
import { mainPageFormValidator, snapPoints, validators, getWebsiteCount } from '../utils';

interface WebsiteInputModalProps {
  isMobile: boolean;
  isOpen: boolean;
  onDismiss: () => void;
  handleMainPageSubmit: (formState: MainPageFormData) => void;
  user: User;
  formState: MainPageFormData;
  setFormState: React.Dispatch<React.SetStateAction<MainPageFormData>>;
}

const WebsiteInputModal: React.FC<WebsiteInputModalProps> = ({
  isMobile,
  isOpen,
  onDismiss,
  handleMainPageSubmit,
  user,
  formState,
  setFormState,
}) => {
  const { Modal, ModalHeader, ModalBody, ModalFooter } = useModalComponents(isMobile);
  const [suggestionStep, setSuggestionStep] = useState(SuggestionSteps.POLICY_PAGES);

  useEffect(() => {
    if (isOpen) {
      const properties = {
        websiteCount: getWebsiteCount(user),
      };
      trackSubmitWebsiteDetailsVerificationModalLoad(properties);
    }
  }, [isOpen]);

  const onChange = ({ name, value }: BladeFormInputOnEvent) => {
    const properties = {
      websiteCount: getWebsiteCount(user),
    };
    /* istanbul ignore next */
    if (!name) return;
    if (name === MainFormFields.REQUIRE_CREDS) {
      trackLoginRequiredToggleButtonClick({
        ...properties,
        loginRequired: value,
      });
      setSuggestionStep(
        value === RequireCredsValues.YES
          ? SuggestionSteps.CREDS_REQUIRED
          : SuggestionSteps.CREDS_NOT_REQUIRED,
      );
    }
    if (name === MainFormFields.PLATFORM) {
      trackAcceptPaymentToggleButtonClick({
        ...properties,
        acceptOn: value,
      });
    }
    setFormState((formState) => {
      return {
        ...formState,
        [name]: {
          value,
          valid: validators[name](value, formState) ? ValidationState.NONE : ValidationState.ERROR,
        },
      };
    });
  };

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss} snapPoints={snapPoints} size="medium">
      <ModalHeader title="Submit details for verification" />
      <ModalBody padding={isMobile ? 'spacing.5' : 'spacing.0'}>
        <Box display="flex" flexDirection="row" minHeight={isMobile ? 'none' : '400px'}>
          <Box
            padding={isMobile ? 'none' : 'spacing.6'}
            display="flex"
            flexDirection="column"
            flex="2"
            gap="spacing.6"
          >
            <RadioGroup
              label="Where do you want to accept payments?"
              name={MainFormFields.PLATFORM}
              onChange={onChange}
              validationState={formState.platform.valid}
              value={formState.platform.value}
              errorText="Please choose the platform"
            >
              <Radio value={Platform.WEBSITE}>Website</Radio>
              <Radio value={Platform.APP}>App</Radio>
            </RadioGroup>
            <TextInput
              helpText={`This is the ${formState.platform.value} where you would like to accept payments`}
              label={`Add your ${formState.platform.value} link`}
              labelPosition="top"
              name={MainFormFields.URL}
              onChange={onChange}
              type="url"
              validationState={formState.url.valid}
              value={formState.url.value}
              errorText="Enter valid website link starting with https://"
            />
            <RadioGroup
              label={`Does your ${formState.platform.value} require login form user to complete payment?`}
              name={MainFormFields.REQUIRE_CREDS}
              onChange={onChange}
              validationState={formState.requireCreds.valid}
              value={formState.requireCreds.value}
              errorText="Please choose valid option"
            >
              <Radio value={RequireCredsValues.YES}>Yes</Radio>
              <Radio value={RequireCredsValues.NO}>No</Radio>
            </RadioGroup>
            {formState.requireCreds.value === RequireCredsValues.YES ? (
              <Box
                padding="spacing.5"
                display="flex"
                flexDirection="column"
                gap="spacing.5"
                backgroundColor="surface.background.gray.moderate"
              >
                <TextInput
                  label="Add test account username/email"
                  labelPosition="top"
                  name={MainFormFields.CREDS_USERNAME}
                  onChange={onChange}
                  placeholder="Add username/email"
                  validationState={formState.credsUsername.valid}
                  value={formState.credsUsername.value}
                  errorText="Enter valid username/email"
                />
                <PasswordInput
                  label="Add test account password"
                  labelPosition="top"
                  name={MainFormFields.CREDS_PASSWORD}
                  onChange={onChange}
                  placeholder="Add password"
                  validationState={formState.credsPassword.valid}
                  value={formState.credsPassword.value}
                  errorText="Enter valid password"
                />
              </Box>
            ) : null}
          </Box>
          {!isMobile && (
            <Box
              padding="spacing.6"
              backgroundColor="surface.background.sea.subtle"
              flex="1.5"
              display="flex"
              justifyContent="center"
            >
              <SuggestionsBox step={suggestionStep} type="ADD_WEBSITE" />
            </Box>
          )}
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          {!isMobile && (
            <Button variant="tertiary" onClick={onDismiss}>
              Cancel
            </Button>
          )}
          <Button
            isFullWidth={isMobile}
            onClick={() => {
              const isValid = mainPageFormValidator(formState);
              if (!isValid) {
                const newFormState = { ...formState };
                Object.keys(newFormState).forEach((key) => {
                  const value = newFormState[key].value;
                  if (!validators[key](value, formState)) {
                    newFormState[key].valid = ValidationState.ERROR;
                  }
                });
                setFormState(newFormState);
                return;
              }
              handleMainPageSubmit(formState);
            }}
            isDisabled={!mainPageFormValidator(formState)}
          >
            {isMobile ? 'Proceed' : 'Submit'}
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default WebsiteInputModal;
