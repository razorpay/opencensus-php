import {
  UPDATE_FEATURE_TOGGLE,
  UPDATE_WEBHOOK_URL,
} from 'merchant/views/MagicCheckout/MagicSettings/components/common/AbandonedWebhookSettings/constants';

export type WebhookUrl = {
  merchant_id: string;
  webhook_url: string;
};

export type FeatureFlagPayload = {
  merchant_id: string;
  mode: string;
  configs: {
    one_cc_abandoned_webhook: boolean;
  };
};

export type FeatureFlagResponse = {
  one_cc_abandoned_webhook: boolean;
};

export type LoadingProperties = typeof UPDATE_WEBHOOK_URL | typeof UPDATE_FEATURE_TOGGLE | '';
