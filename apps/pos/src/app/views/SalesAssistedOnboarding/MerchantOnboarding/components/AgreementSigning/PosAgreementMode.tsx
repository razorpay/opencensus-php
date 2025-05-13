import React, { useEffect, useMemo, useState } from 'react';
import {
  Box,
  Heading,
  Radio,
  RadioGroup,
  ArrowRightIcon,
  Button,
  useToast,
  CheckCircleIcon,
  InfoIcon,
  Text,
  BottomSheet,
  BottomSheetBody,
  BottomSheetFooter,
  BottomSheetHeader,
  Spinner,
} from '@razorpay/blade/components';
import { useNavigate, useParams } from 'react-router-dom';
import { DashboardGraphQLMerchant } from '@libs/shared-types';
import {
  StyledCard,
  StyledCardContainer,
} from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/AgreementSigning/styles';
import Timeline from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/AgreementSigning/Timeline';
import PosAgreementUpload from 'apps/pos/src/app/views/SalesAssistedOnboarding/MerchantOnboarding/components/AgreementSigning/PosAgreementUpload';
import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';
import { MerchantModularOnboardingDetailsSuccessResponse } from 'apps/pos/src/app/types/modular';
import {
  getUpdatedTimeline,
  getInitialTimeline,
  formatUnixTimestamp,
  getAgreementSatusValue,
  getAgreementSentAtValue,
  getAgreementMode,
  getAgreementTypeField,
  getSubmitBtnText,
  getAgreementDocsField,
  IN_PROGRESS,
  COMPLETED,
  OFFLINE,
  ONLINE,
  getSubmitIcon,
  getAgreementComponentStatus,
} from 'apps/pos/src/app/utils/agreementSigning';
import SuccessIcon from 'apps/pos/src/assets/paymentSuccess.svg';
import { FileItem } from 'apps/pos/src/app/types/fileUpload';
import {
  ProcessFilesForModularSave,
  processFilesForModularSave,
} from 'apps/pos/src/app/components/SalesFileUpload/helper';
import {
  AgreementModeType,
  MODULAR_AGREEMENT_FIELDS,
} from 'apps/pos/src/app/types/AgreementSigning';
import copyToClipboard from 'apps/pos/src/app/utils/copyToClipboard';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';
import {
  ANALYTICS_ACTIONS,
  ANALYTICS_EVENTS,
  L1_FUNNEL_STAGE,
  L2_FUNNEL_STAGE,
  PAGE_TYPES,
} from 'apps/pos/src/services/analytics/types';
import { isKycActivatedOrRejected } from 'apps/pos/src/app/utils/merchantActivation';

interface PosAgreementModeProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse;
  isUpdateModularLoading: boolean;
  // @ts-ignore
  updateModularConfig: (args) => void;
  isModularLoading: boolean;
  merchantDetails: DashboardGraphQLMerchant | undefined;
}

