import React, { useEffect, useMemo, useState } from 'react';
import { ArrowRightIcon, Box, Button, Text } from '@razorpay/blade/components';

import { User } from 'common/typings';

import EditableCard from './EditableCard';
import SuggestionsBox from './SuggestionsBox';
import VerifiedPolicyPageCard from './VerifiedPolicyPageCard';
import { PolicyPageContent, getInitialPolicyPagesFormState } from './utils';
import useBusinessWebsiteData from '../hooks/useBusinessWebsiteData';
import useModalComponents from '../hooks/useModalComponents';
import { trackWebsitePrivacyPolicyModalLoaded } from '../tracking';
import {
  BladeFormInputOnEvent,
  ValidationState,
  WebsitePolicyPages,
  PolicyPageFormData,
  PolicyPagesSelection,
} from '../types';
import { policyPageFormValidator, snapPoints, getWebsiteCount } from '../utils';

interface WebsiteFixModalProps {
  isMobile: boolean;
  isOpen: boolean;
  onDismiss: () => void;
  handlePolicyPageSubmit: (formState: PolicyPageFormData) => void;
  user: User;
  onCreateAllPolicyPagesButtonClick: (formState: PolicyPageFormData) => void;
}

const WebsiteFixModal: React.FC<WebsiteFixModalProps> = ({
  isMobile,
  isOpen,
  onDismiss,
  handlePolicyPageSubmit,
  user,
  onCreateAllPolicyPagesButtonClick,
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

  const [activeField, setActiveField] = useState<WebsitePolicyPages | undefined>(undefined);

  const onChange = ({ name, value }: BladeFormInputOnEvent) => {
    /* istanbul ignore next */
    if (!name) return;
    setFormState((formState) => {
      return {
        ...formState,
        [name]: {
          ...formState[name],
          value,
          // clear error while typing
          valid: ValidationState.NONE,
        },
      };
    });
  };
  const handleFocusOnNext = (page: WebsitePolicyPages) => {
    const currentIndex = missingPagesKeys.indexOf(page);
    // if currentIndex is not found or outside the array, set activeField to null
    if (currentIndex === missingPagesKeys.length - 1) {
      setActiveField(undefined);
      return;
    }
    setActiveField(missingPagesKeys[currentIndex + 1]);
  };
  return (
    <Modal
      isOpen={isOpen}
      onDismiss={onDismiss}
      snapPoints={snapPoints}
      size="large"
      zIndex={99999}
    >
      <ModalHeader />
      <ModalBody padding={isMobile ? 'spacing.5' : 'spacing.0'}>
        <Box display="flex" flexDirection="row" minHeight={isMobile ? 'none' : '400px'}>
          <Box
            paddingX={isMobile ? 'none' : 'spacing.8'}
            display="flex"
            flexDirection="column"
            flex="2"
            gap="spacing.8"
            paddingY={isMobile ? 'none' : 'spacing.7'}
          >
            <Box display="flex" flexDirection="column" gap="spacing.4">
              <Box marginBottom="spacing.4" marginTop="spacing.2">
                <Text size="large" weight="medium">
                  Required policy pages on your website
                </Text>
                <Text size="small" color="surface.text.gray.subtle">
                  If you don’t have any of these required pages/details, we'll help you create them.
                </Text>
              </Box>
              {missingPagesKeys.map((page) => {
                const { Icon, title } = PolicyPageContent[page];
                const { value, valid, radioValue } = formState[page];
                return (
                  <EditableCard
                    key={page}
                    page={page}
                    activeField={activeField}
                    setActiveField={setActiveField}
                    title={title}
                    radioValue={radioValue}
                    setFormState={setFormState}
                    onChange={onChange}
                    valid={valid}
                    value={value}
                    handleFocusOnNext={handleFocusOnNext}
                    Icon={Icon}
                  />
                );
              })}
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
              flex="1.3"
              display="flex"
              justifyContent="center"
            >
              <SuggestionsBox type="ADD_MISSING">
                <Box display="flex" flexDirection="column" gap="spacing.4" marginTop="spacing.4">
                  <Text
                    color="surface.text.gray.subtle"
                    size="medium"
                    weight="semibold"
                    variant="body"
                  >
                    In case you don’t have these details, we can create these policy pages for you!
                  </Text>
                </Box>
                <Button
                  variant="primary"
                  size="small"
                  onClick={() => onCreateAllPolicyPagesButtonClick(formState)}
                  iconPosition="right"
                  icon={ArrowRightIcon}
                  marginTop="spacing.4"
                >
                  Create Policy Pages
                </Button>
              </SuggestionsBox>
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
              const { isValid, formState: newFormState } = policyPageFormValidator(formState);
              if (!isValid) {
                setFormState(newFormState);
                return;
              }
              handlePolicyPageSubmit(newFormState);
            }}
          >
            {isMobile ? 'Proceed' : 'Submit'}
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

export default WebsiteFixModal;
