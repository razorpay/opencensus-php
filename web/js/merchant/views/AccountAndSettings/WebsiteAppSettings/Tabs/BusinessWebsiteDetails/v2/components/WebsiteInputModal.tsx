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
import { autoPrefixUrls } from 'common/utils/rzp-utils';
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
import {
  mainPageFormValidator,
  snapPoints,
  validators,
  getWebsiteCount,
  getSuggestionStep,
} from '../utils';
import { isDuplicateWebsite, isPopularWebsite } from '@libs/shared-utils';

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
  const [urlError, setUrlError] = useState('');

  const alreadyAddedWebsites = [user.business_website, ...(user.additional_websites || [])].filter(
    Boolean,
  ) as string[];

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
    }
    if (name === MainFormFields.PLATFORM) {
      trackAcceptPaymentToggleButtonClick({
        ...properties,
        acceptOn: value,
      });
    }

    const newSuggestionStep = getSuggestionStep({
      name,
      value,
    });
    if (suggestionStep !== newSuggestionStep) {
      setSuggestionStep(newSuggestionStep);
    }

    let validationState = validators[name](value, formState)
      ? ValidationState.NONE
      : ValidationState.ERROR;

    if (name === MainFormFields.URL) {
      if (validationState === ValidationState.ERROR) {
        setUrlError(
          value?.startsWith('http:')
            ? 'Please ensure your website is HTTPS compliant (https://)'
            : 'Enter a valid website link',
        );
      } else if (isDuplicateWebsite(alreadyAddedWebsites, autoPrefixUrls(value || '', true))) {
        validationState = ValidationState.ERROR;
        setUrlError(`This ${formState.platform.value ?? 'website'} is already added`);
      } else if (isPopularWebsite(autoPrefixUrls(value || '', true))) {
        validationState = ValidationState.ERROR;
        setUrlError(
          'Please enter a valid business website that you own or manage where you plan to accept payments.',
        );
      } else {
        validationState = ValidationState.NONE;
        setUrlError('');
      }
    }

    setFormState((formState) => {
      return {
        ...formState,
        [name]: {
          value,
          valid: validationState,
        },
      };
    });
  };

  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss} snapPoints={snapPoints} size="large">
      <ModalHeader title="Submit details for verification" />
      <ModalBody padding={isMobile ? 'spacing.5' : 'spacing.0'}>
        <Box display="flex" flexDirection="row" minHeight={isMobile ? 'none' : '440px'}>
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
              label={`Add your ${formState.platform.value} link`}
              labelPosition="top"
              name={MainFormFields.URL}
              onChange={onChange}
              type="url"
              validationState={formState.url.valid}
              value={formState.url.value}
              errorText={urlError}
              onFocus={() => setSuggestionStep(SuggestionSteps.URL_FIELD)}
            />
            <RadioGroup
              label={`Does your ${formState.platform.value} require users to login to complete a payment?`}
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
                  onFocus={() => setSuggestionStep(SuggestionSteps.CREDS_FIELDS)}
                  autoFocus={false}
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
                  onFocus={() => setSuggestionStep(SuggestionSteps.CREDS_FIELDS)}
                  autoFocus={false}
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
              <SuggestionsBox
                step={suggestionStep}
                type="ADD_WEBSITE"
                platform={formState.platform.value}
              />
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