export const PosAgreementMode = ({
  modularConfig,
  isUpdateModularLoading,
  updateModularConfig,
  isModularLoading,
  merchantDetails,
}: PosAgreementModeProps): JSX.Element | null => {
  const { id } = useParams();
  const [agreementMode, setAgreementMode] = useState(getAgreementMode(modularConfig));
  const [isBottomSheetOpen, setIsBottomSheetOpen] = useState(false);
  const [isHandlingFile, setIsHandlingFile] = useState(false);
  const [timelineData, setTimelineData] = useState(getInitialTimeline(modularConfig));
  const [selectedFiles, setSelectedFiles] = useState<ProcessFilesForModularSave[]>([]);
  const { posAgreementDocuments } = useMemo(() => {
    return getAgreementDocsField(modularConfig);
  }, [modularConfig]);
  const defaultUploadedDocs: FileItem[] = useMemo(
    () =>
      posAgreementDocuments?.map((doc) => ({
        fileStoreId: doc?.fileStoreId as string,
        name: doc?.name as string,
        size: doc?.size as number,
      })),
    [posAgreementDocuments],
  );
  const { isMobile } = useScreen();
  const toast = useToast();
  const navigate = useNavigate();
  const isOnlineAgreementExecuted =
    getAgreementMode(modularConfig) === ONLINE &&
    getAgreementSatusValue(modularConfig) === COMPLETED;
  const isOfflineAgreementExecuted =
    getAgreementMode(modularConfig) === OFFLINE && getAgreementComponentStatus(modularConfig);
  const isFormDisabled = isKycActivatedOrRejected(merchantDetails?.activation?.posActivationStatus);

  const handleOnlineAgreement = (data: MerchantModularOnboardingDetailsSuccessResponse) => {
    const agreementStatus = getAgreementSatusValue(data);
    if (agreementStatus === IN_PROGRESS) {
      const agreementSentAt = getAgreementSentAtValue(data);
      const successBadge = {
        mood: 'positive',
        icon: CheckCircleIcon,
        text: 'Agreement Sent',
      } as const;
      const updatedTimeline = getUpdatedTimeline({
        timeline: timelineData,
        stepToUpdate: {
          stepName: MODULAR_AGREEMENT_FIELDS.GENERATE_LINK_FOR_MERCHANT,
          stepBadge: successBadge,
          stepTime: formatUnixTimestamp(agreementSentAt),
        },
        updatedStatus: 'completed',
        newStep: [
          {
            heading: MODULAR_AGREEMENT_FIELDS.SIGNING_CONFIRMATION,
            badge: { text: 'Action Pending', mood: 'notice', icon: InfoIcon },
            status: 'in_progress',
          },
        ],
      });
      setTimelineData(updatedTimeline ?? timelineData);
    } else {
      toast.show({
        color: 'negative',
        content: 'Unable to generate link. Please try again',
        leading: InfoIcon,
        autoDismiss: true,
      });
    }
  };

  const handleRetry = () => {
    toast.show({
      color: 'positive',
      content: 'Agreement re-sent successfully',
      leading: CheckCircleIcon,
      autoDismiss: true,
    });
  };

  const handleOnlineSubmit = () => {
    if (getAgreementSatusValue(modularConfig) === COMPLETED) {
      setIsBottomSheetOpen(true);
      return;
    }
    if (getAgreementSatusValue(modularConfig) === IN_PROGRESS) {
      trackEvent({
        eventName: ANALYTICS_EVENTS.WEBSITE_CTA,
        action: ANALYTICS_ACTIONS.CLICKED,
        properties: {
          label: 'Re-send Link',
          section: 'Agreement Signing',
          subSection: 'Online method',
          l1FunnelStage: L1_FUNNEL_STAGE.AGREEMENT_SIGNING,
          l2FunnelStage: L2_FUNNEL_STAGE.ONLINE_METHOD,
        },
      });
      updateModularConfig({
        [MODULAR_AGREEMENT_FIELDS.RETRY_SEND_AGREEMENT_FIELD]: Math.floor(Date.now() / 1000),
        [MODULAR_AGREEMENT_FIELDS.MODULAR_CALLBACK]: handleRetry,
      });
      return;
    }

    trackEvent({
      eventName: ANALYTICS_EVENTS.WEBSITE_CTA,
      action: ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Generate Link',
        section: 'Agreement Signing',
        subSection: 'Online method',
        l1FunnelStage: L1_FUNNEL_STAGE.AGREEMENT_SIGNING,
        l2FunnelStage: L2_FUNNEL_STAGE.ONLINE_METHOD,
      },
    });
    updateModularConfig({
      [MODULAR_AGREEMENT_FIELDS.AGREEMENT_TYPE_FIELD]: ONLINE,
      [MODULAR_AGREEMENT_FIELDS.MODULAR_CALLBACK]: handleOnlineAgreement,
    });
  };

  const handleSuccessfulSubmit = () => {
    setIsBottomSheetOpen(true);
    if (agreementMode === OFFLINE) {
      setIsHandlingFile(false);
    }
  };
  const handleOfflineSubmit = () => {
    if (!selectedFiles?.length) {
      toast.show({
        color: 'notice',
        content: 'Please upload a file',
        autoDismiss: true,
      });
      return;
    }
    setIsHandlingFile(true);
    const payload = {
      [MODULAR_AGREEMENT_FIELDS.OFFLINE_AGENT_AGREEMENT_DOC]: selectedFiles,
      [MODULAR_AGREEMENT_FIELDS.AGREEMENT_TYPE_FIELD]: OFFLINE,
      [MODULAR_AGREEMENT_FIELDS.MODULAR_CALLBACK]: handleSuccessfulSubmit,
    };
    updateModularConfig(payload);
    trackEvent({
      eventName: ANALYTICS_EVENTS.WEBSITE_CTA,
      action: ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Submit Merchant Details',
        section: 'Agreement Signing',
        subSection: 'Offline method',
        l1FunnelStage: L1_FUNNEL_STAGE.AGREEMENT_SIGNING,
        l2FunnelStage: L2_FUNNEL_STAGE.OFFLINE_METHOD,
      },
    });
  };

  const handleSubmit = () => {
    if (getAgreementSatusValue(modularConfig) === COMPLETED) {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
        action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
        properties: {
          label: 'Submit Merchant Details',
          section: 'Agreement Signing',
          subSection: agreementMode === ONLINE ? 'Online Method' : 'Offline Method',
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.AGREEMENT_SIGNING,
          l2FunnelStage:
            agreementMode === ONLINE
              ? analyticsTypes.L2_FUNNEL_STAGE.ONLINE_METHOD
              : analyticsTypes.L2_FUNNEL_STAGE.OFFLINE_METHOD,
        },
      });
    }
    if (agreementMode === ONLINE) return handleOnlineSubmit();
    if (agreementMode === OFFLINE) return handleOfflineSubmit();
  };

  const handlePosAgreementFileChange = (files: FileItem[]) => {
    setSelectedFiles(processFilesForModularSave(files));
  };

  const copyAgreementLink = () => {
    copyToClipboard(`https://${window.location.hostname}/app/pos-merchant-agreement/sign`);
    toast.show({
      color: 'positive',
      content: 'Link copied successfully',
      leading: CheckCircleIcon,
      autoDismiss: true,
    });
  };

  const getComponent = (fieldValue: string): JSX.Element | null => {
    if (agreementMode === ONLINE && fieldValue === ONLINE) {
      return <Timeline copyLink={copyAgreementLink} data={timelineData} />;
    }
    if (agreementMode === OFFLINE && fieldValue === OFFLINE) {
      return (
        <Box paddingTop="spacing.6">
          <PosAgreementUpload
            merchantId={id}
            name={MODULAR_AGREEMENT_FIELDS.OFFLINE_AGGREMENT_DOC}
            label="Upload TnC & Pricing Agreement"
            accept=".pdf,.jpeg,.jpg,.png"
            uploadType="multiple"
            onChange={handlePosAgreementFileChange}
            maxSize={5 * 1024 * 1023}
            maxLimit={5}
            isLoading={isHandlingFile}
            defaultValue={defaultUploadedDocs}
            isDisabled={isFormDisabled || isOnlineAgreementExecuted || isOfflineAgreementExecuted}
            onError={() => {
              toast.show({
                content: 'Failed to upload agreement proof',
                color: 'negative',
                autoDismiss: true,
              });
            }}
          />
        </Box>
      );
    }
    return null;
  };

  const handleKycSubmit = () => {
    navigate('/pos-sales');
    trackEvent({
      eventName: ANALYTICS_EVENTS.WEBSITE_CTA,
      action: ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Back to Dashboard',
        section: 'Agreement Signing',
        subSection: 'Merchant Onboarding',
        l1FunnelStage: L1_FUNNEL_STAGE.AGREEMENT_SIGNING,
        l2FunnelStage: L2_FUNNEL_STAGE.MERCHANT_ONBOARDING,
      },
    });
  };

  useEffect(() => {
    if (isOnlineAgreementExecuted || isOfflineAgreementExecuted) {
      setIsBottomSheetOpen(true);
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.IMAGE,
        action: analyticsTypes.ANALYTICS_ACTIONS.VIEWED,
        properties: {
          section: 'Agreement Signing',
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.AGREEMENT_SIGNING,
          l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.ONLINE_METHOD,
        },
      });
    }

    trackEvent({
      eventName: ANALYTICS_EVENTS.FORM_PAGE,
      action: ANALYTICS_ACTIONS.VIEWED,
      properties: {
        pageType: PAGE_TYPES.MERCHANT_SIGNING_ONLINE,
        l1FunnelStage: L1_FUNNEL_STAGE.AGREEMENT_SIGNING,
        l2FunnelStage: L2_FUNNEL_STAGE.MERCHANT_SIGNING_ONLINE,
      },
    });

    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    if (agreementMode === ONLINE) {
      trackEvent({
        eventName: ANALYTICS_EVENTS.PAGE,
        action: ANALYTICS_ACTIONS.VIEWED,
        properties: {
          formName: 'Agreement Signing',
          section: 'Agreement Signing',
          subSection: 'Online method',
          l1FunnelStage: L1_FUNNEL_STAGE.AGREEMENT_SIGNING,
          l2FunnelStage: L2_FUNNEL_STAGE.ONLINE_METHOD,
        },
      });
    }
  }, [agreementMode]);

  useEffect(() => {
    if (isBottomSheetOpen) {
      trackEvent({
        eventName: ANALYTICS_EVENTS.PAGE,
        action: ANALYTICS_ACTIONS.VIEWED,
        properties: {
          pageType: PAGE_TYPES.AGREEMENT_DETAILS_SUCCESS,
          l1FunnelStage: L1_FUNNEL_STAGE.AGREEMENT_SIGNING,
          l2FunnelStage: L2_FUNNEL_STAGE.MERCHANT_ONBOARDING,
        },
      });
    }
  }, [isBottomSheetOpen]);

  if (isModularLoading)
    return (
      <Box
        as="section"
        height="90vh"
        width="100%"
        display="flex"
        alignItems="center"
        justifyContent="center"
      >
        <Spinner color="neutral" accessibilityLabel="additional-details-spinner" size="xlarge" />
      </Box>
    );

  if (!modularConfig) return null;

  return (
    <Box maxWidth="767px" padding={['spacing.7', 'spacing.6', 'spacing.7', 'spacing.6']}>
      <Heading color="surface.text.gray.normal" as="h3" marginBottom="spacing.6">
        {getAgreementTypeField(modularConfig)?.meta?.title}
      </Heading>

      <Box>
        <RadioGroup
          onChange={(item) => {
            setAgreementMode(item.value as AgreementModeType);
          }}
          defaultValue={!agreementMode ? ONLINE : agreementMode}
          isDisabled={isFormDisabled || isOnlineAgreementExecuted || isOfflineAgreementExecuted}
        >
          {getAgreementTypeField(modularConfig)?.meta?.options?.map((mode) => (
            <StyledCardContainer key={mode.value} selected={agreementMode === mode.value}>
              <StyledCard selected={agreementMode === mode.value}>
                <Radio value={mode.value}>{mode.label}</Radio>
                {getComponent(mode.value)}
              </StyledCard>
            </StyledCardContainer>
          ))}
        </RadioGroup>
      </Box>
      {isOnlineAgreementExecuted || isOfflineAgreementExecuted ? null : (
        <Box
          display="flex"
          justifyContent="center"
          position="fixed"
          bottom="0px"
          padding="spacing.4"
          backgroundColor="surface.background.gray.intense"
          left="0px"
          right="0px"
          zIndex="1"
        >
          <Button
            type="submit"
            isFullWidth={isMobile}
            iconPosition="right"
            icon={getSubmitIcon({
              mode: agreementMode,
              status: getAgreementSatusValue(modularConfig),
            })}
            isLoading={isUpdateModularLoading}
            onClick={handleSubmit}
            accessibilityLabel="send-link-btn"
            isDisabled={isFormDisabled}
          >
            {getSubmitBtnText({ modularConfig, mode: agreementMode })}
          </Button>
        </Box>
      )}
      <BottomSheet onDismiss={() => setIsBottomSheetOpen(false)} isOpen={isBottomSheetOpen}>
        <BottomSheetHeader />
        <BottomSheetBody>
          <Box textAlign="center">
            <img src={SuccessIcon} alt="success-icon" height="50px" />
            <Text marginTop="spacing.4" size="large" weight="semibold">
              KYC details submitted successfully!
            </Text>
            <Text size="small" color="surface.text.gray.subtle">
              You’ll be able to see the status once updated
            </Text>
          </Box>
        </BottomSheetBody>
        <BottomSheetFooter>
          <Box>
            <Button
              type="button"
              isFullWidth={isMobile}
              iconPosition="right"
              icon={ArrowRightIcon}
              onClick={handleKycSubmit}
            >
              Back to Dashboard
            </Button>
          </Box>
        </BottomSheetFooter>
      </BottomSheet>
    </Box>
  );
};
