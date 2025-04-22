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

import { Environments, ShowNotificationType } from 'common/typings';

import PreviewPagesIllustration from './assets/previewPagesIllustration.svg';
import { PolicyPageContent } from './utils';
import useModalComponents from '../hooks/useModalComponents';
import { usePolicyPagesPreview, usePolicyPagesPublish } from '../hooks/usePolicyPagesPublish';
import { trackPreviewPolicyPages } from '../tracking';
import {
  PartialPolicyPages,
  ValidationState,
  WebsitePolicyPages,
  WebsiteSubmitModalSteps,
  WebsiteUpdateAutomationStatus,
} from '../types';
import { extractTextFromHtmlElement, getErrorMessage, snapPoints } from '../utils';
import { policyPagePublishDisclaimer } from './constants';

const SkeletonWrapper = styled.div(
  ({ theme }) => `
  display: flex;
  flex-direction: column;
  gap: ${theme.spacing[5]}px;
  max-width: 100%;
`,
);

const AlertIconWrapper = styled.div(
  ({ theme }) => `
  display: flex;
  padding: ${theme.spacing[4]}px;
  backgroundColor: ${theme.colors.feedback.background.negative.subtle};
`,
);

export interface PreviewPagesProps {
  isMobile: boolean;
  isOpen: boolean;
  setCurrentStep: (step: WebsiteSubmitModalSteps) => void;
  policyPagesToBeMade: PartialPolicyPages;
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
  const [isDisclaimerOpen, setIsDisclaimerOpen] = useState(false);

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

    if (policyPagesToBeMade.length === 1 && policyPagesToBeMade[0] === WebsitePolicyPages.TERMS) {
      setCurrentStep(WebsiteSubmitModalSteps.ADD_MISSING_POLICY_PAGES);
    } else {
      setCurrentStep(WebsiteSubmitModalSteps.POLICY_PAGES_CREATION);
    }
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
              const isWorkflowRaised =
                res.current_status === WebsiteUpdateAutomationStatus.WORKFLOW_IN_PROGRESS;
              if (
                res.current_status === WebsiteUpdateAutomationStatus.COMPLETED ||
                isWorkflowRaised
              ) {
                trackPreviewPolicyPages('Success', {
                  ...commonProperties,
                  isWorkflowRaised,
                });
                setCurrentStep(WebsiteSubmitModalSteps.POLICY_PAGES_COMPLETE);
              } else {
                // TODO: will check if it also needs to be updated
                const message = 'Something went wrong. Please try again.';
                trackPreviewPolicyPages('Error', { ...commonProperties, errorMessage: message });
                showNotification({
                  type: 'error',
                  message,
                });
              }
            })
            .catch((error) => {
              const message = getErrorMessage(error?.message);
              trackPreviewPolicyPages('Error', {
                ...commonProperties,
                errorMessage: message,
                backendErrorMsg: error?.message,
              });
              showNotification({
                type: 'error',
                message,
              });
            });
        } else {
          // TODO: will check if it also needs to be updated
          const message = 'Failed to provide consent. Please try again';
          trackPreviewPolicyPages('Error', { ...commonProperties, errorMessage: message });
          showNotification({
            type: 'error',
            message,
          });
        }
      })
      .catch((error) => {
        const message = getErrorMessage(error?.message);
        trackPreviewPolicyPages('Error', {
          ...commonProperties,
          errorMessage: message,
          backendErrorMsg: error?.message,
        });
        showNotification({
          type: 'error',
          message,
        });
      });
  };

  const isPublishing = publishMutation.isLoading || consentMutation.isLoading;

  return (
    <Modal isOpen={isOpen} onDismiss={onGoBack} snapPoints={snapPoints} size="large">
      <ModalHeader />
      <ModalBody padding={isMobile ? 'spacing.5' : 'spacing.0'}>
        <Box
          display="flex"
          flexDirection="row"
          minHeight={isMobile ? 'none' : '509px'}
          height="509px"
          overflowY="auto"
        >
          <Box
            paddingX={isMobile ? 'none' : 'spacing.8'}
            paddingY={isMobile ? 'none' : 'spacing.7'}
            flex="2"
          >
            <Box>
              <Heading size="small" weight="semibold">
                Review your policy pages
              </Heading>
              <Text size="medium" color="surface.text.gray.muted" marginBottom="spacing.4">
                We’ve created these using your given details
              </Text>
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
                          testID={`loading-${item}`}
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
                      const pageContent = extractTextFromHtmlElement(
                        item.html_content,
                        'content-text',
                      );
                      return (
                        <Accordion variant="filled" key={`${item.section}-accordian`} size="medium">
                          <AccordionItem>
                            <AccordionItemHeader title={PolicyPageContent[item.section].title} />
                            <AccordionItemBody>
                              <Box height="200px" overflow="scroll">
                                {!!pageContent ? (
                                  <Text size="small" color="surface.text.gray.subtle">
                                    {pageContent}
                                  </Text>
                                ) : (
                                  <div dangerouslySetInnerHTML={{ __html: item.html_content }} />
                                )}
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
                    <Text color="surface.text.gray.subtle">
                      I understand that the content provided is not legal advice, and by using them
                      I agree to{' '}
                      <b>
                        <Link
                          variant="button"
                          onClick={() => setIsDisclaimerOpen(!isDisclaimerOpen)}
                        >
                          this disclaimer.
                        </Link>
                      </b>
                      {isDisclaimerOpen ? (
                        <>
                          <Divider
                            thickness="thin"
                            variant="muted"
                            margin="spacing.6"
                            marginX="spacing.0"
                          />
                          <Text color="surface.text.gray.subtle">
                            {policyPagePublishDisclaimer}
                          </Text>
                        </>
                      ) : null}
                    </Text>
                  </Checkbox>
                </Box>
              </>
            )}
          </Box>
          {!isMobile && (
            <Box
              backgroundColor="surface.background.sea.subtle"
              flex="1"
              display="flex"
              position="sticky"
              top="spacing.0"
              flexDirection="column"
              gap="spacing.3"
              alignItems="center"
              justifyContent="flex-end"
              padding="spacing.0"
              paddingLeft="spacing.4"
              borderTopRightRadius="large"
            >
              <Box>
                <img src={PreviewPagesIllustration} />
              </Box>
            </Box>
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
            isDisabled={isError || isPublishing || isFetching || isConsentChecked === false}
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
