import React, { useState } from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade/src/atoms/View';
import Heading from '@razorpay/blade/src/atoms/Heading';
import Flex from '@razorpay/blade/src/atoms/Flex';
import Space from '@razorpay/blade/src/atoms/Space';
import Icon from '@razorpay/blade/src/atoms/Icon';
import Button from '@razorpay/blade/src/atoms/Button';
import Link from '@commander/shield/src/shared/Link';
import { FullPageLoader } from 'v2/components/Loader';
import { withRouter, RouteComponentProps } from 'react-router-dom';
// import { getMerchantFlow, isL1Submitted, getPoiVerificationStatus } from '../../services/utils';
import { Tabs, Tab } from '../../../../../components/Tabs';
import {
  // isVisible,
  useActivationFormState,
} from '../../context/store';
import useActivation from '../../hooks/useActivation';
import BankDetails from '../../BankDetails';
import ContactDetails from '../../ContactDetails';
import BusinessOverview from '../../BusinessOverview';
import BusinessDetails from '../../BusinessDetails';
import DocumentUpload from '../../DocumentUpload';
import {
  EnableSettlements as EnableSettlementModal,
  SubmitForm as SubmitFormModal,
  Dedupe as DedupeModal,
} from '../../ActivationModals';
import SaveAndExitModal from '../../SaveAndExitModal';
import FAQs from '../../FAQs/FAQs';
import { checkIfDedupe } from '../../services/utils';
// import { L1_FORM_FIELD_NAMES } from '../../Constants/OnboardingConstants';
import { analyticsTrack } from '../../../../../services/tracking/segment';

import { useApp } from 'v2/context/App';
type NextTextT = 'Submit And Verify' | 'Save And Verify' | 'Next';

const StyledFooter = styled(View)`
  box-sizing: border-box;
  width: 100%;
  position: fixed;
  bottom: 0;
  padding: 16px;
  background-color: ${({ theme }) => theme.colors.background['200']};
  border-top: 1px solid rgba(22, 47, 86, 0.1);
`;

const StyledActivationForm = styled(View)`
  min-height: 100vh;
  background-color: ${({ theme }) => theme.colors.background[400]};
`;

const StyledHeader = styled(View)`
  background-color: ${({ theme }) => theme.colors.background[200]};
`;

