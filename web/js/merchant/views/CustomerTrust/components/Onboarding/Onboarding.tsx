import React, { useState } from 'react';
import styled from 'styled-components';
import { Text, Box, Link, Button, Alert, InfoIcon } from '@razorpay/blade/components';
import { Header } from 'merchant/views/CustomerTrust/components/Header';
import { InfoCards } from './InfoCards';
import { TnCModal } from './TnCModal';
import { createOnboardingConsentApi } from '../../utils/api';
import { FetchOnboardingResponse, OnboardingCategory, SetOnboardingStatus } from '../../types';
import MONEY_BACK_PROMISE_IMAGE from './demo.svg';

const MainContainer = styled.div`
  background-color: #fff;
  width: 100%;
  height: fit-content;

  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;

  @media (min-width: 768px) {
    flex-direction: row;
    align-items: flex-start;
  }
`;

const nonEligibleReasonMap = {
  tpv_enabled: 'TPV',
  contact_optional: 'Contact Optional',
  cfb_merchant: 'Customer Fee Bearer',
  razorpay_wallet_enabled: 'Razorpay Wallet',
};

export const Onboarding = ({
  pricing,
  setOnboardingStatus,
  isEligible,
  nonEligibleReason,
  category,
}: {
  pricing: number;
  setOnboardingStatus: SetOnboardingStatus;
  isEligible: boolean;
  nonEligibleReason?: string;
  category: OnboardingCategory;
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  const handleActivateNow = async () => {
    setIsLoading(true);
    try {
      const response: FetchOnboardingResponse = await createOnboardingConsentApi({
        pricing,
      });
      if (response.status_code === 200) {
        setOnboardingStatus('pending');
      }
    } catch (error) {
      console.error(error);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <>
      <TnCModal
        isOpen={isOpen}
        setIsOpen={setIsOpen}
        isLoading={isLoading}
        handleActivateNow={handleActivateNow}
      />

      <MainContainer>
        <Box
          display="flex"
          flexDirection="column"
          gap="spacing.8"
          padding={{ base: 'spacing.5', m: 'spacing.7', l: 'spacing.8' }}
          width={{ base: '100%', l: '698px' }}
        >
          <Box display="flex" flexDirection="column" gap="spacing.9">
            <Header />

            {/* main content */}
            <InfoCards pricing={pricing} category={category} />
          </Box>

          {/* footer */}
          <Box display="flex" flexDirection="column" gap="spacing.3">
            <Box
              display="flex"
              flexDirection={{ base: 'column', m: 'row' }}
              gap="spacing.2"
              alignItems={{ base: 'flex-start', m: 'center' }}
              height={{ base: 'auto', m: 'spacing.5' }}
            >
              <Text size="small" color="surface.text.gray.muted">
                By clicking "Activate Now", you agree to our
              </Text>
              <Link size="small" variant="button" onClick={() => setIsOpen(true)}>
                Terms and Conditions.
              </Link>
            </Box>

            {nonEligibleReason ? (
              <Alert
                isFullWidth={true}
                color="notice"
                icon={InfoIcon}
                description={`${
                  nonEligibleReasonMap[nonEligibleReason as keyof typeof nonEligibleReasonMap]
                } is active on your store. Please deactivate it if you wish to enable Buyer Protection.`}
                isDismissible={false}
                marginY={{ base: 'spacing.2', m: 'spacing.3' }}
              />
            ) : null}

            <Box width={{ base: '100%', m: '250px' }}>
              <Button
                isFullWidth={true}
                onClick={handleActivateNow}
                isLoading={isLoading}
                isDisabled={!isEligible}
              >
                Activate now
              </Button>
            </Box>
          </Box>
        </Box>

        <Box width={{ base: '100%', l: '376px' }} padding={{ base: 'spacing.5', m: 'spacing.7' }}>
          <img width="100%" height="auto" src={MONEY_BACK_PROMISE_IMAGE} alt="money back promise" />
        </Box>
      </MainContainer>
    </>
  );
};
