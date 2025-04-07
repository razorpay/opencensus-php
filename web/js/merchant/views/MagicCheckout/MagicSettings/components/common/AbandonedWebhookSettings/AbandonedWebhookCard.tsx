import React, { useEffect, useState } from 'react';
import {
  Box,
  Button,
  Divider,
  Heading,
  Switch,
  Text,
  TextInput,
  Spinner,
} from '@razorpay/blade/components';
import { makeSize } from '@razorpay/blade/utils';
import {
  fetchFeatureFlagData,
  fetchWebhookUrlData,
} from 'merchant/views/MagicCheckout/MagicSettings/components/common/AbandonedWebhookSettings/api';
import {
  ABANDONED_WEBHOOK_CARD_LABEL,
  UPDATE_FEATURE_TOGGLE,
  UPDATE_WEBHOOK_URL,
} from 'merchant/views/MagicCheckout/MagicSettings/components/common/AbandonedWebhookSettings/constants';
import { isValidWebsite } from '@libs/shared-utils';

const AbandonedWebhookCard = ({ onFeatureToggle, onSave, merchantId, loadingProperty }) => {
  const [abandonedWebhookUrl, setAbandonedWebhookUrl] = useState('');
  const [isAbandonedWebhookConfigEnabled, setIsAbandonedWebhookConfigEnabled] = useState(false);

  const isSaveButtonDisabled =
    abandonedWebhookUrl.trim() === '' ||
    !isValidWebsite({ url: abandonedWebhookUrl, allowHttpProtocol: true }) ||
    !isAbandonedWebhookConfigEnabled;

  const initializeAbandonedWebhook = async () => {
    try {
      const [webhookUrlResponse, featureFlagResponse] = await Promise.all([
        fetchWebhookUrlData(merchantId),
        fetchFeatureFlagData(),
      ]);
      setAbandonedWebhookUrl(webhookUrlResponse?.webhook_url || '');
      setIsAbandonedWebhookConfigEnabled(
        featureFlagResponse?.configs.one_cc_abandoned_webhook || false,
      );
    } catch (error) {}
  };

  useEffect(() => {
    initializeAbandonedWebhook();
  }, [merchantId]);

  return (
    <Box
      borderColor="surface.border.gray.muted"
      padding="spacing.5"
      backgroundColor="surface.background.gray.intense"
      borderRadius="medium"
      maxWidth={makeSize(460)}
    >
      <Box display="flex" justifyContent="space-between">
        <Heading>{ABANDONED_WEBHOOK_CARD_LABEL.HEADING}</Heading>
        {loadingProperty === UPDATE_FEATURE_TOGGLE ? (
          <Spinner accessibilityLabel="loading" />
        ) : (
          <Switch
            isChecked={isAbandonedWebhookConfigEnabled}
            accessibilityLabel={
              isAbandonedWebhookConfigEnabled
                ? ABANDONED_WEBHOOK_CARD_LABEL.TOGGLE_ON_ACCESSIBILITY_LABEL
                : ABANDONED_WEBHOOK_CARD_LABEL.TOGGLE_OFF_ACCESSIBILITY_LABEL
            }
            onChange={(e) => onFeatureToggle(e, setIsAbandonedWebhookConfigEnabled)}
          />
        )}
      </Box>
      <Divider marginTop="spacing.4" />
      <Box display="flex" flexDirection="column" marginTop="spacing.3">
        <Text color="surface.text.gray.muted">{ABANDONED_WEBHOOK_CARD_LABEL.DESCRIPTION}</Text>
        <TextInput
          placeholder={ABANDONED_WEBHOOK_CARD_LABEL.INPUT_PLACEHOLDER}
          accessibilityLabel={ABANDONED_WEBHOOK_CARD_LABEL.INPUT_ACCESSIBILITY_LABEL}
          marginTop="spacing.5"
          validationState={
            isValidWebsite({ url: abandonedWebhookUrl, allowHttpProtocol: true }) ||
            abandonedWebhookUrl.trim() === ''
              ? 'none'
              : 'error'
          }
          errorText={ABANDONED_WEBHOOK_CARD_LABEL.URL_ERROR_MESSAGE}
          value={abandonedWebhookUrl}
          isDisabled={!isAbandonedWebhookConfigEnabled}
          onChange={(event) => setAbandonedWebhookUrl(event.value || '')}
          aria-invalid={!isValidWebsite({ url: abandonedWebhookUrl, allowHttpProtocol: true })}
        />
        <Button
          onClick={() => onSave(abandonedWebhookUrl)}
          isDisabled={isSaveButtonDisabled}
          marginTop="spacing.5"
          alignSelf="end"
          isLoading={loadingProperty === UPDATE_WEBHOOK_URL}
        >
          {ABANDONED_WEBHOOK_CARD_LABEL.SAVE}
        </Button>
      </Box>
    </Box>
  );
};

export default AbandonedWebhookCard;
