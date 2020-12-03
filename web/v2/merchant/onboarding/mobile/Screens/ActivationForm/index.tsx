import React from 'react';
import styled from 'styled-components';
import View from '@razorpay/blade/src/atoms/View';
import Heading from '@razorpay/blade/src/atoms/Heading';
import Flex from '@razorpay/blade/src/atoms/Flex';
import Space from '@razorpay/blade/src/atoms/Space';
import Icon from '@razorpay/blade/src/atoms/Icon';
import Button from '@razorpay/blade/src/atoms/Button';
import Link from '@commander/shield/src/shared/Link';
import { Tabs, Tab } from '../../../../../components/Tabs';
import { useActivationFormState } from '../../context/store';
import useActivation from '../../hooks/useActivation';
import BankDetails from '../../BankDetails';
import ContactDetails from '../../ContactDetails';
import BusinessOverview from '../../BusinessOverview';
import BusinessDetails from '../../BusinessDetails';
import DocumentUpload from '../../DocumentUpload';

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

const ActivationForm: React.FC = () => {
  const { status } = useActivation();

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

  if (status === 'loading') {
    return <div>Loading</div>;
  }

  if (status === 'error') {
    return <div>Something went wrong</div>;
  }

  return (
    <View>
      {/* Header */}
      <Space padding={[1.75, 1.5, 1.75, 0.75]}>
        <Flex justifyContent="space-between" alignItems="center">
          <View>
            <Flex>
              <View>
                <Icon name="chevronLeft" size="large" fill="shade.800" />
                <Heading size="large">Enable Payments</Heading>
              </View>
            </Flex>
            <Link size="xsmall" weight="bold">
              Save and Exit
            </Link>
          </View>
        </Flex>
      </Space>
      {/* Form Tabs */}
      <Space margin={[0, 0, 8.75, 0]}>
        <StyledActivationForm>
          <Tabs activeTabId="contact_details" onChange={(tabId) => console.log({ tabId })}>
            <Tab
              title="Contact Details"
              tabId="contact_details"
              completed={isContactDetailsCompleted}
            >
              <ContactDetails />
            </Tab>
            <Tab
              title="Business Overview"
              tabId="business_overview"
              completed={isBusinessOverviewCompleted}
            >
              <BusinessOverview />
            </Tab>
            <Tab
              title="Business Details"
              tabId="business_details"
              completed={isBusinessDetailsCompleted}
            >
              <BusinessDetails />
            </Tab>
            <Tab
              title="Bank Details"
              tabId="bank_details"
              completed={isBankAndCompanyDetailsCompleted}
            >
              <BankDetails />
            </Tab>
            <Tab title="Documents" tabId="documents">
              <DocumentUpload />
            </Tab>
          </Tabs>
        </StyledActivationForm>
      </Space>
      {/* Footer */}
      <Flex justifyContent="space-between">
        <StyledFooter>
          <Button variant="tertiary">FAQs</Button>
          <Button>Next</Button>
        </StyledFooter>
      </Flex>
    </View>
  );
};

export default ActivationForm;
