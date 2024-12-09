import { Box, Button, Spinner, Text, useToast } from '@razorpay/blade/components';
import React, { useEffect, useState } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import { useZxing } from 'react-zxing';
import noop from 'lodash/noop';
import { ScannerType } from './DeviceMappingScannerContainer';
import { ScannerButton, StyledCard, StyledContainer, StyledDot, StyledLine } from './styles';
import ModalWithBottomSheet from 'apps/pos/src/app/components/ModalWithBottomSheet';
import { BASE_ROUTE, ONBOARDING_ROUTE } from 'apps/pos/src/app/routes';
import { AvailableComponents } from 'apps/pos/src/app/types/common';
import { DEVICE_DEPLOYMENT_FIELDS } from 'apps/pos/src/app/types/DeviceDeployment';
import { ModularPayload } from 'apps/pos/src/app/types/modular';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

interface DeviceMappingScannerComponentProps {
  isLoading: boolean;
  isBarCodeActive: boolean;
  disableBarcode: boolean;
  setActiveScanner: React.Dispatch<React.SetStateAction<ScannerType>>;
  handleModularUpdate: (payload: ModularPayload) => void;
}

const DeviceMappingScannerComponent = ({
  isLoading,
  isBarCodeActive,
  disableBarcode,
  setActiveScanner,
  handleModularUpdate,
}: DeviceMappingScannerComponentProps): JSX.Element => {
  const { id, step } = useParams();
  const navigate = useNavigate();
  const location = useLocation();
  const queryParams = new URLSearchParams(location.search);
  const deviceId = queryParams.get('deviceId');
  const handleTabChange = (tab: ScannerType) => {
    if (disableBarcode && tab === ScannerType.BAR_CODE) return;
    setActiveScanner(tab);
  };
  const [isVerifyingDeviceModalOpen, setIsVerifyingDeviceModalOpen] = useState<boolean>(false);
  const toast = useToast();

  const handleNextAction = () => {
    if (!isBarCodeActive) {
      navigate(
        `/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${id}/${step}/${AvailableComponents.DEVICE_MAPPING_SUCCESS}`,
      );
    } else {
      setActiveScanner(ScannerType.QR_CODE);
    }
  };

  const handleScannerDataUpdate = (scannedInfo: string, qrString: string = '') => {
    const qrStringParts = qrString.split('?');
    const queryParams = qrStringParts.length > 1 ? qrStringParts[1] : '';

    if (!queryParams) {
      toast.show({
        content: 'Please scan a valid QR',
        color: 'neutral',
        autoDismiss: true,
      });
      return;
    }

    const qrStringParams = new URLSearchParams(queryParams);
    if (!qrStringParams.get('tr')) {
      const separator = qrString.includes('?') ? '&' : '?';
      qrString = `${qrString}${separator}tr=${deviceId}`;
    }

    let payload;
    if (isBarCodeActive) {
      payload = {
        [DEVICE_DEPLOYMENT_FIELDS.CURRENT_DEVICE_ID_FIELD]: deviceId,
        [DEVICE_DEPLOYMENT_FIELDS.DEVICE_SERIAL_NUMBER_FIELD]: scannedInfo,
        [DEVICE_DEPLOYMENT_FIELDS.MODULAR_CALLBACK]: handleNextAction,
      };
    } else {
      payload = {
        [DEVICE_DEPLOYMENT_FIELDS.CURRENT_DEVICE_ID_FIELD]: deviceId,
        [DEVICE_DEPLOYMENT_FIELDS.DEVICE_VPA_FIELD]: scannedInfo,
        [DEVICE_DEPLOYMENT_FIELDS.DEVICE_QR_STRING_FIELD]: qrString,
        [DEVICE_DEPLOYMENT_FIELDS.MODULAR_CALLBACK]: handleNextAction,
      };
    }
    handleModularUpdate(payload);
  };
  const handleScannedData = (scannedData: string) => {
    if (isLoading) return;
    const parsedData = new URLSearchParams(scannedData);

    if (isBarCodeActive) {
      if (!!parsedData.get('pa')) {
        toast.show({
          content: 'Please scan a valid Barcode',
          color: 'neutral',
          autoDismiss: true,
        });
        return;
      }
      const barcodeValue = parsedData.entries().next().value[0];
      handleScannerDataUpdate(barcodeValue);
    } else {
      if (!parsedData.get('pa')) {
        toast.show({
          content: 'Please scan a valid QR',
          color: 'neutral',
          autoDismiss: true,
        });
        return;
      }
      const qrValue = parsedData.get('pa') || '';
      handleScannerDataUpdate(qrValue, scannedData);
    }
  };

  const { ref } = useZxing({
    onDecodeResult(scannedInfo) {
      handleScannedData(scannedInfo?.text);
    },
  });

  useEffect(() => {
    setIsVerifyingDeviceModalOpen(isLoading);
  }, [isLoading]);

  const handleManualEntryClick = () => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.WEBSITE_CTA,
      action: analyticsTypes.ANALYTICS_ACTIONS.CLICKED,
      properties: {
        label: 'Enter Details Manually',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_DEPLOYMENT,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.EXPLORE_DEVICE_DEPLOYMENT,
        section: 'Device Serial Number',
        subSection: 'Device Serial Number',
      },
    });
    navigate(
      `/${BASE_ROUTE}/${ONBOARDING_ROUTE}/${id}/${step}/${AvailableComponents.DEVICE_MAPPING_MANUAL}?deviceId=${deviceId}`,
    );
  };

  return (
    <StyledContainer>
      <StyledCard>
        <Text weight="semibold" color="surface.text.staticWhite.normal">
          {isBarCodeActive ? 'Please Scan the Device Serial Number' : 'Place the QR Code to Scan'}
        </Text>
      </StyledCard>
      <Box borderRadius="large" borderColor="surface.border.gray.subtle">
        {/* eslint-disable-next-line */}
        <video ref={ref} width="100%" height="100%" />
      </Box>
      <Text color="surface.text.staticWhite.subtle">
        {isBarCodeActive
          ? 'Present at the bottom of the device'
          : 'Placed on the front of your device'}
      </Text>
      <Box display="flex" flexDirection="column" width="70%">
        <Box
          display="flex"
          justifyContent={isBarCodeActive ? 'flex-end' : 'flex-start'}
          gap="spacing.6"
        >
          <ScannerButton
            onClick={() => {
              handleTabChange(ScannerType.BAR_CODE);
            }}
          >
            <Text
              color={
                isBarCodeActive
                  ? 'interactive.text.primary.normal'
                  : 'surface.text.staticWhite.normal'
              }
              size="medium"
            >
              Barcode
            </Text>
          </ScannerButton>
          <ScannerButton
            onClick={() => {
              handleTabChange(ScannerType.QR_CODE);
            }}
          >
            <Text
              color={
                !isBarCodeActive
                  ? 'interactive.text.primary.normal'
                  : 'surface.text.staticWhite.normal'
              }
              size="medium"
            >
              QR-Code
            </Text>
          </ScannerButton>
        </Box>
        <Box display="flex" justifyContent="center">
          <StyledDot />
        </Box>
      </Box>
      {isBarCodeActive ? (
        <Box width="100%">
          <Box display="flex" alignItems="center" width="100%" gap="spacing.3">
            <StyledLine />
            <Text color="surface.text.gray.muted" size="large">
              OR
            </Text>
            <StyledLine />
          </Box>
          <Button marginTop="spacing.4" isFullWidth onClick={handleManualEntryClick}>
            Enter Details Manually
          </Button>
        </Box>
      ) : null}
      <ModalWithBottomSheet
        content={
          <Box
            position="relative"
            display="flex"
            alignItems="center"
            justifyContent="center"
            flexDirection="column"
            gap="spacing.4"
          >
            <Spinner accessibilityLabel="scannerComponentSpinner" size="large" />
            <Text color="surface.text.gray.muted" size="large">
              Verifying Device
            </Text>
          </Box>
        }
        onDismiss={noop}
        isOpen={isVerifyingDeviceModalOpen}
        snapPoints={[0.8, 0.8, 0.8]}
      />
    </StyledContainer>
  );
};

export default DeviceMappingScannerComponent;
