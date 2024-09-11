/* eslint-disable i18n-rules/no-hardcoded-i18n-types */
import React, { useEffect, useState } from 'react';
import {
  Amount,
  Box,
  ChevronLeftIcon,
  Heading,
  IconButton,
  Text,
} from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';
import QRCode from 'qrcode';
import { QRImageStyled } from './styles';
import DevicePaymentStatusCheck from './DevicePaymentStatusCheck';
import { ModularPayload } from 'apps/pos/src/app/types/modular';
import RzpLogo from 'apps/pos/src/assets/rzpLogo.svg';
import PageError from 'apps/pos/src/app/components/PageError';
import DevicePaymentBg from 'apps/pos/src/assets/paymentScreenBg.webp';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

interface DeviceQRProps {
  qrCodeIntent: string;
  merchantName: string;
  amount: number;
  isUpdateModularLoading: boolean;
  handleModularUpdate: (payload: ModularPayload) => void;
}

const DeviceQR = ({
  qrCodeIntent,
  merchantName,
  amount,
  handleModularUpdate,
  isUpdateModularLoading,
}: DeviceQRProps): JSX.Element => {
  const [qrImage, setQRImage] = useState<string | null>(null);
  const [isError, setIsError] = useState(false);
  const [isQrLoading, setIsQrLoading] = useState(true);
  const navigate = useNavigate();

  const handleBackPress = () => {
    navigate(-1);
  };

  const initializeQRCode = async () => {
    if (!qrCodeIntent) return;
    try {
      const qrUrl = await QRCode.toDataURL(qrCodeIntent);
      if (typeof qrUrl === 'string') {
        setQRImage(qrUrl);
        return;
      }
    } catch {
      setIsError(true);
    } finally {
      setIsQrLoading(false);
    }
  };

  useEffect(() => {
    void initializeQRCode();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [qrCodeIntent]);

  useEffect(() => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.PAGE,
      action: analyticsTypes.ANALYTICS_ACTIONS.VIEWED,
      properties: {
        pageType: analyticsTypes.PAGE_TYPES.CHECKOUT_PAGE,
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.CHECKOUT_PAGE,
        l2FunnelStage: analyticsTypes.L2_FUNNEL_STAGE.PAGE_VIEW,
      },
    });
  }, []);

  if (isError || (!qrImage && !isQrLoading)) {
    return <PageError title="Payment Failed!" description="Failed to generate QR" />;
  }

  return (
    <Box padding="spacing.5" width="100%">
      <Box position="relative" zIndex={1}>
        <IconButton
          icon={ChevronLeftIcon}
          onClick={handleBackPress}
          accessibilityLabel="back-btn"
          size="large"
        />
      </Box>
      <Box
        display="flex"
        flexDirection="column"
        alignItems="center"
        justifyContent="center"
        width="100%"
        position="relative"
        zIndex={1}
      >
        <Box marginBottom="spacing.11" display="flex" flexDirection="column" alignItems="center">
          <Text
            size="small"
            variant="caption"
            color="surface.text.gray.subtle"
            marginBottom="spacing.1"
            textAlign="center"
          >
            Powered by
          </Text>
          <img src={RzpLogo} height="20px" alt="company-logo" />
        </Box>
        <Box
          marginBottom="spacing.8"
          display="flex"
          flexDirection="column"
          alignItems="center"
          width="100%"
        >
          <Heading size="large" marginBottom="spacing.5" textAlign="center">
            {isQrLoading ? 'Generating QR Code...' : 'Scan QR to Make Payment'}
          </Heading>
          {qrImage ? (
            <Box
              elevation="midRaised"
              borderRadius="medium"
              display="flex"
              flexDirection="column"
              alignItems="center"
              backgroundColor="surface.background.gray.intense"
            >
              <QRImageStyled src={qrImage} alt="qr-code" />
              <Text
                size="small"
                weight="semibold"
                marginX="spacing.5"
                marginBottom="spacing.5"
                textAlign="center"
              >
                SCAN & PAY WITH ANY UPI APP
              </Text>
            </Box>
          ) : null}
        </Box>
        <Box display="flex" flexDirection="column" alignItems="center">
          <Heading size="large" marginBottom="spacing.3" weight="regular">
            {merchantName}
          </Heading>
          <Amount
            isAffixSubtle={false}
            size="large"
            weight="semibold"
            value={amount}
            type="heading"
          />
        </Box>
      </Box>
      <Box
        display={{ base: 'block', s: 'none' }}
        position="fixed"
        top="0px"
        zIndex={0}
        left="0px"
        right="0px"
        bottom="0px"
      >
        <img src={DevicePaymentBg} height="100%" alt="device-payment-bg" />
      </Box>
      <DevicePaymentStatusCheck
        isUpdateModularLoading={isUpdateModularLoading}
        handleModularUpdate={handleModularUpdate}
      />
    </Box>
  );
};

export default DeviceQR;