const ActivationForm: React.FC<RouteComponentProps> = ({ history }) => {
  const {
    status,
    data,
    postData,
    // instantPostData
  } = useActivation();
  const { user } = useApp();
  const isContactDetailsCompleted = useActivationFormState(
    (state) => state.isContactDetailsCompleted,
  );
  const isBusinessOverviewCompleted = useActivationFormState(
    (state) => state.isBusinessOverviewCompleted,
  );
  const isBusinessDetailsCompleted = useActivationFormState(
    (state) => state.isBusinessDetailsCompleted,
  );
  const isBankAndCompanyDetailsCompleted = useActivationFormState(
    (state) => state.isBankAndCompanyDetailsCompleted,
  );
  const isDocumentsUploadCompleted = useActivationFormState(
    (state) => state.isDocumentsUploadCompleted,
  );
  // const isL1Complete = [
  //   isContactDetailsCompleted,
  //   isBusinessOverviewCompleted,
  //   isBusinessDetailsCompleted,
  // ].every((isComplete) => isComplete);

  const setIsOpen = useActivationFormState((state) => state.setIsFAQOpen);
  const activeTabId = useActivationFormState((state) => state.active_tab_id);
  const setActiveTabId = useActivationFormState((state) => state.setActiveTabId);
  const [
    isEnableSettlementModalOpen,
    // setIsEnableSettlementModalOpen
  ] = useState(false);
  const [isSubmitFormModalOpen, setIsSubmitFormModalOpen] = useState(false);
  const [isSaveAndExitModalOpen, setIsSaveAndExitModalOpen] = useState(false);
  const [isDedupeModalOpen, setIsDedupeModalOpen] = useState(false);

  // const isUnregPoiStatus = getPoiVerificationStatus(data);
  if (status === 'loading') {
    return <FullPageLoader />;
  }

  if (status === 'error') {
    return <div>Something went wrong</div>;
  }

  // const merchantFlow = getMerchantFlow(data.business_type, data.activation_flow);
  const {
    // onboarding_milestone,
    can_submit,
  } = data;
  // const submitL1 = () => {
  //   const l1ReqData = L1_FORM_FIELD_NAMES.reduce((acc, key) => {
  //     if (isVisible(key, data)) {
  //       acc[key] = data[key];
  //     }
  //     return acc;
  //   }, {});

  //   instantPostData(l1ReqData).then((res) => {
  //     if (res && res.onboarding_milestone === 'L1') {
  //       setIsEnableSettlementModalOpen(true);
  //     }
  //   });
  // };
  const submitL2 = () => {
    analyticsTrack({
      objectName: 'SignUp',
      actionName: 'submit form',
      screen: 'home page',
      user,
      eventAction: 'initiated',
      properties: {
        clickSource: 'submit-and-verify',
      },
    });
    postData({ submit: 1 }).then((res) => {
      const isDedupeState = checkIfDedupe(res);
      if (res && res.submitted && !isDedupeState) {
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'submit form',
          screen: 'home page',
          eventAction: 'success',
          user,
          properties: {
            clickSource: 'submit-and-verify',
          },
        });
        setIsSubmitFormModalOpen(true);
      }
      if (isDedupeState) {
        setIsDedupeModalOpen(true);
      }
    });
  };
  const getNextText = (): NextTextT => {
    if (activeTabId === 'documents') {
      return 'Submit And Verify';
    }
    // if (
    //   activeTabId === 'business_details' &&
    //   (!isL1Submitted(onboarding_milestone) || isUnregPoiStatus)
    // ) {
    //   if (merchantFlow === 'greylist') {
    //     return 'Next';
    //   }
    //   return 'Save And Verify';
    // }
    return 'Next';
  };
  const handleNextClick = () => {
    switch (activeTabId) {
      case 'contact_details':
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'contact info',
          screen: 'home page',
          user,
          eventAction: 'initiated',
        });
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'save modifications',
          screen: 'home page',
          eventAction: 'initiated',
          user,
          properties: {
            clickSource: 'save-next',
            currentTabName: 'contact details',
          },
        });
        setActiveTabId('business_overview');
        break;
      case 'business_overview':
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'business overview',
          screen: 'home page',
          eventAction: 'initiated',
          user,
        });
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'save modifications',
          screen: 'home page',
          eventAction: 'initiated',
          user,
          properties: {
            clickSource: 'save-next',
            currentTabName: 'business overview',
          },
        });
        setActiveTabId('business_details');
        break;
      case 'business_details':
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'business details',
          screen: 'home page',
          eventAction: 'initiated',
          user,
        });
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'save modifications',
          screen: 'home page',
          eventAction: 'initiated',
          user,
          properties: {
            clickSource: 'save-next',
            currentTabName: 'business details',
          },
        });
        // if (
        //   (isL1Submitted(onboarding_milestone) && !isUnregPoiStatus) ||
        //   merchantFlow === 'greylist'
        // ) {
        setActiveTabId('bank_details');
        // }
        // if (!isL1Submitted(onboarding_milestone) || isUnregPoiStatus) {
        //   submitL1();
        // }
        break;
      case 'bank_details':
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'bank details',
          screen: 'home page',
          eventAction: 'initiated',
          user,
        });
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'save modifications',
          screen: 'home page',
          eventAction: 'initiated',
          user,
          properties: {
            clickSource: 'save-next',
            currentTabName: 'bank details',
          },
        });
        setActiveTabId('documents');
        break;
      case 'documents':
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'documents',
          screen: 'home page',
          eventAction: 'initiated',
          user,
        });
        analyticsTrack({
          objectName: 'SignUp',
          actionName: 'save modifications',
          screen: 'home page',
          eventAction: 'initiated',
          user,
          properties: {
            clickSource: 'save-next',
            currentTabName: 'document',
          },
        });
        submitL2();
        break;
      default:
        break;
    }
  };
  const onBack = () => {
    history.push('/onboarding/steps');
  };

  const isAllTabCompleted =
    isContactDetailsCompleted &&
    isBusinessOverviewCompleted &&
    isBusinessDetailsCompleted &&
    isBankAndCompanyDetailsCompleted &&
    isDocumentsUploadCompleted;

  if (isContactDetailsCompleted) {
    analyticsTrack({
      objectName: 'SignUp',
      actionName: 'tab filled',
      screen: 'home page',
      eventAction: 'success',
      user,
      properties: {
        filed_tab_details: 'Contact Details',
        tab_filled: 'yes',
      },
    });
  }
  if (isBusinessOverviewCompleted) {
    analyticsTrack({
      objectName: 'SignUp',
      actionName: 'tab filled',
      screen: 'home page',
      eventAction: 'success',
      user,
      properties: {
        filed_tab_details: 'Business Overview',
        tab_filled: 'yes',
      },
    });
  }
  if (isBusinessDetailsCompleted) {
    analyticsTrack({
      objectName: 'SignUp',
      actionName: 'tab filled',
      screen: 'home page',
      eventAction: 'success',
      user,
      properties: {
        filed_tab_details: 'Business Details',
        tab_filled: 'yes',
      },
    });
  }
  if (isBankAndCompanyDetailsCompleted) {
    analyticsTrack({
      objectName: 'SignUp',
      actionName: 'tab filled',
      screen: 'home page',
      eventAction: 'success',
      user,
      properties: {
        filed_tab_details: 'Bank Details',
        tab_filled: 'yes',
      },
    });
  }
  if (isDocumentsUploadCompleted) {
    analyticsTrack({
      objectName: 'SignUp',
      actionName: 'tab filled',
      screen: 'home page',
      eventAction: 'success',
      user,
      properties: {
        filed_tab_details: 'Document',
        tab_filled: 'yes',
      },
    });
  }

  const getTabs = () => {
    const tabs = [
      <Tab
        key="contact_details"
        title="Contact Details"
        tabId="contact_details"
        completed={isContactDetailsCompleted}
      >
        <ContactDetails isFormLocked={!!data.locked} />
      </Tab>,
      <Tab
        key="business_overview"
        title="Business Overview"
        tabId="business_overview"
        completed={isBusinessOverviewCompleted}
      >
        <BusinessOverview isFormLocked={!!data.locked} />
      </Tab>,
      <Tab
        key="business_details"
        title="Business Details"
        tabId="business_details"
        completed={isBusinessDetailsCompleted}
      >
        <BusinessDetails isFormLocked={!!data.locked} />
      </Tab>,
      <Tab
        key="bank_details"
        title="Bank Details"
        tabId="bank_details"
        completed={isBankAndCompanyDetailsCompleted}
      >
        <BankDetails isFormLocked={!!data.locked} />
      </Tab>,
      <Tab
        key="documents"
        title="Documents"
        tabId="documents"
        completed={isDocumentsUploadCompleted}
      >
        <DocumentUpload isFormLocked={!!data.locked} />
      </Tab>,
    ];
    // if (
    //   merchantFlow === 'greylist'
    //  || (isL1Submitted(onboarding_milestone) && !isUnregPoiStatus)
    // ) {
    //   tabs.push(
    //     <Tab
    //       key="bank_details"
    //       title="Bank Details"
    //       tabId="bank_details"
    //       completed={isBankAndCompanyDetailsCompleted}
    //     >
    //       <BankDetails />
    //     </Tab>,
    //     <Tab
    //       key="documents"
    //       title="Documents"
    //       tabId="documents"
    //       completed={isDocumentsUploadCompleted}
    //     >
    //       <DocumentUpload />
    //     </Tab>,
    //   );
    // }
    return tabs;
  };
  return (
    <View>
      {/* Header */}
      <Space padding={[1.75, 1.5, 1.25, 0.75]}>
        <Flex justifyContent="space-between" alignItems="center">
          <StyledHeader>
            <Flex flexDirection="row" justifyContent="left">
              <View>
                <Space padding={[0, 0.5, 0, 0]}>
                  <View onClick={() => onBack()}>
                    <Icon name="chevronLeft" size="large" fill="shade.800" />
                  </View>
                </Space>
                <View>
                  <Heading size="large">
                    Account Details
                    {/* {merchantFlow === 'greylist'
                      ? 'Account Activation'
                      : !isL1Submitted(onboarding_milestone) || isUnregPoiStatus
                      ? 'Enable Payments'
                      : 'Enable Settlements'} */}
                  </Heading>
                </View>
              </View>
            </Flex>
            <Link
              onClick={() => {
                if (!data.submitted) {
                  setIsSaveAndExitModalOpen(true);
                } else {
                  history.push('/dashboard');
                }

                analyticsTrack({
                  objectName: 'SignUp',
                  actionName: 'form fill',
                  screen: 'home page',
                  eventAction: 'dropped',
                  user,
                });
              }}
              size="xsmall"
              weight="bold"
            >
              Save and Exit
            </Link>
          </StyledHeader>
        </Flex>
      </Space>
      {/* Form Tabs */}
      <Space margin={[0, 0, 8.75, 0]}>
        <StyledActivationForm>
          <Tabs
            activeTabId={activeTabId}
            onChange={(tabId) => {
              if (typeof tabId === 'string') {
                setActiveTabId(tabId);
                analyticsTrack({
                  objectName: 'SignUp',
                  actionName: 'nav action',
                  screen: 'home page',
                  eventAction: 'initiated',
                  user,
                  properties: {
                    currentTabName: tabId,
                  },
                });
              }
            }}
          >
            {getTabs()}
          </Tabs>
        </StyledActivationForm>
      </Space>
      {/* Footer */}
      <Flex justifyContent="space-between">
        <StyledFooter>
          <Button
            onClick={() => {
              setIsOpen(true);
              analyticsTrack({
                objectName: 'SignUp',
                actionName: 'faq',
                screen: 'home page',
                eventAction: 'initiated',
                user,
              });
            }}
            variant="tertiary"
          >
            FAQs
          </Button>
          <Button
            onClick={() => handleNextClick()}
            disabled={
              activeTabId === 'documents' ? !can_submit || !isAllTabCompleted : false
              // (activeTabId === 'business_details' &&
              //     !isL1Complete &&
              //     merchantFlow !== 'greylist' &&
              //     !isL1Submitted(onboarding_milestone)) ||
              //   isUnregPoiStatus
            }
            icon="chevronRight"
            iconAlign="right"
          >
            {getNextText()}
          </Button>
        </StyledFooter>
      </Flex>
      <EnableSettlementModal isOpen={isEnableSettlementModalOpen} />
      <SubmitFormModal isOpen={isSubmitFormModalOpen} isAutoKycDone={data.isAutoKycDone} />
      <DedupeModal isOpen={isDedupeModalOpen} />
      <SaveAndExitModal
        isOpen={isSaveAndExitModalOpen}
        onClose={() => setIsSaveAndExitModalOpen(false)}
      />
      <FAQs />
    </View>
  );
};

export default withRouter(ActivationForm);
