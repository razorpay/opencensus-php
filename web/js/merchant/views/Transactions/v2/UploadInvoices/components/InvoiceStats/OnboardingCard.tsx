import React, { useContext } from 'react';
import { Box, Button, Text, LinkIcon, Link, ExternalLinkIcon } from '@razorpay/blade/components';
import GstPortal from 'assets/cross-border/gst-portal.png';
import NicPortal from 'assets/cross-border/nic-portal.png';

import { PopupContext } from 'merchant/views/Transactions/v2/UploadInvoices/context/PopupContext';
import { MODAL_TYPES } from 'merchant/views/Transactions/v2/UploadInvoices/constants';
import { RZP_LOGO_URL_DARK } from 'merchant/components/SidebarV2/constants/constants';

import { OnboardingCardProps } from './types';
import { getOnboardingCardDetails } from './utils';
import StatsLoader from './StatsLoader';
import { LEARN_MORE_URL } from './constant';

const OnboardingCard = ({ onboardingDetails, isLoading }: OnboardingCardProps): JSX.Element => {
  const { bannerText, buttonText, partner, status } = getOnboardingCardDetails(onboardingDetails);

  const { openPopup } = useContext(PopupContext);

  const onButtonClick = () => {
    if (!status) {
      openPopup(MODAL_TYPES.ONBOARDING, { partner, status });
      return;
    }
    openPopup(MODAL_TYPES.LOGIN, { partner, status });
  };

  const onLearnMoreClick = () => {
    window.open(LEARN_MORE_URL, '_blank');
  };

  if (isLoading) return <StatsLoader />;
  return (
    <Box
      display="flex"
      flexDirection="column"
      justifyContent="space-between"
      padding={{
        base: ['spacing.4', 'spacing.3', 'spacing.4', 'spacing.3'],
        m: ['spacing.6', 'spacing.5', 'spacing.6', 'spacing.5'],
      }}
      flex="1"
      backgroundColor="surface.background.primary.subtle"
    >
      <Box
        display="flex"
        flexDirection="row"
        justifyContent="space-between"
        maxHeight="18px"
        height="18px"
        marginBottom={{ base: 'spacing.3', m: 'spacing.4' }}
      >
        <img height="100%" src={RZP_LOGO_URL_DARK} />
        <LinkIcon color="interactive.icon.gray.normal" />
        <img height="100%" src={GstPortal} />
        <img height="100%" src={NicPortal} />
      </Box>
      <Text marginBottom={{ base: 'spacing.3', m: 'spacing.4' }}>{bannerText}</Text>
      <Box display="flex" flexDirection="row" justifyContent="space-between" alignItems="center">
        <Button icon={LinkIcon} iconPosition="left" onClick={onButtonClick}>
          {buttonText}
        </Button>
        <Link icon={ExternalLinkIcon} iconPosition="right" onClick={onLearnMoreClick}>
          Learn More
        </Link>
      </Box>
    </Box>
  );
};

export default OnboardingCard;
