import React, { useEffect, useState } from 'react';
import LandingPage from 'merchant/views/CustomerTrust/components/LandingPage';
import { fetchOnboardingStatusApi } from 'merchant/views/CustomerTrust/utils/api';
import { FetchOnboardingResponse, OnboardingStatus } from 'merchant/views/CustomerTrust/types';
import { Box, ToastContainer, useToast } from '@razorpay/blade/components';

const CustomerTrust = () => {
  const { show } = useToast();
  const [onboardingStatus, setOnboardingStatus] = useState<OnboardingStatus>(null);

  useEffect(() => {
    async function checkOnboardingStatus() {
      try {
        const response: FetchOnboardingResponse = await fetchOnboardingStatusApi();
        if (response.status_code === 200) {
          if (response.data.onboarding_status === 'interested') {
            setOnboardingStatus('interested');
          } else if (response.data.onboarding_status === 'completed') {
            setOnboardingStatus('completed');
          }
        } else {
          show({
            type: 'informational',
            content: 'Something went wrong. Please try again!',
            color: 'negative',
          });
        }
      } catch (e) {
        show({
          type: 'informational',
          content: 'Something went wrong. Please try again!',
          color: 'negative',
        });
      }
    }
    checkOnboardingStatus();
  }, []);

  return (
    <Box>
      <ToastContainer />
      <LandingPage
        onboardingStatus={onboardingStatus}
        setOnboardingStatus={setOnboardingStatus}
        show={show}
      />
    </Box>
  );
};

export default CustomerTrust;
