import React, { useEffect, useState } from 'react';
import {
  Box,
  Button,
  Text,
  Heading,
  Badge,
  CheckIcon,
  Divider,
  Accordion,
  AccordionItem,
  AccordionItemBody,
  AccordionItemHeader,
  Checkbox,
  Skeleton,
  Link,
  CardBody,
  Card,
  AlertTriangleIcon,
} from '@razorpay/blade/components';
import styled from 'styled-components';
import { trackPreviewPolicyPages } from '../tracking';

import { Environments, ShowNotificationType } from 'common/typings';

import useModalComponents from '../hooks/useModalComponents';
import { usePolicyPagesPreview, usePolicyPagesPublish } from '../hooks/usePolicyPagesPublish';
import {
  PolicyPageToBeMade,
  ValidationState,
  WebsiteSubmitModalSteps,
  WebsiteUpdateAutomationStatus,
} from '../types';
import { snapPoints } from '../utils';

const SkeletonWrapper = styled.div(
  ({ theme }) => `
  display: flex;
  flex-direction: column;
  gap: ${theme.spacing[5]}px;
  max-width: 400px;
`,
);

const AlertIconWrapper = styled.div(
  ({ theme }) => `
  display: flex;
  padding: ${theme.spacing[4]}px;
  backgroundColor: ${theme.colors.feedback.background.negative.subtle};
`,
);

interface PreviewPagesProps {
  isMobile: boolean;
  isOpen: boolean;
  setCurrentStep: (step: WebsiteSubmitModalSteps) => void;
  policyPagesToBeMade: PolicyPageToBeMade;
  mode: Environments;
  showNotification: ShowNotificationType;
}

