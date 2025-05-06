import React, { useState } from 'react';
import { Badge, Box, Divider, Text, Spinner, Alert } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';

import { ShowNotificationType } from 'common/typings';
import { INVITE_TAB_TYPES } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/InviteMerchantTabs/constants';
import FAQContent from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/common/FAQModal/FAQContent';
import useReferralLinks from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/hooks/useReferralLinks';
import { getHasSelectedKycAccess } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/kycAccessFtux';
import ClientAssistOptions from 'merchant/views/PartnerDashboard/SubMerchant/components/ShareReferralLink/ClientAssistOptions';
import SocialShareGroup from 'merchant/views/PartnerDashboard/SubMerchant/components/ShareReferralLink/SocialShareGroup';
import { PARTNER_TYPE, PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { showNotification } from 'merchant_common/reducers/notifications';
import { User } from 'common/typings';
interface PublicLinksTabProps {
  productType: string;
  showNotification: ShowNotificationType;
  user: User;
}
const PublicLinksTab = ({
  productType,
  showNotification,
  user,
}: PublicLinksTabProps): JSX.Element => {
  const inviteFlow = INVITE_TAB_TYPES.PUBLIC_LINK;
  const [shouldShowAlert, setShouldShowAlert] = useState(false);

  // Formik hooks and Validation
  const { data: referralData, isLoading } = useReferralLinks({ showNotification });
  const hasSelectedKycAccess = getHasSelectedKycAccess(productType);
  const referralUrl = referralData?.[productType]?.url;
  const easyAccessUrl = referralData?.[productType]?.easy_kyc_access_url;

  let initialSelectedValue = hasSelectedKycAccess === null ? '' : 'yes';
  if (hasSelectedKycAccess === false) initialSelectedValue = 'no';

  const shouldShowFtuxContent =
    hasSelectedKycAccess === null &&
    easyAccessUrl &&
    (productType === PRODUCT_TYPE.PG || productType === PRODUCT_TYPE.POS) &&
    !user?.isPartner(PARTNER_TYPE.AGGREGATOR);

  if (isLoading)
    return (
      <Box display="flex" alignItems="center" justifyContent="center" marginTop="spacing.5">
        <Spinner alignSelf="center" accessibilityLabel="public-links-spinner" />
      </Box>
    );
  return (
    <>
      <Box display="flex" gap="spacing.3" alignItems="center" marginTop="spacing.5">
        <Text>
          You can now invite users to by sharing a public link on any of your social media profiles
        </Text>
        <Divider />
      </Box>
      {shouldShowFtuxContent ? (
        <>
          <Box display="flex" gap="spacing.3" alignItems="center" marginTop="spacing.9">
            <Badge emphasis="intense" size="large" color="information">
              New update
            </Badge>
            <Divider />
          </Box>
          <Box display="flex" flexDirection="column" gap="spacing.0" marginTop="spacing.5">
            <Text weight="semibold">Assist the client with their KYC</Text>
            <Text size="small">
              to provide them with a quick and seamless onboarding experience.
            </Text>
          </Box>
        </>
      ) : null}
      <Box
        display="flex"
        flexDirection="column"
        gap="spacing.3"
        marginTop="spacing.5"
        marginBottom="spacing.9"
      >
        {easyAccessUrl ? (
          <ClientAssistOptions
            initialValue={initialSelectedValue}
            inviteFlow={inviteFlow}
            productType={productType}
            referralUrl={referralUrl}
            easyAccessUrl={easyAccessUrl}
            showAlert={setShouldShowAlert}
          />
        ) : (
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
          </Box>
        )}
      </Box>
      {shouldShowFtuxContent ? (
        <FAQContent inviteFlow={inviteFlow} productType={productType} />
      ) : null}
      {shouldShowAlert ? (
        <Alert
          emphasis="intense"
          description="Please copy the new link before sharing it."
          title="Your public link has changed"
          color="notice"
        />
      ) : null}
    </>
  );
};

export default compose(
  connect(
    (state) => ({ user: state.session.user }),
    (dispatch) => bindActionCreators({ showNotification }, dispatch),
  ),
)(PublicLinksTab);
