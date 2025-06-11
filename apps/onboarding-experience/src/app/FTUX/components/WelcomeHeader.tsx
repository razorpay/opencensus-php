import React from 'react';
import {
  Box,
  Divider,
  Heading,
  Text,
  ArrowRightIcon,
  Link,
  AvatarGroup,
  Avatar,
} from '@razorpay/blade/components';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { getMerchantHeaderData, formatName } from '@FTUX/utils/homepage';
import { isMobileDevice } from '@libs/shared-utils';

/**
 * Displays a personalized welcome message to the merchant at the top of the FTUX homepage.
 */
const WelcomeHeader = () => {
  const isMobile = isMobileDevice();
  const { merchantData } = useMerchantContext();
  const submittedName =
    merchantData?.merchantById?.contactPerson?.name?.value ||
    merchantData?.merchantById?.name?.registered ||
    '';
  const merchantName = formatName(submittedName);
  const { icons: headerIcons, text: merchantPlatforms } = getMerchantHeaderData(
    merchantData?.merchantById?.business?.paymentAcceptanceChannels,
  );

  const handleScrollToWaysToCollect = () => {
    const element = document.getElementById('ways-to-accept-payments');
    if (element) {
      element.scrollIntoView({ behavior: 'smooth' });
    }
  };

  return (
    <Box
      paddingY="spacing.7"
      display="flex"
      flexDirection="column"
      justifyContent="center"
      alignItems="center"
      gap="spacing.6"
      alignSelf="stretch"
      data-analytics-name="welcome-header-section"
      id="ftux-welcome-header"
    >
      <Heading textAlign="center" size={isMobile ? 'large' : 'xlarge'}>
        {isMobile
          ? `Welcome${merchantName ? `, ${merchantName}` : ''}!`
          : `Hey${merchantName ? ` ${merchantName}` : ''},  welcome to Razorpay.`}
      </Heading>
      <Box
        display="flex"
        flexDirection="column"
        alignItems="center"
        gap={{ base: 'spacing.1', m: 'spacing.2' }}
      >
        <Box
          display="flex"
          flexDirection={{ base: 'column', m: 'row' }}
          alignItems="center"
          gap={{ base: 'spacing.4', m: 'spacing.0' }}
        >
          <AvatarGroup size="small">
            {headerIcons.map((icon) => (
              <Avatar color="primary" src={icon} marginRight="spacing.4" key={icon} />
            ))}
          </AvatarGroup>
          <Text
            color="surface.text.gray.subtle"
            textAlign="center"
            size={isMobile ? 'small' : 'large'}
            weight="regular"
          >
            You picked {merchantPlatforms}, so we'll set it up first.
          </Text>
        </Box>
        <Box
          display="flex"
          flexDirection={{ base: 'column', m: 'row' }}
          alignItems="center"
          gap={{ base: 'spacing.4', m: 'spacing.2' }}
        >
          <Text
            color="surface.text.gray.subtle"
            textAlign="center"
            size={isMobile ? 'small' : 'large'}
            weight="regular"
          >
            Explore other payment options anytime.
          </Text>
          <Link
            variant="button"
            color="neutral"
            icon={ArrowRightIcon}
            iconPosition="right"
            onClick={handleScrollToWaysToCollect}
            size={isMobile ? 'small' : 'large'}
            data-analytics-name="view-all-payment-options"
          >
            View all
          </Link>
        </Box>
      </Box>
      <Divider width="100%" marginY="spacing.5" />
    </Box>
  );
};

export default WelcomeHeader;