function PreviewPages({
  setCurrentStep,
  isOpen,
  mode,
  showNotification,
  isMobile,
  policyPagesToBeMade,
}: PreviewPagesProps): JSX.Element {
  const { Modal, ModalHeader, ModalBody, ModalFooter } = useModalComponents(isMobile);
  const {
    data,
    isError,
    isFetching,
    refetch: retryPreviewAPI,
  } = usePolicyPagesPreview({
    mode,
    sections: policyPagesToBeMade,
  });
  const { consentMutation, publishMutation } = usePolicyPagesPublish();
  const [isConsentChecked, setIsConsentChecked] = useState(true);
  const [isFormError, setIsFormError] = useState(false);

  const commonProperties = {
    policyPageRZPCreate: policyPagesToBeMade.length,
  };

  useEffect(() => {
    if (isOpen) {
      trackPreviewPolicyPages('Displayed', commonProperties);
    }
  }, [isOpen]);

  const onGoBack = () => {
    trackPreviewPolicyPages('Filled', { ...commonProperties, clickedButton: 'cancel' });
    setCurrentStep(WebsiteSubmitModalSteps.POLICY_PAGES_CREATION);
  };

  const onContinueClick = () => {
    // submit details to API and go to next step
    trackPreviewPolicyPages('Filled', { ...commonProperties, clickedButton: 'submit' });
    consentMutation
      .mutateAsync({
        mode,
        consents: [
          {
            type: 'Policy Creation Terms',
            is_provided: isConsentChecked,
          },
        ],
        event: 'WebsitePolicyWizard',
      })
      .then((res) => {
        if (res.success) {
          // on success - trigger publish API
          publishMutation
            .mutateAsync({
              mode,
              sections: policyPagesToBeMade,
            })
            .then((res) => {
              if (res.current_status === WebsiteUpdateAutomationStatus.COMPLETED) {
                trackPreviewPolicyPages('Success', commonProperties);
                setCurrentStep(WebsiteSubmitModalSteps.POLICY_PAGES_COMPLETE);
              } else {
                const message = 'Something went wrong. Please try again.';
                trackPreviewPolicyPages('Error', { ...commonProperties, errorMessage: message });
                showNotification({
                  type: 'error',
                  message,
                });
              }
            })
            .catch((error) => {
              const message = error?.message || 'Failed to submit details. Please try again.';
              trackPreviewPolicyPages('Error', { ...commonProperties, errorMessage: message });
              showNotification({
                type: 'error',
                message,
              });
            });
        } else {
          const message = 'Failed to provide consent. Please try again';
          trackPreviewPolicyPages('Error', { ...commonProperties, errorMessage: message });
          showNotification({
            type: 'error',
            message,
          });
        }
      })
      .catch((error) => {
        const message = error?.message || 'Failed to submit details. Please try again.';
        trackPreviewPolicyPages('Error', { ...commonProperties, errorMessage: message });
        showNotification({
          type: 'error',
          message,
        });
      });
  };

  const isPublishing = publishMutation.isLoading || consentMutation.isLoading;

  return (
    <Modal
      isOpen={isOpen}
      onDismiss={onGoBack}
      snapPoints={snapPoints}
      size="medium"
      zIndex={99999}
    >
      <ModalHeader />
      <ModalBody padding={isMobile ? 'spacing.5' : 'spacing.0'}>
        <Box
          paddingX={isMobile ? 'none' : 'spacing.8'}
          paddingY={isMobile ? 'none' : 'spacing.7'}
          height="560px"
        >
          <Box>
            <Heading size="small" weight="semibold">
              Please review the policy pages created for your business
            </Heading>
            <Text size="medium" color="surface.text.gray.muted" marginBottom="spacing.7">
              We have created these pages based on your given details.
            </Text>
          </Box>
          <Box backgroundColor="transparent" width="100%" overflow="scroll">
            <Box
              display="flex"
              flexDirection="row"
              gap="spacing.3"
              alignItems="center"
              marginBottom="spacing.7"
            >
              <Badge icon={CheckIcon} color="information">
                Created by Razorpay
              </Badge>
              <Divider thickness="thick" variant="subtle" />
            </Box>
          </Box>
          {isFetching || isError ? (
            <SkeletonWrapper>
              {policyPagesToBeMade.map((item) => (
                <Card key={`${item}-wrapper`} elevation="none">
                  <CardBody>
                    {isFetching ? (
                      <Box
                        marginBottom="spacing.4"
                        display="flex"
                        flexDirection="column"
                        gap="spacing.4"
                      >
                        <Skeleton width="100%" height="20px" borderRadius="medium" />
                        <Skeleton width="50%" height="16px" borderRadius="medium" />
                      </Box>
                    ) : (
                      <Box display="flex" gap="spacing.4">
                        <AlertIconWrapper>
                          <AlertTriangleIcon color="feedback.icon.negative.intense" />
                        </AlertIconWrapper>
                        <Box>
                          <Text
                            color="surface.text.gray.subtle"
                            weight="semibold"
                            variant="body"
                            size="medium"
                          >
                            Preview couldn’t be loaded
                          </Text>
                          <Link onClick={() => retryPreviewAPI()} variant="button">
                            Retry
                          </Link>
                        </Box>
                      </Box>
                    )}
                  </CardBody>
                </Card>
              ))}
            </SkeletonWrapper>
          ) : (
            <>
              <Box display="flex" flexDirection="column" gap="spacing.4">
                {data &&
                  data.data.map((item) => {
                    return (
                      <Accordion
                        variant="filled"
                        key={`${item.html_content}-accordian`}
                        size="medium"
                      >
                        <AccordionItem>
                          <AccordionItemHeader title={item.section} />
                          <AccordionItemBody>
                            <Box height="200px" overflow="scroll">
                              <div dangerouslySetInnerHTML={{ __html: item.html_content }} />
                            </Box>
                          </AccordionItemBody>
                        </AccordionItem>
                      </Accordion>
                    );
                  })}
              </Box>
              <Box
                display="flex"
                flexDirection="column"
                justifyContent="flex-end"
                padding="spacing.4"
                position="relative"
                gap="spacing.3"
              >
                <Checkbox
                  size="medium"
                  onChange={(e) => {
                    setIsConsentChecked(e.isChecked);
                    setIsFormError(false);
                  }}
                  validationState={isFormError ? ValidationState.ERROR : ValidationState.NONE}
                  errorText="Please agree to the terms to proceed"
                  isChecked={isConsentChecked}
                >
                  I understand that the content provided is not legal advice, and by using them I
                  agree to this disclaimer
                </Checkbox>
              </Box>
            </>
          )}
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          {!isMobile && (
            <Button variant="tertiary" onClick={onGoBack}>
              Go back
            </Button>
          )}
          <Button
            isFullWidth={isMobile}
            onClick={() => {
              // form validation
              if (!isConsentChecked) {
                setIsFormError(true);
                return;
              }
              // do API call and go to next step
              onContinueClick();
            }}
            isDisabled={isPublishing || isFetching || isConsentChecked === false}
          >
            {isMobile
              ? `Proceed${isPublishing ? 'ing' : ''}`
              : `Submit${isPublishing ? 'ting' : ''}`}
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
}

export default PreviewPages;
