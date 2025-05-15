import React from 'react';
import styled from 'styled-components';
import { Box } from '@razorpay/blade/components';
import { Header } from 'merchant/views/CustomerTrust/components/Header';
import { ActivationStatus } from './ActivationStatus';
import { IntegrationSteps } from './IntegrationSteps';
import { HelpBanner } from './HelpBanner';
import { OnboardingStatus } from '../../types';

const MainContainer = styled.div`
  background-color: #fff;
  width: 100%;
  height: fit-content;
`;

export const Overview = ({ onboardingStatus }: { onboardingStatus: OnboardingStatus }) => {
  return (
    <>
      <MainContainer>
        <Box
          display="flex"
          flexDirection="column"
          gap="spacing.8"
          padding={['spacing.8', 'spacing.7']}
          maxWidth="64rem"
        >
          <Box display="flex" flexDirection="column" gap="spacing.9">
            <Header />

            {/* main content */}
            <ActivationStatus onboardingStatus={onboardingStatus} />
            <IntegrationSteps />
            <HelpBanner />
          </Box>
        </Box>
      </MainContainer>
    </>
  );
};
