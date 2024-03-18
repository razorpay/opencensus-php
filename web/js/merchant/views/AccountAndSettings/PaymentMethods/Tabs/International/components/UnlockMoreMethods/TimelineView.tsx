import React from 'react';
import { Box, Text, Button, ClockIcon, Divider, Badge, InfoIcon } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { V_KYC_STATUS } from 'merchant/reducers/unlockIntlPaymentMethods/initialState';
import { setIsMethodEnablementFormOpen } from 'merchant/reducers/unlockIntlPaymentMethods/reducer';
import { ICProductStates } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import { trackVideoKycRetryClick } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/analytics';

import {
  UNLOCK_METHODS_STEPS,
  KYC_DOCUMENT_STATUS_BADGE_MAPPING,
  V_KYC_STATUS_BADGE_MAPPING,
  V_KYC_REJECTION_REASON_MAPPING,
  KYC_DOCUMENTS,
  VIDEO_KYC,
} from './constants';
import { Icon } from './styled';
import { TimelineViewProps } from './types';
import { getDefaultTab } from './utils';

const TimelineView = ({
  user,
  vKycStatus,
  kycDocumentStatus,
  vKycRejectedReason,
  setIsMethodEnablementFormOpen,
}: TimelineViewProps) => {
  const status = KYC_DOCUMENT_STATUS_BADGE_MAPPING[kycDocumentStatus ?? 'default'];
  const vStatus = V_KYC_STATUS_BADGE_MAPPING[vKycStatus ?? 'default'];

  const onButtonClick = (stepId) => {
    const defaultTab = getDefaultTab(user.business_type);
    setIsMethodEnablementFormOpen({
      isOpen: true,
      defaultTab: stepId === VIDEO_KYC ? 2 : defaultTab,
    });
    if (stepId === VIDEO_KYC && vKycStatus === V_KYC_STATUS.REJECTED) {
      trackVideoKycRetryClick(user.business_type);
    }
  };

  const getVkycDescription = (step) => {
    if (vKycStatus === V_KYC_STATUS.REJECTED) {
      const reason = V_KYC_REJECTION_REASON_MAPPING[vKycRejectedReason];
      return reason
        ? `Your video KYC was unsuccessful due to ( ${reason} ). Please try again.`
        : V_KYC_REJECTION_REASON_MAPPING.default;
    }
    return vStatus ? vStatus.description : step.description;
  };

  return (
    <Box display="flex" flexDirection="column">
      {UNLOCK_METHODS_STEPS.map((step, index) => (
        <Box
          key={step.id}
          display="flex"
          gap="spacing.3"
          paddingBottom={index < UNLOCK_METHODS_STEPS.length - 1 ? 'spacing.8' : 'spacing.0'}
          position="relative"
        >
          {index < UNLOCK_METHODS_STEPS.length - 1 && (
            <Divider
              orientation="vertical"
              position="absolute"
              left="spacing.5"
              top="spacing.8"
              bottom="spacing.0"
            />
          )}
          <Box>
            <Icon>
              <ClockIcon size="medium" color="feedback.icon.notice.lowContrast" />
            </Icon>
          </Box>
          <Box display="flex" gap="spacing.1" flex="1">
            <Box display="flex" flexDirection="column" gap="spacing.1" flex="1">
              <Text weight="bold" size="large">
                {step.title}
              </Text>
              <Text>
                {step.id === KYC_DOCUMENTS
                  ? status
                    ? status.description
                    : step.description
                  : null}
                {step.id === VIDEO_KYC ? getVkycDescription(step) : null}
              </Text>
            </Box>
            <Box>
              {step.id === KYC_DOCUMENTS && status && (
                <Box as="span">
                  {kycDocumentStatus !== ICProductStates.REJECTED ? (
                    <Badge contrast="high" color={status.status} size="large" icon={InfoIcon}>
                      {status.label as string}
                    </Badge>
                  ) : (
                    <Button size="small" onClick={() => onButtonClick(step.id)}>
                      {step.ctaText.default as string}
                    </Button>
                  )}
                </Box>
              )}
              {step.id === VIDEO_KYC && (
                <Box as="span">
                  {![V_KYC_STATUS.REJECTED, V_KYC_STATUS.INITIATED].includes(
                    vKycStatus as 'rejected' | 'initiated',
                  ) && vStatus ? (
                    <Badge contrast="high" color={vStatus.status} size="large" icon={InfoIcon}>
                      {vStatus.label as string}
                    </Badge>
                  ) : (
                    <Button size="small" onClick={() => onButtonClick(step.id)}>
                      {
                        // eslint-disable-next-line react/no-unknown-property
                        step.ctaText[vKycStatus as 'rejected' | 'initiated'] ||
                          (step.ctaText.default as string)
                      }
                    </Button>
                  )}
                </Box>
              )}
            </Box>
          </Box>
        </Box>
      ))}
    </Box>
  );
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      setIsMethodEnablementFormOpen,
    },
    dispatch,
  );
};

export default connect(null, mapDispatchToProps)(TimelineView);
