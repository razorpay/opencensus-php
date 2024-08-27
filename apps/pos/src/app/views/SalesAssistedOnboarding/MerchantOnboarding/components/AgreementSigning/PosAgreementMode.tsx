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
import { Merchant } from '@dashboard/shared-utils/graphql/graph-types';
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

interface PosAgreementModeProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse;
  isUpdateModularLoading: boolean;
  updateModularConfig: (args) => void;
  isModularLoading: boolean;
  merchantDetails: Merchant | undefined;
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
  const isAgreementExecuted = getAgreementSatusValue(modularConfig) === COMPLETED;
  const isFormDisabled = !!merchantDetails?.activation?.posActivationStatus;

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
      });
    }
  };

  const handleRetry = () => {
    toast.show({
      color: 'positive',
      content: 'Agreement re-sent successfully',
      leading: CheckCircleIcon,
    });
  };

  const handleOnlineSubmit = () => {
    if (getAgreementSatusValue(modularConfig) === COMPLETED) {
      setIsBottomSheetOpen(true);
      return;
    }
    if (getAgreementSatusValue(modularConfig) === IN_PROGRESS) {
      updateModularConfig({
        [MODULAR_AGREEMENT_FIELDS.RETRY_SEND_AGREEMENT_FIELD]: Math.floor(Date.now() / 1000),
        [MODULAR_AGREEMENT_FIELDS.MODULAR_CALLBACK]: handleRetry,
      });
      return;
    }
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
  };

  const handleSubmit = () => {
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
            isDisabled={isFormDisabled || isAgreementExecuted}
            onError={() => {
              toast.show({
                content: 'Failed to upload agreement proof',
                color: 'negative',
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
  };

  useEffect(() => {
    if (isAgreementExecuted) {
      setIsBottomSheetOpen(true);
    }
  }, []);

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
          isDisabled={isFormDisabled || isAgreementExecuted}
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
      {!isAgreementExecuted ? (
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
      ) : null}
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
