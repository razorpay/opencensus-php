import React, { useEffect, useMemo, useState } from 'react';
import { Box, Button, Text, Link, TextInput } from '@razorpay/blade/components';

import { User } from 'common/typings';
import { isUrlLenient } from 'common/utils/validators';

import SuggestionsBox from './SuggestionsBox';
import VerifiedPolicyPageCard from './VerifiedPolicyPageCard';
import { PolicyPageContent, getInitialPolicyPagesFormState, policySuggestionStep } from './utils';
import useBusinessWebsiteData from '../hooks/useBusinessWebsiteData';
import useModalComponents from '../hooks/useModalComponents';
import { trackWebsitePrivacyPolicyModalLoaded } from '../tracking';
import {
  BladeFormInputOnEvent,
  ValidationState,
  WebsitePolicyPages,
  PolicyPageFormData,
} from '../types';
import { policyPageFormValidator, snapPoints, getWebsiteCount } from '../utils';

interface WebsiteFixModalProps {
  isMobile: boolean;
  isOpen: boolean;
  onDismiss: () => void;
  handlePolicyPageSubmit: (formState: PolicyPageFormData) => void;
  user: User;
}

const WebsiteFixModal: React.FC<WebsiteFixModalProps> = ({
  isMobile,
  isOpen,
  onDismiss,
  handlePolicyPageSubmit,
  user,
}) => {
  const { Modal, ModalHeader, ModalBody, ModalFooter } = useModalComponents(isMobile);
  const { websiteUpdateData: { website_verification_page_status } = {} } = useBusinessWebsiteData();

  useEffect(() => {
    if (isOpen) {
      const properties = {
        websiteCount: getWebsiteCount(user),
      };
      trackWebsitePrivacyPolicyModalLoaded(properties);
    }
  }, [isOpen]);

  const { verifiedPages, missingPages, verifiedPagesKeys, missingPagesKeys } = useMemo(
    () => getInitialPolicyPagesFormState(website_verification_page_status),
    [website_verification_page_status],
  );
  const [formState, setFormState] = useState<PolicyPageFormData>(missingPages);

  const [activeField, setActiveField] = useState<WebsitePolicyPages>(missingPagesKeys[0]);

  const onChange = ({ name, value }: BladeFormInputOnEvent) => {
    /* istanbul ignore next */
    if (!name) return;
    setFormState((formState) => {
      return {
        ...formState,
        [name]: {
          value,
          // clear error while typing
          valid: ValidationState.NONE,
        },
      };
    });
  };

  return (
    <Modal
      isOpen={isOpen}
      onDismiss={onDismiss}
      snapPoints={snapPoints}
      size="medium"
      zIndex={99999}
    >
      <ModalHeader title="Submit details for verification" />
      <ModalBody padding={isMobile ? 'spacing.5' : 'spacing.0'}>
        <Box display="flex" flexDirection="row" minHeight={isMobile ? 'none' : '400px'}>
          <Box
            padding={isMobile ? 'none' : 'spacing.6'}
            display="flex"
            flexDirection="column"
            flex="2"
            gap="spacing.8"
          >
            <Box display="flex" flexDirection="column" gap="spacing.4">
              <Box>
                <Text size="large" weight="medium">
                  Required policy pages on your website
                </Text>
                <Text size="small" color="surface.text.gray.subtle">
                  Kindly update your website pages/details{' '}
                  <Link
                    size="small"
                    target="_blank"
                    href="https://razorpay.com/docs/payments/dashboard/account-settings/profile/#add-or-update-website-or-app-details"
                  >
                    Know more
                  </Link>
                </Text>
              </Box>
              {missingPagesKeys.map((page) => (
                <TextInput
                  label={`${PolicyPageContent[page].title} link`}
                  labelPosition="top"
                  name={page}
                  onChange={onChange}
                  type="url"
                  validationState={formState[page].valid}
                  value={formState[page].value}
                  errorText="Enter valid website link"
                  key={page}
                  onFocus={() => setActiveField(page as WebsitePolicyPages)}
                />
              ))}
            </Box>
            {verifiedPagesKeys.length ? (
              <Box gap="spacing.4" display="flex" flexDirection="column">
                <Box>
                  <Text size="large" weight="medium">
                    Policy pages found on your website
                  </Text>
                  <Text size="small" color="surface.text.gray.subtle">
                    The details have been saved and are ready for verification{' '}
                  </Text>
                </Box>
                <Box display="flex" flexDirection="column" gap="spacing.5">
                  {verifiedPagesKeys.map((pageKey) => {
                    const { Icon, title } = PolicyPageContent[pageKey];
                    return (
                      <VerifiedPolicyPageCard
                        key={pageKey}
                        pageKey={pageKey}
                        url={verifiedPages[pageKey].value}
                        Icon={Icon}
                        title={title}
                      />
                    );
                  })}
                </Box>
              </Box>
            ) : /* istanbul ignore next */
            null}
          </Box>
          {!isMobile && (
            <Box
              padding="spacing.6"
              backgroundColor="surface.background.sea.subtle"
              flex="1.5"
              display="flex"
              justifyContent="center"
            >
              <SuggestionsBox step={policySuggestionStep[activeField]} />
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
              const isValid = policyPageFormValidator(formState);
              if (!isValid) {
                const newFormState = { ...formState };
                Object.keys(newFormState).forEach((key) => {
                  if (!isUrlLenient(formState[key].value)) {
                    newFormState[key].valid = ValidationState.ERROR;
                  }
                });
                setFormState(newFormState);
                return;
              }
              handlePolicyPageSubmit(formState);
            }}
            // isDisabled={!policyPageFormValidator(formState)}
          >
            {isMobile ? 'Proceed' : 'Submit'}
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default WebsiteFixModal;
