import React, { useEffect, useMemo, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { useMobile } from 'common/hooks/useMobile';
import { Environments, ShowNotificationType, User as UserType } from 'common/typings';
import { noop } from 'common/utils/rzp-utils';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import { merchantFetch } from 'merchant/utils/ajax';
import { closeModal as closeModalReducer } from 'merchant_common/reducers/modals';
import { showNotification as showNotificationReducer } from 'merchant_common/reducers/notifications';

import CompletedPolicyPages from './components/CompletedPolicyPages';
import Loader from './components/Loader';
import MainPageLivenessError from './components/MainPageLivenessError';
import PreviewPages from './components/PreviewPolicyPages';
import Questionnaire from './components/Questionaire';
import WebsiteFixModal from './components/WebsiteFixModal';
import WebsiteInputModal from './components/WebsiteInputModal';
import useBusinessWebsiteData from './hooks/useBusinessWebsiteData';
import useSaveWebsiteUpdate from './hooks/useSaveWebsiteData';
import {
  trackWebsitePrivacyPolicyModalRequestClicked,
  trackSubmitWebsiteDetailsVerificationRequestClick,
  trackBasicWebsiteCheckCompleteModalLoad,
  trackBasicWebsiteCheckInProgressModalLoad,
  trackBasicWebsiteCheckFailureModalLoad,
  track,
  trackQuestionaire,
} from './tracking';
import {
  Platform,
  WebsiteSubmitModalSteps,
  WebsiteUpdateAutomationStatus,
  PolicyPageFormData,
  MainPageFormData,
  PolicyPagesSelection,
  WebsiteVerificationStatus,
  PartialPolicyPages,
  WebsitePolicyPages,
  PolicyPageCreationFormFieldType,
} from './types';
import {
  mainPageFormDefaultValue,
  getWebsiteCount,
  getWebsiteMainPageSubmitPayload,
  getWebsitePolicyPagesSubmitPayload,
  handleAppAndWebsiteSubmitForNonActivated,
  handleAppSubmitForActivated,
  isMainPageSubmitPayloadValid,
  getErrorMessage,
  getPayloadForPolicyPageSubmission,
  errorSuggestsToGeneratePolicyPages,
} from './utils';
import { getInitialPolicyPagesFormState } from './components/utils';
import { defaultPolicyPageCreationFormField } from './components/constants';
import { usePolicyPagesDetails } from './hooks/usePolicyPagesDetails';

interface WebsiteSubmitModalProps {
  isOpen: boolean;
  onDismiss: () => void;
  showNotification: ShowNotificationType;
  user: UserType;
  mode: Environments;
  org: { business_name: string };
  refetchData: VoidFunction;
  updateUserSession: (user: UserType) => void;
  refreshWebsiteData?: VoidFunction;
}

const WebsiteSubmitModal: React.FC<WebsiteSubmitModalProps> = ({
  isOpen,
  onDismiss,
  showNotification,
  user,
  mode,
  org,
  updateUserSession = () => {},
  refetchData = noop,
  refreshWebsiteData = noop,
}) => {
  const isMobile = useMobile();
  const saveWebsiteUpdate = useSaveWebsiteUpdate();
  const { mutate: savePolicyPagesMutate, isPosting: isSubmittingPolicyPages } =
    usePolicyPagesDetails();

  const {
    currentStep,
    setCurrentStep,
    websiteUpdateData: { main_page_url, website_verification_page_status } = {},
  } = useBusinessWebsiteData();
  const [mainPageFormState, setMainPageFormState] =
    useState<MainPageFormData>(mainPageFormDefaultValue);

  const { missingPages, missingPagesKeys, verifiedPages, verifiedPagesKeys } = useMemo(
    () => getInitialPolicyPagesFormState(website_verification_page_status),
    [website_verification_page_status],
  );
  const [policyFormState, setPolicyFormState] = useState<PolicyPageFormData>(missingPages);
  const [policyPagesToBeMade, setPolicyPagesToBeMade] = useState<PartialPolicyPages>([]);
  const [pagesBeingVerified, setPagesBeingVerified] = useState<WebsitePolicyPages[]>([]);

  const [policyCreationState, setPolicyCreationState] = useState<PolicyPageCreationFormFieldType>(
    defaultPolicyPageCreationFormField,
  );

  useEffect(() => {
    if (missingPagesKeys.length > 0) {
      setPolicyFormState(missingPages);
    }
  }, [missingPagesKeys]);

  async function submitAppForActivated(formState: MainPageFormData) {
    try {
      await handleAppSubmitForActivated(formState);
      refetchData();
      showNotification({
        type: 'success',
        message: 'Thank you for providing app.',
      });
      onDismiss();
    } catch (errorMessage) {
      showNotification({
        type: 'error',
        message: errorMessage as string,
      });
    }
  }

  async function submitAppAndWebsiteForNonActivated(formState: MainPageFormData) {
    try {
      const response = await handleAppAndWebsiteSubmitForNonActivated(formState);
      updateUserSession({
        ...user,
        business_website: response.data.business_website,
        has_key_access: response.data.has_key_access,
      });

      refetchData();

      showNotification({
        type: 'success',
        message: 'Thank you for providing website.',
      });
      onDismiss();
    } catch (errorMessage) {
      showNotification({
        type: 'error',
        message: errorMessage as string,
      });
    }
  }

  const createTermsPolicyPage = async () => {
    savePolicyPagesMutate(
      getPayloadForPolicyPageSubmission({
        mode,
        formState: policyCreationState,
        policyPagesToBeMade: [WebsitePolicyPages.TERMS],
      }),
    )
      .then(() => {
        trackQuestionaire('Submitted Terms');
        setCurrentStep(WebsiteSubmitModalSteps.POLICY_PAGES_PREVIEW);
      })
      .catch((error) => {
        const message = getErrorMessage(error?.message);
        trackQuestionaire('Failed Terms', {
          errorMessage: message,
          backendErrorMsg: error?.message,
        });
        showNotification({
          type: 'error',
          message,
        });
      });
  };

  const setNextStepForPolicyPages = ({
    newPolicyPagesToBeMade,
  }: {
    newPolicyPagesToBeMade: PartialPolicyPages;
  }) => {
    setPolicyPagesToBeMade(newPolicyPagesToBeMade);
    if (
      newPolicyPagesToBeMade.length === 1 &&
      newPolicyPagesToBeMade[0] === WebsitePolicyPages.TERMS
    ) {
      createTermsPolicyPage();
    } else {
      setCurrentStep(WebsiteSubmitModalSteps.POLICY_PAGES_CREATION);
    }
  };

  const handleMainPageSubmit = async (formState: MainPageFormData) => {
    const {
      has_key_access: hasKeyAccess,
      business_website,
      additional_websites,
      isActivated,
      activation_status: activationStatus,
    } = user;
    const isKLAMerchant = !Boolean(hasKeyAccess);
    const alreadyAddedWebsites = [business_website, ...(additional_websites || [])].filter(
      Boolean,
    ) as string[];
    const [isInputValid, inputValidationError] = isMainPageSubmitPayloadValid({
      formState,
      userWebsites: alreadyAddedWebsites,
      isKLAMerchant,
    });
    if (!isInputValid) {
      showNotification({
        type: 'error',
        message: inputValidationError,
      });
      return;
    }

    // old first time add flow => here it is being used for non activated user for app and website submit
    const shouldUseNonActivatedAPI = formState.platform.value === Platform.WEBSITE && !isActivated;

    // old edit flow => here its being used for app submit
    const oldEditFlow = hasKeyAccess || (business_website && isActivated);

    const isCredsFilled = formState.credsUsername.value && formState.credsPassword.value;
    const analyticsProperties = {
      loginRequired: formState.requireCreds.value,
      acceptOn: formState.platform.value,
      loginCredentialsEntered:
        // eslint-disable-next-line i18n-rules/no-hardcoded-i18n-types
        formState.requireCreds.value === 'yes' ? (isCredsFilled ? 'yes' : 'no') : 'na',
      newWebsiteLink: formState.url.value,
      websiteCount: getWebsiteCount(user),
      stateIsActivated: isActivated,
      stateHasKeyAccess: hasKeyAccess,
      stateActivationStatus: activationStatus,
    };
    trackSubmitWebsiteDetailsVerificationRequestClick({
      ...analyticsProperties,
      clickedButton: 'submit',
    });

    if (formState.platform.value === Platform.APP || shouldUseNonActivatedAPI) {
      if (oldEditFlow && !shouldUseNonActivatedAPI) {
        await submitAppForActivated(formState);
      } else {
        await submitAppAndWebsiteForNonActivated(formState);
      }
      refreshWebsiteData();
    } else {
      // save button
      setCurrentStep(WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_IN_PROGRESS);
      trackBasicWebsiteCheckInProgressModalLoad({
        ...analyticsProperties,
        actionFrom: 'websiteFlow',
      });
      saveWebsiteUpdate.mutate(
        getWebsiteMainPageSubmitPayload({
          formState,
          mode,
        }),
        {
          onSuccess: (response) => {
            refreshWebsiteData();
            // istanbul ignore else
            if (response?.current_status) {
              trackBasicWebsiteCheckCompleteModalLoad({
                basicCheckPassed: 'no',
                newWebsiteLink: response?.main_page_url,
                websiteCount: getWebsiteCount(user),
                actionFrom: 'websiteFlow',
                isWebsiteLive: true,
              });
              setCurrentStep(WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_SUCCESS);
            } else {
              // TODO: this required backend input, will be handled in next PR
              const errorMessage = 'Invalid Response. Please try again.';
              trackBasicWebsiteCheckFailureModalLoad({
                basicCheckPassed: 'no',
                websiteCount: getWebsiteCount(user),
                actionFrom: 'websiteFlow',
                errorMessage,
              });
              showNotification({
                type: 'error',
                message: errorMessage,
              });
              onDismiss();
            }
          },
          onError: (error) => {
            refreshWebsiteData();
            if ((error as Error)?.message?.includes('liviness')) {
              trackBasicWebsiteCheckFailureModalLoad({
                basicCheckPassed: 'no',
                websiteCount: getWebsiteCount(user),
                actionFrom: 'websiteFlow',
                errorMessage: 'Website is not live',
                isWebsiteLive: false,
              });
              setCurrentStep(WebsiteSubmitModalSteps.MAIN_PAGE_LIVENESS_ERROR);
              return;
            }
            /* @ts-expect-error error-message-check */
            const errorMessage = getErrorMessage(error?.message);
            trackBasicWebsiteCheckFailureModalLoad({
              basicCheckPassed: 'no',
              websiteCount: getWebsiteCount(user),
              actionFrom: 'websiteFlow',
              errorMessage,
              backendErrorMsg: error?.message,
            });
            showNotification({
              type: 'error',
              message: errorMessage,
            });
            onDismiss();
          },
        },
      );
    }
  };

  const handlePolicyPageSubmit = (formState: PolicyPageFormData) => {
    // save button
    const pagesFilled: PartialPolicyPages = [];
    const newPolicyPagesToBeMade: PartialPolicyPages = [];
    const notApplicablePages: PartialPolicyPages = [];
    Object.entries(formState).forEach(([key, data]) => {
      if (data.radioValue === PolicyPagesSelection.YES) {
        pagesFilled.push(key as WebsitePolicyPages);
      } else if (data.radioValue === PolicyPagesSelection.NO) {
        newPolicyPagesToBeMade.push(key as WebsitePolicyPages);
      } else if (data.radioValue === PolicyPagesSelection.NA) {
        notApplicablePages.push(key as WebsitePolicyPages);
      }
    });
    const properties = {
      websiteCount: getWebsiteCount(user),
      pagesFilled,
      newWebsiteLink: main_page_url,
    };
    trackWebsitePrivacyPolicyModalRequestClicked({ ...properties, clickedButton: 'submit' });

    setPagesBeingVerified(Object.keys(formState) as WebsitePolicyPages[]);

    track({
      objectName: 'Policy Page Continue Option',
      properties: {
        policyPageCountTotal: Object.keys(formState).length,
        websiteCount: getWebsiteCount(user),
        policyPageRZPCreate: Object.values(formState).filter(
          ({ radioValue }) => radioValue === PolicyPagesSelection.NO,
        ).length,
      },
    });

    // if no user provided links exist, then directly go to the policy pages creation step
    if (pagesFilled.length === 0 && notApplicablePages.length === 0) {
      setNextStepForPolicyPages({ newPolicyPagesToBeMade });
      return;
    }
    setCurrentStep(WebsiteSubmitModalSteps.POLICY_PAGES_SUBMIT_IN_PROGRESS);
    trackBasicWebsiteCheckInProgressModalLoad({
      ...properties,
      actionFrom: 'policyPages',
    });

    saveWebsiteUpdate.mutate(
      getWebsitePolicyPagesSubmitPayload({
        formState,
        mode,
      }),
      {
        onSuccess: (response) => {
          refreshWebsiteData();
          const { current_status, main_page_url, website_verification_page_status } = response;
          if (current_status === WebsiteUpdateAutomationStatus.IN_PROGRESS) {
            pagesFilled.forEach((page) => {
              if (
                website_verification_page_status &&
                website_verification_page_status[page]?.verified !==
                  WebsiteVerificationStatus.PASSED &&
                website_verification_page_status[page]?.verified !==
                  WebsiteVerificationStatus.NOT_APPLICABLE
              ) {
                newPolicyPagesToBeMade.push(page);
              }
            });

            trackBasicWebsiteCheckCompleteModalLoad({
              basicCheckPassed: 'no',
              newWebsiteLink: main_page_url,
              websiteCount: getWebsiteCount(user),
              isSuccess: false,
              isWorkflowRaised: true,
              actionFrom: 'policyPages',
            });
            setNextStepForPolicyPages({ newPolicyPagesToBeMade });
          } else if (current_status === WebsiteUpdateAutomationStatus.COMPLETED) {
            updateMainPageUrl();
            trackBasicWebsiteCheckCompleteModalLoad({
              basicCheckPassed: 'yes',
              newWebsiteLink: main_page_url,
              websiteCount: getWebsiteCount(user),
              isSuccess: true,
              isWorkflowRaised: false,
              actionFrom: 'policyPages',
            });
            setCurrentStep(WebsiteSubmitModalSteps.WEBSITE_UPDATE_SUCCESS);
          } else if (current_status === WebsiteUpdateAutomationStatus.WORKFLOW_IN_PROGRESS) {
            setCurrentStep(WebsiteSubmitModalSteps.WEBSITE_UPDATE_WORKFLOW_RAISED);
            trackBasicWebsiteCheckCompleteModalLoad({
              basicCheckPassed: 'yes',
              newWebsiteLink: main_page_url,
              websiteCount: getWebsiteCount(user),
              isSuccess: true,
              isWorkflowRaised: true,
              actionFrom: 'policyPages',
            });
          } else {
            if (
              WebsiteUpdateAutomationStatus.WEBSITE_UPDATE_FAILED === current_status ||
              WebsiteUpdateAutomationStatus.WORKFLOW_CREATION_FAILED === current_status
            ) {
              // we don't need to show toast message for these statuses as we show dedicated banner
              onDismiss();
            } else {
              const errorMessage = 'Invalid Response. Please try again.';
              trackBasicWebsiteCheckFailureModalLoad({
                basicCheckPassed: 'no',
                websiteCount: getWebsiteCount(user),
                actionFrom: 'policyPages',
                errorMessage,
              });
              showNotification({
                type: 'error',
                message: errorMessage,
              });
              onDismiss();
            }
          }
        },
        onError: (error) => {
          refreshWebsiteData();
          /* @ts-expect-error error-message-check */
          const errorMessage = getErrorMessage(error?.message);
          trackBasicWebsiteCheckFailureModalLoad({
            basicCheckPassed: 'no',
            websiteCount: getWebsiteCount(user),
            actionFrom: 'policyPages',
            errorMessage,
            backendErrorMsg: error?.message,
          });
          showNotification({
            type: 'error',
            message: errorMessage,
          });
          // do not dismiss modal if error is related to policy pages generation and go back to previous step
          if (errorSuggestsToGeneratePolicyPages.includes(error?.message)) {
            setCurrentStep(WebsiteSubmitModalSteps.ADD_MISSING_POLICY_PAGES);
          } else {
            onDismiss();
          }
        },
      },
    );
  };

  const handleCancel = (actionFrom: string) => {
    // used to close modal and track cancel button on modals that collect user input
    onDismiss();
    const properties = {
      websiteCount: getWebsiteCount(user),
    };
    if (actionFrom === 'websiteFlow') {
      trackSubmitWebsiteDetailsVerificationRequestClick({
        ...properties,
        clickedButton: 'cancel',
      });
    } else {
      trackWebsitePrivacyPolicyModalRequestClicked({ ...properties, clickedButton: 'cancel' });
    }
  };

  async function updateMainPageUrl() {
    try {
      const response = await merchantFetch({
        url: 'merchant/activation',
        mode,
      });

      if (response.data) {
        const {
          business_website,
          merchant: { has_key_access },
        } = response.data;

        updateUserSession({
          ...user,
          business_website,
          has_key_access,
        });
      } else {
        showNotification({
          type: 'error',
          message: 'Something went wrong. Try again later',
        });
      }
    } catch (err) {
      showNotification({
        type: 'error',
        message: 'Something went wrong. Try again later',
      });
    }
  }

  const onCreateAllPolicyPagesButtonClick = (formState: PolicyPageFormData) => {
    const newPolicyPagesToBeMade = Object.keys(formState) as PartialPolicyPages;
    setPagesBeingVerified(Object.keys(formState) as WebsitePolicyPages[]);
    setNextStepForPolicyPages({ newPolicyPagesToBeMade });
  };

  const onWebsiteChangeClick = () => {
    setCurrentStep(WebsiteSubmitModalSteps.ADD_MAIN_PAGE);
  };

  switch (currentStep) {
    case WebsiteSubmitModalSteps.ADD_MAIN_PAGE:
      return (
        <WebsiteInputModal
          isMobile={isMobile}
          isOpen={isOpen}
          onDismiss={handleCancel.bind(null, 'websiteFlow')}
          handleMainPageSubmit={handleMainPageSubmit}
          user={user}
          formState={mainPageFormState}
          setFormState={setMainPageFormState}
        />
      );
    case WebsiteSubmitModalSteps.ADD_MISSING_POLICY_PAGES:
      return (
        <WebsiteFixModal
          isMobile={isMobile}
          isOpen={isOpen}
          onDismiss={handleCancel.bind(null, 'policyPages')}
          handlePolicyPageSubmit={handlePolicyPageSubmit}
          user={user}
          org={org}
          onCreateAllPolicyPagesButtonClick={onCreateAllPolicyPagesButtonClick}
          formState={policyFormState}
          setFormState={setPolicyFormState}
          missingPages={missingPages}
          missingPagesKeys={missingPagesKeys}
          verifiedPages={verifiedPages}
          verifiedPagesKeys={verifiedPagesKeys}
          isSubmittingPolicyPages={isSubmittingPolicyPages}
        />
      );
    case WebsiteSubmitModalSteps.POLICY_PAGES_CREATION:
      return (
        <Questionnaire
          isMobile={isMobile}
          isOpen={isOpen}
          setCurrentStep={setCurrentStep}
          policyPagesToBeMade={policyPagesToBeMade}
          mode={mode}
          org={org}
          showNotification={showNotification}
          formState={policyCreationState}
          setFormState={setPolicyCreationState}
        />
      );
    case WebsiteSubmitModalSteps.POLICY_PAGES_PREVIEW:
      return (
        <PreviewPages
          isMobile={isMobile}
          isOpen={isOpen}
          setCurrentStep={setCurrentStep}
          policyPagesToBeMade={policyPagesToBeMade}
          mode={mode}
          showNotification={showNotification}
          onSuccess={refreshWebsiteData}
        />
      );
    case WebsiteSubmitModalSteps.POLICY_PAGES_COMPLETE:
      return (
        <CompletedPolicyPages
          isMobile={isMobile}
          isOpen={isOpen}
          onDismiss={onDismiss}
          pagesBeingVerified={pagesBeingVerified}
        />
      );

    case WebsiteSubmitModalSteps.MAIN_PAGE_LIVENESS_ERROR:
      return (
        <MainPageLivenessError
          isMobile={isMobile}
          isOpen={isOpen}
          onDismiss={onDismiss}
          onWebsiteChangeClick={onWebsiteChangeClick}
          mainPageFormState={mainPageFormState}
        />
      );

    case WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_IN_PROGRESS:
    case WebsiteSubmitModalSteps.POLICY_PAGES_SUBMIT_IN_PROGRESS:
    case WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_SUCCESS:
    case WebsiteSubmitModalSteps.WEBSITE_UPDATE_SUCCESS:
    case WebsiteSubmitModalSteps.WEBSITE_UPDATE_WORKFLOW_RAISED:
      return (
        <Loader
          isMobile={isMobile}
          isOpen={isOpen}
          onDismiss={onDismiss}
          variant={currentStep}
          onClick={onDismiss}
        />
      );

    default:
      return null;
  }
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    mode: state.session.mode,
    org: state.session.org,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      showNotification: showNotificationReducer,
      closeModal: closeModalReducer,
      fetchWorkflowStatus: fetchWorkflowStatusReducer,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(WebsiteSubmitModal);
