import React, { useEffect } from 'react';
import { Box, Text, Alert, AlertProps, Link } from '@razorpay/blade/components';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import CompanyRegisterBanner from './components/CompanyRegisterBanner';
import IncorpPackageCard from './components/IncorpPackageCard';
import RazorpayExclusiveOffer from './components/RazorpayExclusiveOffer';
import { useScreen } from './utils';
import { trackLandingPageView } from './analytics';

const ContactRize = () => (
  <Text
    marginTop={'spacing.7'}
    marginLeft={'spacing.7'}
    size="small"
    color="surface.text.gray.subtle"
    display={'flex'}
  >
    For support, contact us on
    <Link size="small" marginLeft={'spacing.1'} href="mailto:rize-registrations@razorpay.com">
      rize-registrations@razorpay.com
    </Link>
  </Text>
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
  useEffect(() => {
    trackLandingPageView();
  }, []);
  return (
    <Box marginBottom="spacing.3">
      <CompanyRegisterBanner isSmallDevice={isSmallDevice} />
      <IncorpPackageCard isSmallDevice={isSmallDevice} />
      <RazorpayExclusiveOffer isSmallDevice={isSmallDevice} />
      <ContactRize />
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
