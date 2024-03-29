import React from 'react';
import { Divider, Box, Text, Button } from '@razorpay/blade/components';

import copyToClipboard from 'common/utils/copyToClipboard';
import {
  trackCopyLinkClicked,
  trackSocialShareLinkClicked,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';
import { mediaWindowUrl } from 'merchant/views/PartnerDashboard/SubMerchant/components/utils/social-share';

type SocialShareGroupProps = {
  referralUrl: string;
  inviteFlow: string;
  productType: string;
  isKycAssistedSelected: boolean | null;
  showDivider?: boolean;
};
const SocialShareGroup = ({
  referralUrl,
  inviteFlow,
  productType,
  isKycAssistedSelected,
  showDivider = true,
}: SocialShareGroupProps): JSX.Element => {
  const shareReferralOn = (platform) => {
    trackSocialShareLinkClicked({
      inviteFlow,
      productType,
      socialMedia: platform,
      isKycAssistedSelected,
    });
    mediaWindowUrl({
      type: platform,
      url: referralUrl,
      title: 'Sign up on Razorpay!',
      description:
        "Start using a wide range of Razorpay's payment solutions and unlock growth for your business with just a few clicks. Go live in less than 10 minutes.",
    });
  };
  const onCopyLinkClicked = () => {
    trackCopyLinkClicked({ inviteFlow, productType, isKycAssistedSelected });
    copyToClipboard(referralUrl);
  };

  return (
    <>
      {showDivider ? <Divider /> : null}
      <Box display="flex" flexDirection="column" gap="spacing.5" alignItems="center">
        <Box
          width="100%"
          display="flex"
          gap="spacing.3"
          justifyContent="space-between"
          alignItems="center"
          padding="spacing.3"
          flex="1"
          backgroundColor="surface.background.gray.intense"
        >
          <Text weight="semibold" color="surface.text.gray.muted">
            {referralUrl}
          </Text>
          <Button size="small" onClick={onCopyLinkClicked}>
            Copy Link
          </Button>
        </Box>
        <Box width="100%" display="flex" flexDirection="column" gap="spacing.3" alignItems="center">
          <Text size="small">Or Share via</Text>
          <Box display="flex" gap="spacing.5">
            <img
              style={{ borderRadius: '25px', height: '32px', cursor: 'pointer' }}
              src="/img/social-media/fb.png"
              alt="share via fb"
              onClick={() => shareReferralOn('fb')}
            />
            <img
              style={{ borderRadius: '25px', height: '32px', cursor: 'pointer' }}
              src="/img/social-media/twitter.png"
              alt="share via twitter"
              onClick={() => shareReferralOn('twitter')}
            />
            <img
              style={{ borderRadius: '25px', height: '32px', cursor: 'pointer' }}
              src="/img/social-media/whatsapp.png"
              alt="share via whatsapp"
              onClick={() => shareReferralOn('whatsapp')}
            />
          </Box>
        </Box>
      </Box>
    </>
  );
};

export default SocialShareGroup;
