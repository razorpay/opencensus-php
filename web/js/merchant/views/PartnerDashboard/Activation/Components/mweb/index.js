import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Heading from '@razorpay/blade-old/src/atoms/Heading';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import Button from '@razorpay/blade-old/src/atoms/Button';
import Link from '@razorpay/commander-shield/src/shared/Link';
import { Tabs, Tab } from 'common/components/Tabs';
import { FullPageLoader } from 'common/components/Loader';
import ActivationModal from './ActivationModal';
import SaveAndExitModal from './SaveAndExitModal';
import React, { useState } from 'react';
import ContactDetails from './ContactDetails';
import BusinessDetails from './BusinessDetails';
import useActivation from '../../Hooks/useActivation';
import { useActivationFormState } from '../../Hooks/store';
import { SnackbarProvider } from 'common/components/SnackBar/SnackbarContext';

const RenderMwebActivationForm = (props) => {
  const [isSaveAndExitModalOpen, setIsSaveAndExitModalOpen] = useState(false);
  const { status: activationStatus, data, postData } = useActivation();
  const isContactDetailsCompleted = useActivationFormState(
    (state) => state.isContactDetailsCompleted,
  );
  const isBusinessDetailsCompleted = useActivationFormState(
    (state) => state.isBusinessDetailsCompleted,
  );
  const [activeTabId, setActiveTabId] = useState('contact_details');
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [modalType, setModalType] = useState('');

  const { locked: isFormLocked, submitted: isFormSubmitted } = data?.partner_activation || {};

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

  const onBack = () => {
    history.push('/partners');
  };

  const getTabs = () => {
    const tabs = [
      <Tab
        key="contact_details"
        title="Contact Details"
        tabId="contact_details"
        completed={isContactDetailsCompleted}
      >
        <ContactDetails isFormLocked={isFormLocked} />
      </Tab>,
      <Tab
        key="business_details"
        title="Business Details"
        tabId="business_details"
        completed={isBusinessDetailsCompleted}
      >
        <BusinessDetails isFormLocked={isFormLocked} />
      </Tab>,
    ];
    return tabs;
  };

  const submitForm = () => {
    const reqData = {
      submit: 1,
    };
    postData(reqData);
  };

  const handleNextClick = () => {
    switch (activeTabId) {
      case 'contact_details':
        setActiveTabId('business_details');
        break;
      case 'business_details':
        if (!isFormSubmitted) {
          submitForm();
        }
        break;
      default:
        break;
    }
  };

  const isNextButtonEnabled = () => {
    switch (activeTabId) {
      case 'contact_details':
        return isContactDetailsCompleted;
      case 'business_details':
        if (!isFormLocked) {
          return isBusinessDetailsCompleted;
        }
        return false;
      default:
        return false;
    }
  };

  const getNextText = () => {
    if (activeTabId === 'business_details' && !isFormSubmitted) {
      return 'Submit And Verify';
    }
    return 'Next';
  };

  if (activationStatus === 'loading') {
    return <FullPageLoader />;
  }

  if (activationStatus === 'error') {
    return <div> Something went wrong </div>;
  }

  return (
    <SnackbarProvider>
      <View>
        {/* Header */}
        <Space padding={[1.75, 1.5, 1.25, 0.75]}>
          <Flex justifyContent="space-between" alignItems="center">
            <StyledHeader>
              <Flex flexDirection="row" justifyContent="flex-start">
                <View>
                  <Space padding={[0, 0.5, 0, 0]}>
                    <View data-testid="backIcon" onClick={() => onBack()}>
                      <Icon name="chevronLeft" size="large" fill="shade.800" />
                    </View>
                  </Space>
                  <View>
                    <Heading size="large">Partner Activation</Heading>
                  </View>
                </View>
              </Flex>
              <Link
                onClick={() => {
                  if (!props.submitted) {
                    setIsSaveAndExitModalOpen(true);
                  } else {
                    history.push('/dashboard');
                  }
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
              onClick={() => handleNextClick()}
              disabled={!isNextButtonEnabled()}
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
          activationData={data}
        />
        <SaveAndExitModal
          isOpen={isSaveAndExitModalOpen}
          onClose={() => setIsSaveAndExitModalOpen(false)}
        />
      </View>
    </SnackbarProvider>
  );
};

export default RenderMwebActivationForm;
