import React, { useEffect, useState } from 'react';
import {
  Modal,
  ModalBody,
  ModalHeader,
  Box,
  Text,
  RadioGroup,
  Radio,
  Badge,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { useI18Service } from 'common/i18';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { User } from 'common/typings';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { getProductTypeVisibilityMap } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/utils/tabsData';
import { Org } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import { ReferralData } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/hooks/useReferralLinks';
import { ORG_NAME, PRODUCT_NAME, PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import ClientAssistOptions from './ClientAssistOptions';
import SocialShareGroup from './SocialShareGroup';

type ShareReferralLinkType = {
  referralData: ReferralData | undefined;
  closeModal: () => void;
  user: User;
  org: Org;
  initialProductType: string;
};
const ShareReferralLink = ({
  closeModal,
  referralData,
  user,
  org,
  initialProductType,
}: ShareReferralLinkType): JSX.Element => {
  // tracking arg
  const inviteFlow = 'SHARE_REFERRAL_LINK';
  const orgName = org.business_name || ORG_NAME.RZP;
  const xProductName = PRODUCT_NAME[PRODUCT_TYPE.X];
  const [productType, setProductType] = useState(initialProductType);
  // TODO v2: make a copy of ShareReferralLink component to separately handle urls for isPlatformPartnerInviteFlowEnabled
  const referralUrl = referralData?.[productType]?.url;
  const easyAccessUrl = referralData?.[productType]?.easy_kyc_access_url;

  const i18 = useI18Service();
  const experiments = usePartnerDashboardExperiments();
  const { isPlatformPartnerInviteFlowEnabled } = experiments;
  const productTypeVisibilityMap = getProductTypeVisibilityMap({ user, experiments, i18 });

  useEffect(() => {
    analyticsTrackWithUserInfo({
      objectName: 'Social Share Referral Box',
      actionName: 'Opened',
      screen: window.location.pathname,
      properties: {
        productType,
      },
    });
  }, [productType]);

  const handleModalClose = () => {
    analyticsTrackWithUserInfo({
      objectName: 'Social Share Referral Box',
      actionName: 'Closed',
      screen: window.location.pathname,
      properties: {
        productType,
      },
    });
    closeModal();
  };
  return (
    <ErrorBoundary team={Teams?.PARTNERSHIP} rank={Ranks.P1} resetOnProps>
      <Modal zIndex={10000} size="small" isOpen={true} onDismiss={handleModalClose}>
        <ModalHeader title="Share Referral Link" />
        <ModalBody>
          <RadioGroup
            onChange={({ value }) => setProductType(value)}
            value={productType}
            size="small"
            label=""
          >
            <Box
              display="flex"
              flexDirection="column"
              justifyContent="center"
              backgroundColor="surface.background.level2.lowContrast"
            >
              {productTypeVisibilityMap[PRODUCT_TYPE.POS] ? (
                <div onClick={() => setProductType(PRODUCT_TYPE.POS)}>
                  <Box
                    display="flex"
                    flexDirection="column"
                    gap="spacing.5"
                    justifyContent="center"
                    padding="spacing.6"
                    backgroundColor="surface.background.level2.lowContrast"
                    borderColor="surface.border.normal.lowContrast"
                    borderWidth="thin"
                  >
                    <Box display="flex" gap="spacing.5" alignItems="center" flex="1">
                      <Box
                        display="flex"
                        flexDirection="column"
                        gap="spacing.2"
                        justifyContent="center"
                      >
                        <Box display="flex" flexDirection="column" gap="spacing.2">
                          <Box display="flex" flexDirection="column" gap="spacing.2">
                            <Badge color="positive">NEW</Badge>
                            <Text weight="bold">{orgName} POS</Text>
                            <Text size="small">
                              Refer merchants to {orgName} POS, a robust payment ecosystem and
                              receive competitive commissions.
                            </Text>
                          </Box>
                        </Box>
                      </Box>
                      <Radio value={PRODUCT_TYPE.POS}>{''}</Radio>
                    </Box>
                    {productType === PRODUCT_TYPE.POS ? (
                      easyAccessUrl ? (
                        <ClientAssistOptions
                          inviteFlow={inviteFlow}
                          productType={productType}
                          referralUrl={referralUrl}
                          easyAccessUrl={easyAccessUrl}
                        />
                      ) : (
                        <SocialShareGroup
                          isKycAssistedSelected={null}
                          inviteFlow={inviteFlow}
                          productType={productType}
                          referralUrl={referralUrl}
                        />
                      )
                    ) : null}
                  </Box>
                </div>
              ) : null}

              {productTypeVisibilityMap[PRODUCT_TYPE.PG] ? (
                <div onClick={() => setProductType(PRODUCT_TYPE.PG)}>
                  <Box
                    display="flex"
                    flexDirection="column"
                    gap="spacing.5"
                    justifyContent="center"
                    padding="spacing.6"
                    backgroundColor="surface.background.level2.lowContrast"
                    borderColor="surface.border.normal.lowContrast"
                    borderWidth="thin"
                  >
                    <Box display="flex" gap="spacing.5" alignItems="center" flex="1">
                      <Box
                        display="flex"
                        flexDirection="column"
                        gap="spacing.2"
                        justifyContent="center"
                      >
                        <Box display="flex" flexDirection="column" gap="spacing.2">
                          <Box display="flex" flexDirection="column" gap="spacing.2">
                            <Text weight="bold">{orgName} Payments</Text>
                            <Text size="small">
                              Invite clients to use {orgName} Payment products to collect payments
                            </Text>
                          </Box>
                        </Box>
                      </Box>
                      <Radio value={PRODUCT_TYPE.PG}>{''}</Radio>
                    </Box>
                    {productType === PRODUCT_TYPE.PG ? (
                      !isPlatformPartnerInviteFlowEnabled && easyAccessUrl ? (
                        <ClientAssistOptions
                          inviteFlow={inviteFlow}
                          productType={productType}
                          referralUrl={referralUrl}
                          easyAccessUrl={easyAccessUrl}
                        />
                      ) : (
                        <SocialShareGroup
                          isKycAssistedSelected={null}
                          inviteFlow={inviteFlow}
                          productType={productType}
                          referralUrl={referralUrl}
                        />
                      )
                    ) : null}
                  </Box>
                </div>
              ) : null}

              {!isPlatformPartnerInviteFlowEnabled &&
              productTypeVisibilityMap[PRODUCT_TYPE.X] &&
              !i18.isConfigTagEnabled('partnership.add_new_razorpay_x_merchant') ? (
                <div onClick={() => setProductType(PRODUCT_TYPE.X)}>
                  <Box
                    display="flex"
                    flexDirection="column"
                    gap="spacing.5"
                    justifyContent="center"
                    padding="spacing.6"
                    backgroundColor="surface.background.level2.lowContrast"
                    borderColor="surface.border.normal.lowContrast"
                    borderWidth="thin"
                  >
                    <Box display="flex" gap="spacing.5" alignItems="center" flex="1">
                      <Box
                        display="flex"
                        flexDirection="column"
                        gap="spacing.2"
                        justifyContent="center"
                      >
                        <Box display="flex" flexDirection="column" gap="spacing.2">
                          <Box display="flex" flexDirection="column" gap="spacing.2">
                            <Text weight="bold">{xProductName}</Text>
                            <Text size="small">
                              Refer merchants to {xProductName} products like Current account to
                              process payouts
                            </Text>
                          </Box>
                        </Box>
                      </Box>
                      <Radio value={PRODUCT_TYPE.X}>{''}</Radio>
                    </Box>
                    {productType === PRODUCT_TYPE.X ? (
                      <SocialShareGroup
                        isKycAssistedSelected={null}
                        inviteFlow={inviteFlow}
                        productType={productType}
                        referralUrl={referralUrl}
                      />
                    ) : null}
                  </Box>
                </div>
              ) : null}
              {!isPlatformPartnerInviteFlowEnabled &&
              productTypeVisibilityMap[PRODUCT_TYPE.CAPITAL] ? (
                <div onClick={() => setProductType(PRODUCT_TYPE.CAPITAL)}>
                  <Box
                    display="flex"
                    flexDirection="column"
                    gap="spacing.5"
                    justifyContent="center"
                    padding="spacing.6"
                    backgroundColor="surface.background.level2.lowContrast"
                    borderColor="surface.border.normal.lowContrast"
                    borderWidth="thin"
                  >
                    <Box display="flex" gap="spacing.5" alignItems="center" flex="1">
                      <Box
                        display="flex"
                        flexDirection="column"
                        gap="spacing.2"
                        justifyContent="center"
                      >
                        <Box display="flex" flexDirection="column" gap="spacing.2">
                          <Box display="flex" flexDirection="column" gap="spacing.2">
                            <Text weight="bold">Line of Credit</Text>
                            <Text size="small">
                              Refer merchants to Capital products like Line of Credit
                            </Text>
                          </Box>
                        </Box>
                      </Box>
                      <Radio value={PRODUCT_TYPE.CAPITAL}>{''}</Radio>
                    </Box>
                    {productType === PRODUCT_TYPE.CAPITAL && (
                      <SocialShareGroup
                        isKycAssistedSelected={null}
                        productType={productType}
                        inviteFlow={inviteFlow}
                        referralUrl={referralUrl}
                      />
                    )}
                  </Box>
                </div>
              ) : null}
            </Box>
          </RadioGroup>
        </ModalBody>
      </Modal>
    </ErrorBoundary>
  );
};

export default connect(
  (state) => ({ org: state.session.org, user: state.session.user }),
  (dispatch) =>
    bindActionCreators(
      {
        openModal,
        closeModal,
        showNotification,
      },
      dispatch,
    ),
)(ShareReferralLink);
