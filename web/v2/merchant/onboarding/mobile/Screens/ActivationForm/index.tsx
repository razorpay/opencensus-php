import React, { useState } from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import Button from '@razorpay/blade-old/src/atoms/Button';
import Link from '@razorpay/commander-shield/src/shared/Link';
import { FullPageLoader } from 'v2/components/Loader';
import { withRouter, RouteComponentProps } from 'react-router-dom';
import {
  isL1Submitted,
  getPoiVerificationStatus,
  hasSelectedBlacklistCategory,
  isUnregisteredBusiness,
} from 'v2/merchant/onboarding/mobile/services/utils';
import { Tabs, Tab } from '../../../../../components/Tabs';
import { useActivationFormState } from '../../context/store';
import useActivation from '../../hooks/useActivation';
import useBusinessCategory from '../../hooks/useBusinessCategory';
import BankDetails from '../../BankDetails';
import ContactDetails from '../../ContactDetails';
import BusinessOverview from '../../BusinessOverview';
import BusinessDetails from '../../BusinessDetails';
import DocumentUpload from '../../DocumentUpload';
import SaveAndExitModal from '../../SaveAndExitModal';
import FAQs from '../../FAQs/FAQs';
import { checkIfDedupe } from '../../services/utils';
import { analyticsTrack } from '../../../../../services/tracking/segment';
import { ActivationModal, ModalTypeT } from '../../ActivationModals';
import { useApp } from 'v2/context/App';
import { switchMode } from 'v2/services/mode';

