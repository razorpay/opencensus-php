import React from 'react';
import { Box, Divider, Text, Spinner } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { ShowNotificationType } from 'common/typings';
import { OAuthAppDetailsType } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import { INVITE_TAB_TYPES } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/InviteMerchantTabs/constants';
import ApplicationDetails from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/common/ApplicationDetails';
import useOAuthInviteLinks from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/hooks/useOAuthInviteLinks';
import { trackPublicLinkFlowCTAClicked } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';
import SocialShareGroup from 'merchant/views/PartnerDashboard/SubMerchant/components/ShareReferralLink/SocialShareGroup';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { showNotification } from 'merchant_common/reducers/notifications';

interface PublicOAuthLinksProps {
  selectedApp: OAuthAppDetailsType;
  productType: string;
  showNotification: ShowNotificationType;
  goToAppSelectionStep: () => void;
}
const PublicOAuthLinks = ({
  selectedApp,
  productType = PRODUCT_TYPE.PG,
  goToAppSelectionStep,
  showNotification,
}: PublicOAuthLinksProps): JSX.Element => {
  const inviteFlow = INVITE_TAB_TYPES.PUBLIC_LINK;
  const { data: referralData, isLoading } = useOAuthInviteLinks({ showNotification, selectedApp });
  const referralUrl = referralData?.value as string;

  if (isLoading)
    return (
      <Box display="flex" alignItems="center" justifyContent="center" marginTop="spacing.5">
        <Spinner alignSelf="center" accessibilityLabel="public-oauth-links-spinner" />
      </Box>
    );
  const onChangeAppClick = () => {
    trackPublicLinkFlowCTAClicked({ ctaClicked: 'Change App', productType });
    goToAppSelectionStep();
  };

  return (
    <>
      <Box display="flex" gap="spacing.3" alignItems="center" marginTop="spacing.5">
        <Text>
          You can now invite users to by sharing a public link on any of your social media profiles
        </Text>
        <Divider />
      </Box>
      <Box
        display="flex"
        flexDirection="column"
        gap="spacing.3"
        marginTop="spacing.5"
        marginBottom="spacing.9"
      >
        <Box
          display="flex"
          flexDirection="column"
          gap="spacing.5"
          padding={['spacing.5', 'spacing.5', 'spacing.7']}
          backgroundColor="surface.background.gray.moderate"
        >
          <SocialShareGroup
            isKycAssistedSelected={null}
            inviteFlow={inviteFlow}
            productType={productType}
            showDivider={false}
            referralUrl={referralUrl}
          />
          {selectedApp?.name ? (
            <ApplicationDetails
              name={selectedApp.name}
              id={selectedApp.application_id}
              handleChange={onChangeAppClick}
            />
          ) : null}
        </Box>
      </Box>
    </>
  );
};

export default connect(
  () => ({}),
  (dispatch) => bindActionCreators({ showNotification }, dispatch),
)(PublicOAuthLinks);
