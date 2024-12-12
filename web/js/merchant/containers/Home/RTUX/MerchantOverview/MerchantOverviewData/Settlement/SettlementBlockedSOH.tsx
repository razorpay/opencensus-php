import React, { useState } from 'react';
import {
  Box,
  Heading,
  Text,
  Button,
  AlertTriangleIcon,
  StepGroup,
  StepItem,
  StepItemIcon,
  CheckIcon,
  InfoIcon,
  StepItemIndicator,
} from '@razorpay/blade/components';
import { useMobile } from 'common/hooks/useMobile';
import { mobileBreakoints } from 'merchant/views/Transactions/v2/common/constants';
import { settlementConfig } from 'merchant/containers/Home/RTUX/MerchantOverview/types';
import { StyledImage } from './utils';
import Image from 'assets/paper-dart-blocked.png';
import {
  SETTLEMENT_HOLD_BANK_UPDATE_MESSAGE,
  SETTLEMENT_HOLD_CONTACT_SUPPORT_MESSAGE,
  SETTLEMENT_HOLD_CTA_TEXT,
  SETTLEMENT_HOLD_FEATURE,
  SETTLEMENT_HOLD_MESSAGE,
  SETTLEMENT_HOLD_PRIMARY_TEXT,
} from 'merchant/views/Settlements/components/utils';
import { useNavigate } from 'react-router-dom';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import { closeModal } from 'merchant_common/reducers/modals';

interface SettlementBlockedSOHProps extends settlementConfig {
  bankUpdate: boolean;
  settlementConfig: settlementConfig | null;
}

const SettlementBlockedSOH: React.FC<SettlementBlockedSOHProps> = ({
  bankUpdate,
  settlementConfig,
}) => {
  const isMobile = useMobile(mobileBreakoints);
  const navigate = useNavigate();
  const isHold = settlementConfig?.source === SETTLEMENT_HOLD_FEATURE.HOLD;
  const isFoh = settlementConfig?.source === SETTLEMENT_HOLD_FEATURE.FOH;

  const soh_cta_link = ROUTES_INFO.BANK_ACCOUNT_DETAILS;

  let currentStatus: string = '';
  // First prefernce is FOH, only check for SOH when FOH is not enabled
  // Showing different message for SOH in case the cta_text is not 'Contact Support'  if (!isFoh && isHold && settlementConfig?.cta_text != SETTLEMENT_HOLD_CTA_TEXT.CONTACT_SUPPORT)
  if (!isFoh && isHold && settlementConfig?.cta_text != SETTLEMENT_HOLD_CTA_TEXT.CONTACT_SUPPORT) {
    currentStatus = SETTLEMENT_HOLD_FEATURE.HOLD;
  }
  const handleClick = (url) => {
    navigate(url);
  };

  const handleContactSupport = () => {
    closeModal();

    if (window.rzpTicketSystem) {
      CreateTicketEmitter.emit('create-ticket', 'tickets');
    }
  };

  const renderContent = () => {
    switch (currentStatus) {
      case SETTLEMENT_HOLD_FEATURE.HOLD:
        return (
          <>
            <Box flex="3">
              <Box display="flex" gap="spacing.3" alignItems="flex-start">
                <Box flexShrink="0" marginTop="spacing.3">
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
                <Heading size={isMobile ? 'medium' : 'large'}>
                  {bankUpdate
                    ? SETTLEMENT_HOLD_BANK_UPDATE_MESSAGE.HEADING
                    : settlementConfig?.sub_title || SETTLEMENT_HOLD_PRIMARY_TEXT}
                </Heading>
              </Box>
              <Box>
                <StepGroup orientation="vertical" size={isMobile ? 'medium' : 'large'}>
                  <StepItem
                    stepProgress={bankUpdate ? 'full' : 'none'}
                    marker={
                      isMobile ? (
                        <StepItemIndicator color={bankUpdate ? 'positive' : 'neutral'} />
                      ) : (
                        <StepItemIcon
                          icon={CheckIcon}
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
                    {!bankUpdate && (
                      <Button
                        marginLeft={{ base: 'none', l: 'spacing.5' }}
                        onClick={() => {
                          handleClick(soh_cta_link);
                        }}
                      >
                        {SETTLEMENT_HOLD_CTA_TEXT.UPDATE_BANKACC}
                      </Button>
                    )}
                  </StepItem>
                  <StepItem
                    marker={
                      isMobile ? (
                        <StepItemIndicator color="neutral" />
                      ) : (
                        <StepItemIcon icon={CheckIcon} color="neutral" />
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
      default:
        return (
          <>
            <Box flex="3">
              <Box display="flex" gap="spacing.3" alignItems="flex-start">
                <Box flexShrink="0" marginTop="spacing.3">
                  <AlertTriangleIcon
                    size={!isMobile ? 'xlarge' : 'large'}
                    color="interactive.icon.negative.subtle"
                  />
                </Box>
                <Heading size="large">
                  {settlementConfig?.sub_title || SETTLEMENT_HOLD_PRIMARY_TEXT}
                </Heading>
              </Box>
              <Box borderRadius="large" margin="spacing.4" paddingLeft="spacing.4">
                <Text weight="medium">{SETTLEMENT_HOLD_CONTACT_SUPPORT_MESSAGE.SUB_TITLE}</Text>

                <Button
                  marginTop="spacing.6"
                  onClick={() => {
                    handleContactSupport();
                  }}
                >
                  {SETTLEMENT_HOLD_CTA_TEXT.CONTACT_SUPPORT}
                </Button>
              </Box>
            </Box>
            <Box flex="2" display={{ base: 'none', l: 'block' }}>
              <StyledImage src={Image} alt="Status_Image" />
            </Box>
          </>
        );
    }
  };
  return (
    <Box display="flex" minHeight={isMobile ? 'auto' : '150px'}>
      {renderContent()}
    </Box>
  );
};

export default SettlementBlockedSOH;
