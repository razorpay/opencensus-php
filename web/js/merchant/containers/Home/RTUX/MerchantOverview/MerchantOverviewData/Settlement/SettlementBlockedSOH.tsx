import React from 'react';
import {
  Box,
  Button,
  AlertTriangleIcon,
  StepGroup,
  StepItem,
  StepItemIcon,
  CheckIcon,
  InfoIcon,
  StepItemIndicator,
  DotIcon,
  Text,
  Heading,
  useTheme,
} from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { useMobile } from 'common/hooks/useMobile';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';
import { settlementConfig } from 'merchant/containers/Home/RTUX/MerchantOverview/types';
import {
  fireContactSupportFohEvent,
  isRiskDisabled,
  isRiskFoh,
  showContactSupport,
  StyledImage,
} from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/Settlement/utils';
import Image from 'assets/paper-dart-blocked.png';
import {
  FOH_DISABLED_LIVE_TEXT,
  SETTLEMENT_HOLD_BANK_UPDATE_MESSAGE,
  SETTLEMENT_HOLD_CONTACT_SUPPORT_MESSAGE,
  SETTLEMENT_HOLD_CTA_TEXT,
  SETTLEMENT_HOLD_FEATURE,
  SETTLEMENT_HOLD_MESSAGE,
  SETTLEMENT_HOLD_PRIMARY_TEXT,
  SETTLEMENTS_BLOCK_TITLE,
} from 'merchant/views/Settlements/components/utils';
import { useNavigate } from 'react-router-dom';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import { closeModal } from 'merchant_common/reducers/modals';
import { useFohTicket } from 'merchant/containers/Home/RTUX/MerchantOverview/MerchantOverviewData/store';
import useFohTicketData from './useFohTicketData';
interface SettlementBlockedSOHProps extends settlementConfig {
  bankUpdate: boolean;
  settlementConfig: settlementConfig | null;
  isFohRiskDisabled: boolean;
}
const SettlementBlockedSOH: React.FC<SettlementBlockedSOHProps> = ({
  bankUpdate,
  settlementConfig,
  isFohRiskDisabled = false,
}) => {
  const isMobile = useMobile(mobileBreakoints);
  const navigate = useNavigate();
  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMediumDesktop = matchedBreakpoint && ['base', 'xs', 's', 'm'].includes(matchedBreakpoint);
  const isHold = settlementConfig?.source === SETTLEMENT_HOLD_FEATURE.HOLD;
  const isFoh = settlementConfig?.source === SETTLEMENT_HOLD_FEATURE.FOH;
  const isBlock = settlementConfig?.source === SETTLEMENT_HOLD_FEATURE.BLOCK;
  const soh_cta_link = ROUTES_INFO.BANK_ACCOUNT_DETAILS;
  const defaultHeading = isBlock ? SETTLEMENTS_BLOCK_TITLE : SETTLEMENT_HOLD_PRIMARY_TEXT;
  let currentStatus: string = '';
  const { fohTicketId, fohTicketStatus } = useFohTicket();
  const { isLoading } = useFohTicketData();
  // First prefernce is FOH, only check for SOH when FOH is not enabled
  if (isFohRiskDisabled) {
    currentStatus = SETTLEMENT_HOLD_FEATURE.DISABLED_LIVE;
  }
  // Showing different message for SOH in case the cta_text is not 'Update Bank Details'  if (!isFoh && isHold && settlementConfig?.cta_text != SETTLEMENT_HOLD_CTA_TEXT.CONTACT_SUPPORT)
  if (!isFoh && isHold && settlementConfig?.cta_text !== SETTLEMENT_HOLD_CTA_TEXT.CONTACT_SUPPORT) {
    currentStatus = SETTLEMENT_HOLD_FEATURE.HOLD;
  }
  const handleClick = (url) => {
    navigate(url);
  };
  const handleContactSupport = () => {
    closeModal();
    if (showContactSupport(fohTicketStatus) && fohTicketId) {
      fireContactSupportFohEvent(fohTicketId);
      return;
    }
    if (window.rzpTicketSystem) {
      CreateTicketEmitter.emit('create-ticket', 'tickets');
    }
  };
  const renderContent = () => {
    switch (currentStatus) {
      case SETTLEMENT_HOLD_FEATURE.HOLD:
        return (
          <>
            <Box flex="4" padding="spacing.6">
              <Box
                display="flex"
                gap={isMobile ? 'spacing.4' : 'spacing.5'}
                alignItems="flex-start"
                paddingTop={isMobile ? 'spacing.0' : 'spacing.5'}
              >
                <Box
                  flexShrink="0"
                  marginTop="spacing.2"
                  marginLeft={isMobile ? 'spacing.3' : 'spacing.0'}
                >
                  {bankUpdate ? (
                    <InfoIcon
                      size={!isMobile ? 'xlarge' : 'large'}
                      color="interactive.icon.information.subtle"
                    />
                  ) : (
                    <AlertTriangleIcon
                      size={!isMobile ? 'xlarge' : 'large'}
                      color="interactive.icon.negative.subtle"
                    />
                  )}
                </Box>
                <Box flex="1" maxWidth="800px">
                  <Heading size={isMobile ? 'medium' : 'large'} weight="semibold">
                    {bankUpdate
                      ? SETTLEMENT_HOLD_BANK_UPDATE_MESSAGE.HEADING
                      : settlementConfig?.sub_title || SETTLEMENT_HOLD_PRIMARY_TEXT}
                  </Heading>
                </Box>
              </Box>
              <Box>
                <StepGroup
                  orientation="vertical"
                  size={isMobile ? 'medium' : 'large'}
                  marginLeft={isMobile ? 'spacing.2' : 'spacing.8'}
                >
                  <StepItem
                    stepProgress={bankUpdate ? 'full' : 'none'}
                    marker={
                      isMobile ? (
                        <StepItemIcon
                          color={bankUpdate ? 'positive' : 'neutral'}
                          icon={bankUpdate ? CheckIcon : DotIcon}
                        />
                      ) : (
                        <StepItemIcon
                          icon={bankUpdate ? CheckIcon : DotIcon}
                          color={bankUpdate ? 'positive' : 'neutral'}
                        />
                      )
                    }
                    title={
                      bankUpdate
                        ? SETTLEMENT_HOLD_BANK_UPDATE_MESSAGE.STEP1
                        : SETTLEMENT_HOLD_MESSAGE.SOH
                    }
                  >
                    <Box marginTop="-8px">
                      {!bankUpdate && (
                        <Button
                          size="small"
                          onClick={() => {
                            handleClick(soh_cta_link);
                          }}
                        >
                          {SETTLEMENT_HOLD_CTA_TEXT.UPDATE_BANKACC}
                        </Button>
                      )}
                    </Box>
                  </StepItem>
                  <StepItem
                    marker={
                      isMobile ? (
                        <StepItemIndicator color={bankUpdate ? 'primary' : 'neutral'} />
                      ) : (
                        <StepItemIcon icon={DotIcon} color={bankUpdate ? 'primary' : 'neutral'} />
                      )
                    }
                    title={SETTLEMENT_HOLD_BANK_UPDATE_MESSAGE.STEP2}
                  />
                </StepGroup>
              </Box>
            </Box>
            <Box
              flex="1"
              overflow="auto"
              height="auto"
              width="100%"
              display={{ base: 'none', l: 'block' }}
            >
              <StyledImage src={Image} alt="Status_Image" />
            </Box>
          </>
        );
      case SETTLEMENT_HOLD_FEATURE.DISABLED_LIVE:
        return (
          <Box
            display="flex"
            flexDirection="row"
            justifyContent="space-between"
            width="100%"
            backgroundImage={isMediumDesktop ? '' : `url(${Image})`}
            backgroundPosition="right bottom"
            backgroundRepeat="no-repeat"
            backgroundSize="contain"
          >
            <Box
              flex="4"
              display="flex"
              flexDirection="column"
              justifyContent="center"
              padding="spacing.6"
            >
              <Box display="flex" gap="spacing.5" alignItems="flex-start">
                <Box flexShrink="0" marginTop="spacing.2">
                  <AlertTriangleIcon
                    size={!isMobile ? 'xlarge' : 'large'}
                    color="interactive.icon.negative.subtle"
                  />
                </Box>
                <Heading size={isMobile ? 'medium' : 'large'} weight="semibold">
                  {settlementConfig?.sub_title}
                </Heading>
              </Box>
              <Box borderRadius="large" margin="spacing.4" paddingLeft="spacing.8">
                <Text weight="medium" color="surface.text.gray.subtle" size="medium">
                  {FOH_DISABLED_LIVE_TEXT}
                </Text>
                <Button
                  marginTop="spacing.6"
                  onClick={() => {
                    handleContactSupport();
                  }}
                  isLoading={isLoading}
                >
                  {SETTLEMENT_HOLD_CTA_TEXT.CONTACT_SUPPORT}
                </Button>
              </Box>
            </Box>
            <Box flex="2" display={{ base: 'none', l: 'flex' }} />
          </Box>
        );
      default:
        return (
          <Box
            display="flex"
            flexDirection="row"
            justifyContent="space-between"
            width="100%"
            backgroundImage={isMediumDesktop ? '' : `url(${Image})`}
            backgroundPosition="right bottom"
            backgroundRepeat="no-repeat"
            backgroundSize="contain"
          >
            <Box
              flex="4"
              display="flex"
              flexDirection="column"
              justifyContent="center"
              padding="spacing.6"
            >
              <Box display="flex" gap="spacing.5" alignItems="flex-start">
                <Box flexShrink="0" marginTop="spacing.2">
                  <AlertTriangleIcon
                    size={!isMobile ? 'xlarge' : 'large'}
                    color="interactive.icon.negative.subtle"
                  />
                </Box>
                <Heading size={isMobile ? 'medium' : 'large'} weight="semibold">
                  {settlementConfig?.sub_title || defaultHeading}
                </Heading>
              </Box>
              <Box borderRadius="large" margin="spacing.4" paddingLeft="spacing.8">
                <Text weight="medium" color="surface.text.gray.subtle" size="medium">
                  {SETTLEMENT_HOLD_CONTACT_SUPPORT_MESSAGE.SUB_TITLE}
                </Text>
                <Button
                  marginTop="spacing.6"
                  onClick={() => {
                    handleContactSupport();
                  }}
                  isLoading={(isRiskFoh() || isRiskDisabled()) && isLoading}
                >
                  {SETTLEMENT_HOLD_CTA_TEXT.CONTACT_SUPPORT}
                </Button>
              </Box>
            </Box>
            <Box flex="2" display={{ base: 'none', l: 'flex' }} />
          </Box>
        );
    }
  };
  return (
    <Box
      display="flex"
      minHeight={isMobile ? 'auto' : '150px'}
      marginX={isMobile ? 'auto' : 'spacing.3'}
      width="100%"
    >
      {renderContent()}
    </Box>
  );
};
export default SettlementBlockedSOH;