type NextTextT = 'Submit And Verify' | 'Submit KYC' | 'Next';

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
  const { status: activationStatus, data, postData } = useActivation();
  const { user, experiments } = useApp();
  const [status, businessCategoriesData] = useBusinessCategory('');
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

  const setIsOpen = useActivationFormState((state) => state.setIsFAQOpen);
  const activeTabId = useActivationFormState((state) => state.active_tab_id);
  const setActiveTabId = useActivationFormState((state) => state.setActiveTabId);
  const [isSaveAndExitModalOpen, setIsSaveAndExitModalOpen] = useState(false);
  const [isModalOpen, setIsModalOpen] = useState<boolean>(false);
  const [modalType, setModalType] = useState<ModalTypeT>('');

  if (activationStatus === 'loading') {
    return <FullPageLoader />;
  }

  if (activationStatus === 'error') {
    return <div>Something went wrong</div>;
  }

  const {
    activation_form_milestone,
    can_submit,
    submitted,
    locked,
    poi_verification_status,
    activation_status,
  } = data;

  const isInstantActivationEnabled = experiments.isInstantActivationEnabled;

  const merchantDedupeStatus = checkIfDedupe({ ...data, isInstantActivationEnabled });
  const isDedupe = merchantDedupeStatus === 'blocked';

  const isBlackListCategory =
    status === 'success' && hasSelectedBlacklistCategory(data, businessCategoriesData);

  const submitL1 = () => {
    analyticsTrack({
      objectName: 'SignUp L1',
      actionName: 'submit form',
      screen: 'home page',
      user,
      eventAction: 'initiated',
      properties: {
        clickSource: 'submit-and-verify',
      },
      activationType: 'act',
    });
    postData({ activation_form_milestone: 'L1' }).then((res) => {
      if (res && res.activation_form_milestone === 'L1') {
        const dedupeStatus = checkIfDedupe({ ...res, isInstantActivationEnabled });
        if (dedupeStatus === 'blocked') {
          setModalType('dedupe');
        } else if (
          isUnregisteredBusiness(res.business_type) &&
          res.poi_verification_status === 'initiated' &&
          experiments.canSkipPoiValidation
        ) {
          setModalType('poi_initiated');
        } else if (res.activated && res.activation_status === 'instantly_activated') {
          setModalType('payment_enable');
          switchMode(user.current, 'live');
        } else if (dedupeStatus === 'partial_match' || res?.activation_flow === 'greylist') {
          setModalType('payment_disable');
        }
        setIsModalOpen(true);
      }
    });
  };

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
    const payload = isInstantActivationEnabled
      ? { activation_form_milestone: 'L2' }
      : { submit: 1 };
    postData(payload).then((res) => {
      if (res) {
        const dedupeStatus = checkIfDedupe({ ...res, isInstantActivationEnabled });
        if (res.submitted && dedupeStatus === 'blocked') {
          setModalType('dedupe');
        } else if (!res.business_website && experiments.canGenerateTnCPage) {
          setModalType('tnc');
        } else {
          setModalType('under_review');
        }
        setIsModalOpen(true);
      }
    });
  };
  const getNextText = (): NextTextT => {
    if (activeTabId === 'documents') {
      return 'Submit And Verify';
    }
    if (
      activeTabId === 'business_details' &&
      isInstantActivationEnabled &&
      (!isL1Submitted(activation_form_milestone) ||
        isDedupe ||
        poi_verification_status === 'initiated')
    ) {
      return 'Submit KYC';
    }
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
        if (!isL1Submitted(activation_form_milestone) && isInstantActivationEnabled) {
          submitL1();
        } else {
          setActiveTabId('bank_details');
        }
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

  const isL1AllTabComplete =
    isContactDetailsCompleted && isBusinessOverviewCompleted && isBusinessDetailsCompleted;

  const isAllTabCompleted =
    isL1AllTabComplete && isBankAndCompanyDetailsCompleted && isDocumentsUploadCompleted;

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

  const canSubmitActivationForm = (): boolean => {
    if (activeTabId === 'documents') {
      return (
        !can_submit ||
        !isAllTabCompleted ||
        (submitted && (locked || activation_status === 'needs_clarification'))
      );
    } else if (
      activeTabId === 'business_details' &&
      !isL1Submitted(activation_form_milestone) &&
      isInstantActivationEnabled
    ) {
      return (
        !isL1AllTabComplete ||
        isBlackListCategory ||
        (getPoiVerificationStatus(data) && !experiments.canSkipPoiValidation)
      );
    } else {
      return (
        false ||
        (activeTabId === 'business_details' &&
          (isDedupe || poi_verification_status === 'initiated') &&
          isInstantActivationEnabled)
      );
    }
  };

  const isFormLocked = () => {
    return (
      !!locked ||
      activation_status === 'needs_clarification' ||
      (isUnregisteredBusiness(data.business_type) &&
        activation_form_milestone === 'L1' &&
        poi_verification_status === 'initiated')
    );
  };

  const getTabs = () => {
    const tabs = [
      <Tab
        key="contact_details"
        title="Contact Details"
        tabId="contact_details"
        completed={isContactDetailsCompleted}
      >
        <ContactDetails isFormLocked={isFormLocked()} />
      </Tab>,
      <Tab
        key="business_overview"
        title="Business Overview"
        tabId="business_overview"
        completed={isBusinessOverviewCompleted}
      >
        <BusinessOverview isFormLocked={isFormLocked()} />
      </Tab>,
      <Tab
        key="business_details"
        title="Business Details"
        tabId="business_details"
        completed={isBusinessDetailsCompleted}
      >
        <BusinessDetails isFormLocked={isFormLocked()} />
      </Tab>,
    ];
    if (
      (!isDedupe &&
        (!isUnregisteredBusiness(data.business_type) || poi_verification_status !== 'initiated') &&
        activation_form_milestone === 'L1') ||
      ((isDedupe || !!submitted) && activation_form_milestone === 'L2') ||
      !isInstantActivationEnabled
    ) {
      tabs.push(
        <Tab
          key="bank_details"
          title="Bank Details"
          tabId="bank_details"
          completed={isBankAndCompanyDetailsCompleted}
        >
          <BankDetails isFormLocked={isFormLocked()} />
        </Tab>,
        <Tab
          key="documents"
          title="Documents"
          tabId="documents"
          completed={isDocumentsUploadCompleted}
        >
          <DocumentUpload isFormLocked={isFormLocked()} />
        </Tab>,
      );
    }
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
                  <Heading size="large">Account Activation</Heading>
                </View>
              </View>
            </Flex>
            <Link
              onClick={() => {
                if (!submitted && !isDedupe && data.poi_verification_status !== 'initiated') {
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
            disabled={canSubmitActivationForm()}
            icon="chevronRight"
            iconAlign="right"
          >
            {getNextText()}
          </Button>
        </StyledFooter>
      </Flex>
      <ActivationModal
        isOpen={isModalOpen}
        modalType={modalType}
        closeModal={() => setIsModalOpen(false)}
        dedupeStatus={merchantDedupeStatus}
        activationData={data}
      />
      <SaveAndExitModal
        isOpen={isSaveAndExitModalOpen}
        onClose={() => setIsSaveAndExitModalOpen(false)}
      />
      <FAQs />
    </View>
  );
};

export default withRouter(ActivationForm);
