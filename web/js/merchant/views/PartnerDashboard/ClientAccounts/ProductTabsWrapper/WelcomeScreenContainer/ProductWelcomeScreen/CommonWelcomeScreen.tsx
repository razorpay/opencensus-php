import React from 'react';
import { Heading, Text, Box, Button, PlusIcon } from '@razorpay/blade/components';
import AddNewSubMerchants from 'assets/onboarding/add-new-sub-merchants.png';
import ShareReferralLink from 'assets/onboarding/share-referral-link.png';
import { connect } from 'react-redux';

import { useI18Service } from 'common/i18';
import { User } from 'common/typings';
import Image from 'common/ui/Image';
import useWelcomeScreenData from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/WelcomeScreenContainer/hooks/useWelcomeScreenData';
import useProductActions from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/hooks/useProductActions';
import { Org } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import { getHasSelectedKycAccess } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/kycAccessFtux';
import SocialShareGroup from 'merchant/views/PartnerDashboard/SubMerchant/components/ShareReferralLink/SocialShareGroup';
import {
  ADD_NEW_MERCHANT_ELIGIBLE_ROLES,
  ORG_NAME,
} from 'merchant/views/PartnerDashboard/constants';
import { ShowWhen } from 'merchant_common/components/RouteGuard';

type CommonWelcomeScreenProps = {
  getAddMerchantVisibility: (args: User) => boolean;
  getShareReferralLinkVisibility: (args: User) => boolean;
  mainTitle?: string;
  org: Org;
  productType: string;
};
const CommonWelcomeScreen = ({
  getAddMerchantVisibility,
  getShareReferralLinkVisibility,
  org,
  productType,
  mainTitle = 'Welcome to Partner Dashboard',
}: CommonWelcomeScreenProps): JSX.Element => {
  const inviteFlow = `WELCOME_SCREEN_${productType.toUpperCase()}`;
  const hasSelectedKycAccess = getHasSelectedKycAccess(productType);
  const { referralData } = useWelcomeScreenData();

  const easyAccessUrl = referralData?.[productType]?.easy_kyc_access_url;
  const nonEasyAccessUrl = referralData?.[productType]?.url;
  const referralUrl = hasSelectedKycAccess ? easyAccessUrl : nonEasyAccessUrl;
  const orgName = org?.business_name || ORG_NAME.RZP;
  const i18 = useI18Service();
  const { handleAddMerchant } = useProductActions();
  return (
    <Box backgroundColor="surface.background.gray.intense" paddingTop="135px" paddingBottom="200px">
      <Box display="flex" flexDirection="row" flexWrap="wrap" gap="28px" justifyContent="center">
        <Box display="flex" flexDirection="column" gap="28px" alignItems="center">
          <Box display="flex" flexDirection="column" gap="spacing.2" alignItems="center">
            <Heading size="large">{mainTitle}</Heading>
            <Text size="large">Get Started by adding clients on {orgName}</Text>
          </Box>

          <Box
            display="flex"
            flexDirection="row"
            flexWrap="wrap"
            gap="spacing.11"
            justifyContent="center"
          >
            <ShowWhen
              myRole={ADD_NEW_MERCHANT_ELIGIBLE_ROLES}
              additionalCondition={(currentUser) => getAddMerchantVisibility(currentUser)}
            >
              <Box display="flex" flexDirection="column" gap="spacing.3" alignItems="center">
                <Box>
                  <Image src={AddNewSubMerchants} isWebP />
                </Box>
                <Text size="large">Invite a client by adding their details</Text>

                <Button onClick={handleAddMerchant} icon={PlusIcon}>
                  Add New Clients
                </Button>
              </Box>
            </ShowWhen>
            <ShowWhen
              additionalCondition={(currentUser) =>
                getShareReferralLinkVisibility(currentUser) &&
                !i18?.isConfigTagEnabled('partnership.referral_links')
              }
            >
              <Box display="flex" flexDirection="column" gap="spacing.3" alignItems="center">
                <Box>
                  <Image src={ShareReferralLink} isWebP />
                </Box>
                <Text size="large">
                  Share the{' '}
                  <Text display="inline-block" size="large" weight="semibold">
                    invite link
                  </Text>{' '}
                  on social media
                </Text>
                <Box>
                  <SocialShareGroup
                    isKycAssistedSelected={hasSelectedKycAccess}
                    inviteFlow={inviteFlow}
                    referralUrl={referralUrl}
                    showDivider={false}
                    productType={productType}
                  />
                </Box>
              </Box>
            </ShowWhen>
          </Box>
        </Box>
      </Box>
    </Box>
  );
};
export default connect(
  (state) => ({
    org: state.session.org,
  }),
  null,
)(CommonWelcomeScreen);
