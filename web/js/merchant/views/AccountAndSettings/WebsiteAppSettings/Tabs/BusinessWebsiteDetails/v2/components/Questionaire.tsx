import React, { useEffect, useState } from 'react';
import { Box, Button, Text, Heading, Chip, ChipGroup, TextInput } from '@razorpay/blade/components';

import { Environments, ShowNotificationType } from 'common/typings';

import { PolicyPageCreationQuestionaire } from './constants';
import { getQuestionaireDetailsFromPolicyPagesToBeGenerated } from './utils';
import useModalComponents from '../hooks/useModalComponents';
import { usePolicyPagesDetails } from '../hooks/usePolicyPagesDetails';
import { track as analyticsTrack, trackQuestionaire } from '../tracking';
import {
  BladeFormInputOnEvent,
  PolicyPageCreationFormFieldType,
  PartialPolicyPages,
  ValidationState,
  WebsitePolicyPagesDetailsKeys,
  WebsiteSubmitModalSteps,
} from '../types';
import {
  snapPoints,
  policyPageFormCreationValidator,
  getPayloadForPolicyPageSubmission,
  getWebsiteCount,
  getErrorMessage,
} from '../utils';
import CreatePolicyPagesIllustration from './assets/createPolicyPages.svg';

const sensitiveKeys = [
  WebsitePolicyPagesDetailsKeys.SUPPORT_CONTACT_NUMBER,
  WebsitePolicyPagesDetailsKeys.SUPPORT_EMAIL,
] as Array<string>;

export interface QuestionareProps {
  isMobile: boolean;
  isOpen: boolean;
  setCurrentStep: (step: WebsiteSubmitModalSteps) => void;
  policyPagesToBeMade: PartialPolicyPages;
  mode: Environments;
  org: { business_name: string };
  showNotification: ShowNotificationType;
  formState: PolicyPageCreationFormFieldType;
  setFormState: React.Dispatch<React.SetStateAction<PolicyPageCreationFormFieldType>>;
}

