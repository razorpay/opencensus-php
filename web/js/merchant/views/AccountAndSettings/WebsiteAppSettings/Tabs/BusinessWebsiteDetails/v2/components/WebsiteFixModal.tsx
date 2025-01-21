import React, { useEffect, useState } from 'react';
import { ArrowRightIcon, Box, Button, Heading, Text, Link } from '@razorpay/blade/components';

import { User } from 'common/typings';

import EditableCard from './EditableCard';
import HorizontalLineWithText from './HorizontalLineWithText';
import SuggestionsBox from './SuggestionsBox';
import VerifiedPolicyPageCard from './VerifiedPolicyPageCard';
import PolicyPagesIllustration from './assets/policyPagesIllustration.svg';
import { PolicyPageContent } from './utils';
import useModalComponents from '../hooks/useModalComponents';
import { trackWebsitePrivacyPolicyModalLoaded } from '../tracking';
import {
  BladeFormInputOnEvent,
  ValidationState,
  WebsitePolicyPages,
  PolicyPageFormData,
  FormFieldType,
  MissingPagesFormFieldType,
} from '../types';
import { policyPageFormValidator, snapPoints, getWebsiteCount } from '../utils';

interface WebsiteFixModalProps {
  isMobile: boolean;
  isOpen: boolean;
  onDismiss: () => void;
  handlePolicyPageSubmit: (formState: PolicyPageFormData) => void;
  user: User;
  org: { business_name: string };
  onCreateAllPolicyPagesButtonClick: (formState: PolicyPageFormData) => void;
  formState: PolicyPageFormData;
  setFormState: React.Dispatch<React.SetStateAction<PolicyPageFormData>>;

  verifiedPages: Record<WebsitePolicyPages, FormFieldType>;
  missingPages: Record<WebsitePolicyPages, MissingPagesFormFieldType>;
  verifiedPagesKeys: WebsitePolicyPages[];
  missingPagesKeys: WebsitePolicyPages[];
}

const WebsiteFixModal: React.FC<WebsiteFixModalProps> = ({
  isMobile,
  isOpen,
  onDismiss,
  handlePolicyPageSubmit,
  user,
  org,
  onCreateAllPolicyPagesButtonClick,
  formState,
  setFormState,
  verifiedPages,
  verifiedPagesKeys,
  missingPagesKeys,
}) => {
  const { Modal, ModalHeader, ModalBody, ModalFooter } = useModalComponents(isMobile);

  useEffect(() => {
    if (isOpen) {
      const properties = {
        websiteCount: getWebsiteCount(user),
      };
      trackWebsitePrivacyPolicyModalLoaded(properties);
    }
  }, [isOpen]);

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
    const nextUnfilledPageIdx = missingPagesKeys.findIndex(
      (findPage) => !formState[findPage]?.radioValue && page !== findPage,
    );
    setActiveField(nextUnfilledPageIdx !== -1 ? missingPagesKeys[nextUnfilledPageIdx] : undefined);
  };
  return (
    <Modal isOpen={isOpen} onDismiss={onDismiss} snapPoints={snapPoints} size="large">
      <ModalHeader />
      <ModalBody padding={isMobile ? 'spacing.5' : 'spacing.0'}>
        <Box
          display="flex"
          flexDirection="row"
          minHeight={isMobile ? 'none' : '509px'}
          height="509px"
        >
          <Box
            paddingX={isMobile ? 'none' : 'spacing.8'}
            display="flex"
            flexDirection="column"
            flex="2"
            gap="spacing.8"
            paddingY={isMobile ? 'none' : 'spacing.7'}
            overflowY="auto"
          >
            <Box
              display="flex"
              flexDirection="column"
              gap="spacing.6"
              paddingBottom={verifiedPagesKeys.length || isMobile ? 'spacing.0' : 'spacing.7'}
            >
              <Box marginBottom="spacing.4" marginTop="spacing.2">
                <Heading size="small" weight="semibold">
                  Required policy pages on your website
                </Heading>
                <Text size="medium" color="surface.text.gray.muted">
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
              <Box
                gap="spacing.4"
                display="flex"
                flexDirection="column"
                paddingBottom={isMobile ? 'spacing.0' : 'spacing.7'}
              >
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
              paddingTop="65px"
              position="sticky"
              top="spacing.0"
              alignItems="center"
              flexDirection="column"
              gap="spacing.3"
              borderTopRightRadius="large"
            >
              <HorizontalLineWithText>
                <span>Recommended</span>
              </HorizontalLineWithText>
              <SuggestionsBox type="ADD_MISSING">
                <Box position="relative" padding="spacing.5">
                  <Text size="large" weight="medium">
                    Create all missing policy pages with {org.business_name} in 2 mins! ⚡️
                  </Text>
                  <Link
                    variant="button"
                    size="medium"
                    onClick={() => onCreateAllPolicyPagesButtonClick(formState)}
                    iconPosition="right"
                    icon={ArrowRightIcon}
                    marginTop="spacing.6"
                  >
                    Create Now
                  </Link>
                  <Box position="absolute" right="spacing.0" bottom="spacing.0">
                    <img src={PolicyPagesIllustration} />
                  </Box>
                </Box>
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
