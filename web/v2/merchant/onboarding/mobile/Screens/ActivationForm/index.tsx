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
import { getMerchantFlow, isL1Submitted } from '../../services/utils';
import { Tabs, Tab } from '../../../../../components/Tabs';
import { useActivationFormState } from '../../context/store';
import useActivation from '../../hooks/useActivation';
import BankDetails from '../../BankDetails';
import ContactDetails from '../../ContactDetails';
import BusinessOverview from '../../BusinessOverview';
import BusinessDetails from '../../BusinessDetails';
import DocumentUpload from '../../DocumentUpload';
import {
  EnableSettlements as EnableSettlementModal,
  SubmitForm as SubmitFormModal,
} from '../../ActivationModals';
import SaveAndExitModal from '../../SaveAndExitModal';
import FAQs from '../../FAQs/FAQs';

type NextTextT = 'Submit And Verify' | 'Save And Verify' | 'Save';

const StyledFooter = styled(View)`
  box-sizing: border-box;
  width: 100%;
  position: fixed;
  bottom: 0;
  padding: 16px;
  background-color: ${({ theme }) => theme.colors.background['200']};
`;

const StyledActivationForm = styled(View)`
  min-height: 100vh;
  background-color: ${({ theme }) => theme.colors.background[400]};
`;

const StyledHeader = styled(View)`
  background-color: ${({ theme }) => theme.colors.background[200]};
`;

const ActivationForm: React.FC<RouteComponentProps> = ({ history }) => {
  const { status, data, postData } = useActivation();
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
  const [isEnableSettlementModalOpen, setIsEnableSettlementModalOpen] = useState(false);
  const [isSubmitFormModalOpen, setIsSubmitFormModalOpen] = useState(false);
  const [isSaveAndExitModalOpen, setIsSaveAndExitModalOpen] = useState(false);

  if (status === 'loading') {
    return <FullPageLoader />;
  }

  if (status === 'error') {
    return <div>Something went wrong</div>;
  }

  const merchantFlow = getMerchantFlow(data.business_type, data.activation_flow);
  const { onboarding_milestone } = data;
  const submitL1 = () => {
    postData({ onboarding_milestone: 'L1' }).then((res) => {
      if (res && res.onboarding_milestone === 'L1') {
        setIsEnableSettlementModalOpen(true);
      }
    });
  };
  const submitL2 = () => {
    postData({ submit: 1 }).then((res) => {
      if (res && res.submitted) {
        setIsSubmitFormModalOpen(true);
      }
    });
  };
  const getNextText = (): NextTextT => {
    if (activeTabId === 'documents') {
      return 'Submit And Verify';
    }
    if (activeTabId === 'business_details' && !isL1Submitted(onboarding_milestone)) {
      return 'Save And Verify';
    }
    return 'Save';
  };
  const handleNextClick = () => {
    switch (activeTabId) {
      case 'contact_details':
        setActiveTabId('business_overview');
        break;
      case 'business_overview':
        setActiveTabId('business_details');
        break;
      case 'business_details':
        if (!isL1Submitted(onboarding_milestone)) {
          submitL1();
        }
        break;
      case 'bank_details':
        setActiveTabId('documents');
        break;
      case 'documents':
        submitL2();
        break;
      default:
        break;
    }
  };
  const onBack = () => {
    history.push('/onboarding/steps');
  };

  const getTabs = () => {
    const tabs = [
      <Tab
        key="contact_details"
        title="Contact Details"
        tabId="contact_details"
        completed={isContactDetailsCompleted}
      >
        <ContactDetails />
      </Tab>,
      <Tab
        key="business_overview"
        title="Business Overview"
        tabId="business_overview"
        completed={isBusinessOverviewCompleted}
      >
        <BusinessOverview />
      </Tab>,
      <Tab
        key="business_details"
        title="Business Details"
        tabId="business_details"
        completed={isBusinessDetailsCompleted}
      >
        <BusinessDetails />
      </Tab>,
    ];
    if (merchantFlow === 'greylist' || isL1Submitted(onboarding_milestone)) {
      tabs.push(
        <Tab
          key="bank_details"
          title="Bank Details"
          tabId="bank_details"
          completed={isBankAndCompanyDetailsCompleted}
        >
          <BankDetails />
        </Tab>,
        <Tab
          key="documents"
          title="Documents"
          tabId="documents"
          completed={isDocumentsUploadCompleted}
        >
          <DocumentUpload />
        </Tab>,
      );
    }
    return tabs;
  };
  return (
    <View>
      {/* Header */}
      <Space padding={[1.75, 1.5, 1.75, 0.75]}>
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
                  <Heading size="large">Enable Payments</Heading>
                </View>
              </View>
            </Flex>
            <Link onClick={() => setIsSaveAndExitModalOpen(true)} size="xsmall" weight="bold">
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
            onChange={(tabId) => typeof tabId === 'string' && setActiveTabId(tabId)}
          >
            {getTabs()}
          </Tabs>
        </StyledActivationForm>
      </Space>
      {/* Footer */}
      <Flex justifyContent="space-between">
        <StyledFooter>
          <Button onClick={() => setIsOpen(true)} variant="tertiary">
            FAQs
          </Button>
          <Button onClick={() => handleNextClick()}>{getNextText()}</Button>
        </StyledFooter>
      </Flex>
      <EnableSettlementModal isOpen={isEnableSettlementModalOpen} />
      <SubmitFormModal isOpen={isSubmitFormModalOpen} />
      <SaveAndExitModal
        isOpen={isSaveAndExitModalOpen}
        onClose={() => setIsSaveAndExitModalOpen(false)}
      />
      <FAQs />
    </View>
  );
};

export default withRouter(ActivationForm);