function Questionare({
  isOpen,
  setCurrentStep,
  isMobile,
  policyPagesToBeMade,
  mode,
  org,
  showNotification,
  formState,
  setFormState,
}: QuestionareProps): JSX.Element {
  const { Modal, ModalHeader, ModalBody, ModalFooter } = useModalComponents(isMobile);
  const { mutate: savePolicyPagesMutate, isPosting } = usePolicyPagesDetails();

  useEffect(() => {
    if (isOpen) {
      analyticsTrack({
        objectName: 'Create Website Pages Modal',
        actionName: 'Displayed',
        properties: {
          websiteCount: getWebsiteCount(window.rzp_user),
        },
      });
    }
  }, [isOpen]);

  const onChipChange = ({ name, values }) => {
    setFormState((prevState) => ({
      ...prevState,
      [name]: {
        value: values[0],
        valid: ValidationState.NONE,
      },
    }));
  };

  const handleTextInputChange = ({ name, value }: BladeFormInputOnEvent) => {
    setFormState((prevState) => ({
      ...prevState,
      [name as string]: {
        value,
        valid: ValidationState.NONE,
      },
    }));
  };

  const { isEmpty, questionaireMapping } =
    getQuestionaireDetailsFromPolicyPagesToBeGenerated(policyPagesToBeMade);

  const questionareCTAtrack = (properties) =>
    analyticsTrack({
      objectName: 'Create Website Pages Details',
      actionName: 'Filled',
      properties: {
        websiteCount: getWebsiteCount(window.rzp_user),
        details: Object.entries(formState).reduce((acc, [key, value]) => {
          const isQuestionShown = questionaireMapping[key];
          if (isQuestionShown) {
            acc[key] = sensitiveKeys.includes(key) ? Boolean(value?.value) : value?.value;
          }
          return acc;
        }, {}),
        ...properties,
      },
    });

  const onGoBack = () => {
    questionareCTAtrack({ clickedButton: 'cancel' });
    setCurrentStep(WebsiteSubmitModalSteps.ADD_MISSING_POLICY_PAGES);
  };

  const onContinueClick = () => {
    questionareCTAtrack({ clickedButton: 'submit' });
    savePolicyPagesMutate(
      getPayloadForPolicyPageSubmission({
        mode,
        formState,
        policyPagesToBeMade,
      }),
    )
      .then(() => {
        trackQuestionaire('Submitted');
        setCurrentStep(WebsiteSubmitModalSteps.POLICY_PAGES_PREVIEW);
      })
      .catch((error) => {
        const message = getErrorMessage(error?.message);
        trackQuestionaire('Failed', {
          errorMessage: message,
          backendErrorMsg: error?.message,
        });
        showNotification({
          type: 'error',
          message,
        });
      });
  };

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
            <Box marginBottom="spacing.7">
              <Heading size="small" weight="semibold">
                Create policy pages with {org.business_name}
              </Heading>
              <Text size="medium" color="surface.text.gray.muted">
                {isEmpty
                  ? `We’ll be creating the ‘Terms and Conditions’ page using your given details`
                  : `Awesome! We’ll need a couple of details from you to create this page`}
              </Text>
            </Box>
            {isEmpty ? null : (
              <Box
                width="100%"
                display="flex"
                flexDirection="column"
                overflow="scroll"
                gap="spacing.5"
                paddingLeft="spacing.1"
                paddingBottom="spacing.1"
              >
                <>
                  {PolicyPageCreationQuestionaire.map((question) => {
                    return (
                      questionaireMapping[question.questionId] && (
                        <Box key={question.questionId}>
                          <Text
                            size="small"
                            variant="body"
                            weight="semibold"
                            color="surface.text.gray.subtle"
                            marginBottom="spacing.3"
                            testID={`question-${question.questionId}`}
                          >
                            {question.value}
                          </Text>
                          <ChipGroup
                            accessibilityLabel="Test"
                            onChange={onChipChange}
                            value={formState[question.questionId].value}
                            selectionType="single"
                            name={question.questionId}
                            size="xsmall"
                            testID={`chips-${question.questionId}`}
                            validationState={formState[question.questionId].valid}
                            errorText="Please select an option"
                          >
                            {question.options.map((option) => {
                              return (
                                <Chip value={option.value} key={option.value}>
                                  {option.value}
                                </Chip>
                              );
                            })}
                          </ChipGroup>
                        </Box>
                      )
                    );
                  })}
                  {questionaireMapping[WebsitePolicyPagesDetailsKeys.SUPPORT_CONTACT_NUMBER] && (
                    <Box width="99%">
                      <TextInput
                        label="Enter your support contact number"
                        placeholder="Phone number"
                        type="telephone"
                        maxCharacters="10"
                        name={WebsitePolicyPagesDetailsKeys.SUPPORT_CONTACT_NUMBER}
                        onChange={handleTextInputChange}
                        value={
                          formState[WebsitePolicyPagesDetailsKeys.SUPPORT_CONTACT_NUMBER].value
                        }
                        validationState={
                          formState[WebsitePolicyPagesDetailsKeys.SUPPORT_CONTACT_NUMBER].valid
                        }
                        errorText="Please enter a valid contact number"
                        testID="support-contact-number"
                      />
                    </Box>
                  )}
                  {questionaireMapping[WebsitePolicyPagesDetailsKeys.SUPPORT_EMAIL] && (
                    <Box width="99%" marginTop="spacing.3">
                      <TextInput
                        label="Enter your support email ID"
                        placeholder="Email ID"
                        type="email"
                        name={WebsitePolicyPagesDetailsKeys.SUPPORT_EMAIL}
                        onChange={handleTextInputChange}
                        value={formState[WebsitePolicyPagesDetailsKeys.SUPPORT_EMAIL].value}
                        validationState={
                          formState[WebsitePolicyPagesDetailsKeys.SUPPORT_EMAIL].valid
                        }
                        errorText="Please enter a valid email id"
                        testID="support-email"
                      />
                    </Box>
                  )}
                </>
              </Box>
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
                <img src={CreatePolicyPagesIllustration} />
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
              const { isValid, formState: newFormState } = policyPageFormCreationValidator(
                formState,
                questionaireMapping,
              );
              if (!isValid) {
                setFormState(newFormState);
                return;
              }
              onContinueClick();
            }}
            isDisabled={isPosting}
          >
            {isMobile ? `Proceed${isPosting ? 'ing' : ''}` : `Submit${isPosting ? 'ting' : ''}`}
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
}

export default Questionare;
