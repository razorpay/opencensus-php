import { merchantFetch } from 'merchant/utils/ajax';
import type {
  FeatureFlagPayload,
  FeatureFlagResponse,
  WebhookUrl,
} from 'merchant/views/MagicCheckout/MagicSettings/components/common/AbandonedWebhookSettings/types';

export const fetchWebhookUrlData = async (merchantId: string): Promise<WebhookUrl | undefined> => {
  try {
    const response: { data: WebhookUrl } = await merchantFetch({
      url: `abandon_checkout/webhook_url?merchant_id=${merchantId}`,
    });
    return response.data;
  } catch (err) {
    return;
  }
};

export const updateWebhookUrlData = async (data: WebhookUrl): Promise<WebhookUrl | undefined> => {
  return merchantFetch({
    url: 'abandon_checkout/webhook_url',
    method: 'post',
    data,
  });
};

export const fetchFeatureFlagData = async (): Promise<FeatureFlagPayload | undefined> => {
  try {
    const response: {
      data: FeatureFlagPayload;
    } = await merchantFetch({
      url: `merchants/configs`,
    });

    return response.data;
  } catch (err) {
    return;
  }
};

export const updateFeatureFlagSettings = (
  data: FeatureFlagPayload,
): Promise<FeatureFlagResponse | undefined> => {
  return merchantFetch({
    url: 'magic/merchant/configs',
    method: 'post',
    data,
  });
};
