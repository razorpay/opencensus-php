import React, { useState } from 'react';
import AbandonedWebhookCard from 'merchant/views/MagicCheckout/MagicSettings/components/common/AbandonedWebhookSettings/AbandonedWebhookCard';
import {
  updateFeatureFlagSettings,
  updateWebhookUrlData,
} from 'merchant/views/MagicCheckout/MagicSettings/components/common/AbandonedWebhookSettings/api';
import { getMode } from '@libs/shared-utils';
import { notifyError, notifySuccess } from 'merchant/views/MagicCheckout/utils/notification';
import type { LoadingProperties } from 'merchant/views/MagicCheckout/MagicSettings/components/common/AbandonedWebhookSettings/types';
import {
  ABANDONED_WEBHOOK_CARD_LABEL,
  UPDATE_FEATURE_TOGGLE,
  UPDATE_WEBHOOK_URL,
} from 'merchant/views/MagicCheckout/MagicSettings/components/common/AbandonedWebhookSettings/constants';

const AbandonedWebhookSettings = ({ merchantId }) => {
  const [loadingProperty, setLoadingProperty] = useState<LoadingProperties>('');

  const handleRequestComplete = () => {
    setLoadingProperty('');
  };

  //handler func
  const handleWebhookUrlSave = (abandonedWebhookUrl) => {
    setLoadingProperty(UPDATE_WEBHOOK_URL);

    updateWebhookUrlData({
      merchant_id: merchantId,
      webhook_url: abandonedWebhookUrl,
    })
      .then(() => notifySuccess(ABANDONED_WEBHOOK_CARD_LABEL.WEBHOOK_URL_SAVE_MESSAGE))
      .catch(() => notifyError(ABANDONED_WEBHOOK_CARD_LABEL.ERROR_MESSAGE))
      .finally(handleRequestComplete);
  };

  const handleFeatureToggle = (e, setIsAbandonedWebhookConfigEnabled) => {
    setLoadingProperty(UPDATE_FEATURE_TOGGLE);
    updateFeatureFlagSettings({
      merchant_id: merchantId,
      mode: getMode(),
      configs: {
        one_cc_abandoned_webhook: e.isChecked,
      },
    })
      .then(() => {
        setIsAbandonedWebhookConfigEnabled(e.isChecked);
        notifySuccess(ABANDONED_WEBHOOK_CARD_LABEL.SETTING_SAVE_MESSAGE);
      })
      .catch(() => notifyError(ABANDONED_WEBHOOK_CARD_LABEL.ERROR_MESSAGE))
      .finally(handleRequestComplete);
  };

  return (
    <AbandonedWebhookCard
      onFeatureToggle={handleFeatureToggle}
      onSave={handleWebhookUrlSave}
      loadingProperty={loadingProperty}
      merchantId={merchantId}
    />
  );
};

export default AbandonedWebhookSettings;
