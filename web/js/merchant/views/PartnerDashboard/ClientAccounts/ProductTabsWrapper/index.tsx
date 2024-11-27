import React, { useMemo, useState } from 'react';
import { Box } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { useI18Service } from 'common/i18';
import { ShowNotificationType, User } from 'common/typings';
import ProductWrapper from 'common/ui/ProductWrapper';
import { useTwoFactorVerificationContext } from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import ProductClientAccounts from 'merchant/views/PartnerDashboard/ClientAccounts//ProductTabsWrapper/ProductClientAccounts';
import {
  trackAddMerchantClicked,
  trackShareReferralLinkClicked,
} from 'merchant/views/PartnerDashboard/ClientAccounts/analytics';
import { OpenModalT, Org } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import AddMerchant from 'merchant/views/PartnerDashboard/SubMerchant/AddMerchant';
import InviteMerchantModal from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal';
import { INVITE_MERCHANT_STEPS } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/constants';
import useReferralLinks from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/hooks/useReferralLinks';
import ShareReferralLink from 'merchant/views/PartnerDashboard/SubMerchant/components/ShareReferralLink';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import SideHeader from './SideHeader';
import WelcomeScreenContainer from './WelcomeScreenContainer';
import { ProductActionsContext } from './context';
import { getIsInviteFlowEnabled, getTabsData } from './utils/tabsData';

type ClientProductsWrapperProps = {
  productType: string;
  user: User;
  org: Org;
  openModal: OpenModalT;
  closeModal: () => void;
  showNotification: ShowNotificationType;
};
const ClientProductsWrapper = ({
  productType,
  user,
  org,
  closeModal,
  openModal,
  showNotification,
}: ClientProductsWrapperProps): JSX.Element => {
  // Formik hooks and Validation
  const { data: referralData, isLoading: isReferralLinksLoading } = useReferralLinks({
    showNotification,
  });

  const partnerId = user.id as string;

  const { criticalFlow } = useTwoFactorVerificationContext();
  const experiments = usePartnerDashboardExperiments();
  const { isPlatformPartnerInviteFlowEnabled, is2FaEnabled } = experiments;

  const { isInviteFlowEnabled, isPlatformPartnerWithPGInviteFlow } = getIsInviteFlowEnabled(
    productType,
    experiments,
  );
  const [isInviteMerchantModalOpen, setIsInviteMerchantModalOpen] = useState(false);

  const i18 = useI18Service();

  const { tabsData, productTypeVisibilityMap } = useMemo(
    () => getTabsData({ user, i18, experiments, productType }),
    [user, i18, experiments, productType],
  );

  const handleAddMerchant = () => {
    trackAddMerchantClicked(productType);
    if (isInviteFlowEnabled) {
      setIsInviteMerchantModalOpen(true);
    } else if (is2FaEnabled) {
      criticalFlow({
        enforceVerifyOtp: true,
        modes: ['live', 'test'],
        onUserTwoFaVerified: () => {
          openModal({
            size: 'med-large',
            component: (
              <AddMerchant
                closeModal={closeModal}
                referralData={referralData}
                addType={productType}
                org={org}
                isConfigTagEnabled={i18.isConfigTagEnabled}
              />
            ),
          });
        },
      });
    } else {
      openModal({
        size: 'med-large',
        component: (
          <AddMerchant
            closeModal={closeModal}
            referralData={referralData}
            addType={productType}
            org={org}
            isConfigTagEnabled={i18.isConfigTagEnabled}
          />
        ),
      });
    }
  };

  const handleShareReferralLink = () => {
    trackShareReferralLinkClicked(productType, partnerId);
    openModal({
      size: 'med-large',
      isNew: true,
      component: <ShareReferralLink referralData={referralData} initialProductType={productType} />,
    });
  };

  return (
    <ProductActionsContext.Provider
      value={{ handleAddMerchant, handleShareReferralLink, isInviteMerchantModalOpen }}
    >
      <ProductWrapper
        tabsData={tabsData}
        extra={
          <SideHeader
            i18={i18}
            isPlatformPartnerWithPGInviteFlow={isPlatformPartnerWithPGInviteFlow}
          />
        }
      >
        <Box>
          <WelcomeScreenContainer
            isReferralLinksLoading={isReferralLinksLoading}
            referralData={referralData}
            user={user}
            productType={productType}
          >
            <ProductClientAccounts productTypeVisibilityMap={productTypeVisibilityMap} />
          </WelcomeScreenContainer>

          {isInviteFlowEnabled ? (
            <InviteMerchantModal
              initialProductType={productType}
              initialStep={
                isPlatformPartnerInviteFlowEnabled
                  ? INVITE_MERCHANT_STEPS.CHOOSE_OAUTH_APP
                  : INVITE_MERCHANT_STEPS.INVITE_TABS
              }
              isOpen={isInviteMerchantModalOpen}
              onDismiss={() => setIsInviteMerchantModalOpen(false)}
            />
          ) : null}
        </Box>
      </ProductWrapper>
    </ProductActionsContext.Provider>
  );
};
export default connect(
  (state) => ({ user: state.session.user }),
  (dispatch) =>
    bindActionCreators(
      {
        openModal,
        closeModal,
        showNotification,
      },
      dispatch,
    ),
)(ClientProductsWrapper);
