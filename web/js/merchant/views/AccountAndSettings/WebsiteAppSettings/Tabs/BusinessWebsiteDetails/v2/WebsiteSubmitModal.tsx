import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { useMobile } from 'common/hooks/useMobile';
import { Environments, ShowNotificationType, User as UserType } from 'common/typings';
import User from 'merchant/models/User';
import { updateSession as updateSessionReducer } from 'merchant/reducers/session';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import { merchantFetch } from 'merchant/utils/ajax';
import { closeModal as closeModalReducer } from 'merchant_common/reducers/modals';
import { showNotification as showNotificationReducer } from 'merchant_common/reducers/notifications';

import Loader from './components/Loader';
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
} from './tracking';
import {
  Platform,
  WebsiteSubmitModalSteps,
  WebsiteUpdateAutomationStatus,
  PolicyPageFormData,
  MainPageFormData,
} from './types';
import {
  getWebsiteCount,
  getWebsiteMainPageSubmitPayload,
  getWebsitePolicyPagesSubmitPayload,
  handleAppAndWebsiteSubmitForNonActivated,
  handleAppSubmitForActivated,
  isMainPageSubmitPayloadValid,
} from './utils';

interface WebsiteSubmitModalProps {
  isOpen: boolean;
  onDismiss: () => void;
  showNotification: ShowNotificationType;
  updateSession: (args: { user: UserType; mode?: string }) => void;
  user: UserType;
  mode: Environments;
}

const WebsiteSubmitModal: React.FC<WebsiteSubmitModalProps> = ({
  isOpen,
  onDismiss,
  showNotification,
  updateSession,
  user,
  mode,
}) => {
  const isMobile = useMobile();
  const saveWebsiteUpdate = useSaveWebsiteUpdate();
  const { currentStep, setCurrentStep, websiteUpdateData } = useBusinessWebsiteData();

  async function submitAppForActivated(formState) {
    try {
      await handleAppSubmitForActivated(formState);
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

  async function submitAppAndWebsiteForNonActivated(formState) {
    try {
      const response = await handleAppAndWebsiteSubmitForNonActivated(formState);
      const newUser = new User({
        ...user,
        business_website: response.data.business_website,
        has_key_access: response.data.has_key_access,
      });

      updateSession({
        user: newUser as unknown as UserType,
        mode,
      });

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

  const handleMainPageSubmit = async (formState: MainPageFormData) => {
    const [isInputValid, inputValidationError] = isMainPageSubmitPayloadValid(
      formState,
      user.business_website,
    );
    if (!isInputValid) {
      showNotification({
        type: 'error',
        message: inputValidationError,
      });
      return;
    }

    const { has_key_access: hasKeyAccess, business_website, isActivated } = user;

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
            if ((error as Error)?.message?.includes('liviness')) {
              trackBasicWebsiteCheckFailureModalLoad({
                basicCheckPassed: 'no',
                websiteCount: getWebsiteCount(user),
                actionFrom: 'websiteFlow',
                errorMessage: 'Website is not live',
                isWebsiteLive: false,
              });
              setCurrentStep(WebsiteSubmitModalSteps.MAIN_PAGE_ERROR);
              return;
            }
            /* @ts-expect-error error-message-check */
            const errorMessage = error?.message || 'Failed to submit details. Please try again.';
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
          },
        },
      );
    }
  };

  const handlePolicyPageSubmit = (formState: PolicyPageFormData) => {
    // save button
    // TODO: Check for validation logic before firing this event
    const pagesFilled: Array<string> = [];
    Object.entries(formState).forEach(([key, data]) => {
      if (data.value) {
        pagesFilled.push(key);
      }
    });
    const properties = {
      websiteCount: getWebsiteCount(user),
      pagesFilled,
      newWebsiteLink: websiteUpdateData?.main_page_url,
    };
    trackWebsitePrivacyPolicyModalRequestClicked({ ...properties, clickedButton: 'submit' });
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
          const { current_status, main_page_url } = response;
          if (current_status === WebsiteUpdateAutomationStatus.WORKFLOW_IN_PROGRESS) {
            trackBasicWebsiteCheckCompleteModalLoad({
              basicCheckPassed: 'no',
              newWebsiteLink: main_page_url,
              websiteCount: getWebsiteCount(user),
              isSuccess: false,
              isWorkflowRaised: true,
              actionFrom: 'policyPages',
            });
            setCurrentStep(WebsiteSubmitModalSteps.MANUAL_WF_RAISED);
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
        },
        onError: (error) => {
          /* @ts-expect-error error-message-check */
          const errorMessage = error?.message || 'Failed to submit details. Please try again.';
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
    // TODO: Maybe convert it to a controlled component?
    if (actionFrom === 'websiteFlow') {
      trackSubmitWebsiteDetailsVerificationRequestClick({
        ...properties,
        // TODO: check if additional details required on cancel button
        clickedButton: 'cancel',
      });
    } else {
      // TODO: check if additional details required on cancel button
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
        const newUser = new User({
          ...user,
          business_website,
          has_key_access,
          merchant: {
            ...user.merchant,
            has_key_access,
          },
        });
        updateSession({
          user: newUser as unknown as UserType,
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

  switch (currentStep) {
    case WebsiteSubmitModalSteps.ADD_MAIN_PAGE:
      return (
        <WebsiteInputModal
          isMobile={isMobile}
          isOpen={isOpen}
          onDismiss={handleCancel.bind(null, 'websiteFlow')}
          handleMainPageSubmit={handleMainPageSubmit}
          user={user}
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
        />
      );
    case WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_IN_PROGRESS:
    case WebsiteSubmitModalSteps.POLICY_PAGES_SUBMIT_IN_PROGRESS:
    case WebsiteSubmitModalSteps.MAIN_PAGE_SUBMIT_SUCCESS:
    case WebsiteSubmitModalSteps.MANUAL_WF_RAISED:
    case WebsiteSubmitModalSteps.WEBSITE_UPDATE_SUCCESS:
    case WebsiteSubmitModalSteps.MAIN_PAGE_ERROR:
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
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      showNotification: showNotificationReducer,
      closeModal: closeModalReducer,
      fetchWorkflowStatus: fetchWorkflowStatusReducer,
      updateSession: updateSessionReducer,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(WebsiteSubmitModal);
