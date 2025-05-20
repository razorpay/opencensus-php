import React, { useEffect, useState } from 'react';
import { compose } from 'redux';
import { connect } from 'react-redux';
import LandingPage from 'merchant/views/CustomerTrust/components/LandingPage';
import { fetchOnboardingStatusApi } from 'merchant/views/CustomerTrust/utils/api';
import { FetchOnboardingResponse, OnboardingStatus } from 'merchant/views/CustomerTrust/types';
import { Box, Spinner, ToastContainer, useToast } from '@razorpay/blade/components';
import { Onboarding } from 'merchant/views/CustomerTrust/components/Onboarding';
import { Overview } from 'merchant/views/CustomerTrust/components/Overview';
import { trackBpLandingPageView } from './analytics';

const CustomerTrust = ({ user }: { user: any }) => {
  const { show } = useToast();

  const isSelfServeEnabled = user.isBuyerProtectionSelfServeEnabled;
  const skipLandingPage =
    new URLSearchParams(window.location.search).get('skipLandingPage') === 'true';

  const [loading, setLoading] = useState(false);
  const [onboardingResponse, setOnboardingResponse] = useState<
    FetchOnboardingResponse['data'] | undefined
  >();

  const onboardingStatus = onboardingResponse?.onboarding_status!;

  useEffect(() => {
    async function checkOnboardingStatus() {
      setLoading(true);
      try {
        const response: FetchOnboardingResponse = await fetchOnboardingStatusApi();
        if (response.status_code === 200) {
          setOnboardingResponse(response.data);
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
      } finally {
        setLoading(false);
      }
    }
    checkOnboardingStatus();

    trackBpLandingPageView();
  }, []);

  const setOnboardingStatus = (status: OnboardingStatus) => {
    if (onboardingResponse) {
      setOnboardingResponse({ ...onboardingResponse, onboarding_status: status });
    }
  };

  const renderMainContent = () => {
    if (loading) {
      return (
        <Box display="flex" justifyContent="center" alignItems="center" width="100%" height="100%">
          <Spinner accessibilityLabel="loading" />
        </Box>
      );
    }

    if (isSelfServeEnabled) {
      if (onboardingStatus === 'activated' || onboardingStatus === 'pending') {
        return <Overview onboardingStatus={onboardingStatus} />;
      }

      if (skipLandingPage || onboardingStatus === 'in-progress') {
        return (
          <Onboarding
            pricing={onboardingResponse?.pricing!}
            nonEligibleReason={onboardingResponse?.reason}
            setOnboardingStatus={setOnboardingStatus}
            isEligible={onboardingResponse?.eligible || false}
            category={onboardingResponse?.category!}
          />
        );
      }
    }

    return (
      <LandingPage
        onboardingStatus={onboardingStatus}
        setOnboardingStatus={setOnboardingStatus}
        show={show}
        isSelfServeEnabled={isSelfServeEnabled}
      />
    );
  };

  return (
    <Box>
      <ToastContainer />
      {renderMainContent()}
    </Box>
  );
};

const mapStateToProps = (state) => ({ user: state.session.user });
export default compose<any>(connect(mapStateToProps, null))(CustomerTrust);
