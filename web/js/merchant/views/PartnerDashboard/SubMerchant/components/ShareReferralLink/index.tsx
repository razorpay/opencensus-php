import React, { useEffect, useState } from 'react';
import {
  Modal,
  ModalBody,
  ModalHeader,
  Box,
  Text,
  RadioGroup,
  Radio,
} from '@razorpay/blade/components';

import { useI18Service } from 'common/i18';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { User } from 'common/typings';
import { analyticsTrack } from 'common/utils/analytics';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

import ClientAssistOptions from './ClientAssistOptions';
import SocialShareGroup from './SocialShareGroup';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';

type ShareReferralLinkType = {
  closeModal: () => void;
  referralData: string | Record<string, unknown>;
  user: User;
  product: string;
};
const ShareReferralLink = ({
  closeModal,
  referralData,
  user,
  product,
}: ShareReferralLinkType): JSX.Element => {
  // tracking arg
  const inviteFlow = 'SHARE_REFERRAL_LINK';

  const [productType, setProductType] = useState(product);
  // TODO v2: make a copy of ShareReferralLink component to separately handle urls for isPlatformPartnerInviteFlowEnabled
  const referralUrl = referralData?.[productType]?.url;
  const easyAccessUrl = referralData?.[productType]?.easy_kyc_access_url;
  const { isPlatformPartnerInviteFlowEnabled } = usePartnerDashboardExperiments();
  const { isConfigTagEnabled } = useI18Service();

  useEffect(() => {
    analyticsTrack({
      objectName: 'Social Share Referral Box',
      actionName: 'Opened',
      screen: window.location,
      properties: {
        productType,
      },
    });
  }, [productType]);

  const handleModalClose = () => {
    analyticsTrack({
      objectName: 'Social Share Referral Box',
      actionName: 'Closed',
      screen: window.location,
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
                          <Text weight="bold">Razorpay Payments</Text>
                          <Text size="small">
                            Invite clients to use Razorpay Payment products to collect payments
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
              {!isPlatformPartnerInviteFlowEnabled &&
              !isConfigTagEnabled('partnership.add_new_razorpay_x_merchant') ? (
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
                            <Text weight="bold">RazorpayX</Text>
                            <Text size="small">
                              Refer merchants to RazorpayX products like Current account to process
                              payouts
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
              {!isPlatformPartnerInviteFlowEnabled && user.isPartnershipForCapitalEnabled ? (
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

export default ShareReferralLink;
