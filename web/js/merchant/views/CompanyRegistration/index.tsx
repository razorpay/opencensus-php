import React, { useEffect } from 'react';
import { Box, Text, Alert, AlertProps, Link, Spinner } from '@razorpay/blade/components';
import errorService from '@razorpay/universe-cli/errorService';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import { useQuery } from '@tanstack/react-query';
import { getUser } from 'merchant/store';

import { trackLandingPageView, trackEventOnUserScreenPageView } from './analytics';
import CompanyRegisterBanner from './components/CompanyRegisterBanner';
import IncorpPackageCard from './components/IncorpPackageCard';
import RazorpayExclusiveOffer from './components/RazorpayExclusiveOffer';
import UserJourney from './components/UserJourney';
import UserIncorporationStatus from './components/UserIncorporationStatus';
import SwitchAccount from './components/SwitchAccount';
import { getModularOnboardingData } from './services/services';
import { useScreen, parseWorkflowResponse, getSteps } from './utils';
import { RIZE_JOURNEY } from './constant';

const ContactRize = ({ isSmallDevice }) => (
  <Box paddingBottom='spacing.7'>
    <Text
      size="small"
      marginLeft={isSmallDevice ? 'spacing.0' : 'spacing.7'}
      color="surface.text.gray.subtle"
      display="flex"
    >
      For support, contact us on
      <Link size="small" marginLeft="spacing.1" href="mailto:rize-registrations@razorpay.com">
        rize-registrations@razorpay.com
      </Link>
    </Text>
  </Box>
);
const PageError = ({
  title = 'Something went wrong!',
  description,
  color = 'negative',
  isFullWidth = false,
  isDismissible = false,
}: AlertProps): JSX.Element => {
  return (
    <Alert
      title={title}
      description={description}
      color={color}
      margin="spacing.5"
      isDismissible={isDismissible}
      isFullWidth={isFullWidth}
    />
  );
};
const CompanyRegistration = () => {
  const { isMobile, isTablet } = useScreen();
  const isSmallDevice = isMobile || isTablet; // Small Device will be mobile or tablet, else it will be Desktop

  const session = getUser();

  // fetch user data
  const { data, isLoading } = useQuery({
    queryKey: ['rizeCompanyRegistration'],
    queryFn: () => getModularOnboardingData(session?.merchant?.id, session.user?.id),
    retryDelay: 800,
    refetchOnWindowFocus: false,
    cacheTime: 1000 * 60 * 1,
    refetchOnMount: 'always',
  });

  const workflow = data?.data;
  const userProgress = parseWorkflowResponse(workflow);
  const workflowData = workflow?.workflow_data;

  useEffect(() => {
    if (userProgress?.screen == RIZE_JOURNEY.INITIAL_SCREEN) {
      trackLandingPageView();
    } else if (userProgress?.screen == RIZE_JOURNEY.RESUME_SCREEN) {
      trackEventOnUserScreenPageView();
    }
  }, []);

  const renderScreen = () => {
    switch (userProgress?.screen) {
      case RIZE_JOURNEY.INITIAL_SCREEN:
        return (
          <>
            <IncorpPackageCard isSmallDevice={isSmallDevice} />
            <RazorpayExclusiveOffer isSmallDevice={isSmallDevice} />
          </>
        );
      case RIZE_JOURNEY.RESUME_SCREEN:
        return (
          <UserJourney isSmallDevice={isSmallDevice} userJourney={userProgress.user_journey} />
        );
      case RIZE_JOURNEY.STATUS_SCREEN:
        return (
          <UserIncorporationStatus
            isSmallDevice={isSmallDevice}
            getSteps={() => getSteps(workflowData)}
          />
        );
      case RIZE_JOURNEY.ACCOUNT_SCREEN:
        return <SwitchAccount />;
      default:
        return <></>;
    }
  };
  return (
    <Box height="100vh" marginBottom="spacing.3" display="flex" flexDirection="column">
      {isLoading ? (
        <Spinner accessibilityLabel="Rize Incorporation spinner" size="xlarge" />
      ) : (
        <>
          <CompanyRegisterBanner isSmallDevice={isSmallDevice} screen={userProgress.screen} />
          {renderScreen()}
          <ContactRize isSmallDevice={isSmallDevice} />
        </>
      )}
    </Box>
  );
};

const CompanyRegistrationWrapper = () => {
  return (
    <ErrorBoundary
      rank={errorService.ErrorRank.P0}
      tags={{ module: 'Rize Incorporation' }}
      fallbackComponent={
        <Box marginTop="spacing.8">
          <PageError
            title="Something went wrong!"
            description="We are facing some issues. Please try again later."
          />
        </Box>
      }
    >
      <CompanyRegistration />
    </ErrorBoundary>
  );
};
export default CompanyRegistrationWrapper;
