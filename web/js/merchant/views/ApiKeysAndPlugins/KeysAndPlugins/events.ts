import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { KeyField, MerchantProduct } from './types';

const SCREEN = 'API Keys & Plugins';

type CTA =
  | 'Payment Channel'
  | 'Download Key'
  | 'Generate Key'
  | 'Generate New Key'
  | 'Integration Guide'
  | 'Go To Setup'
  | 'Add Link'
  | 'Save Link';

interface AsyncResult {
  status: 'Success' | 'Failure';
  failureReason?: string;
}

interface CustomProps {
  paymentChannel: string;
  product: MerchantProduct;
  plugin?: string;
  key?: KeyField;
}

export const trackKeyCopy = (props: CustomProps & Required<Pick<CustomProps, 'key'>>): void => {
  analyticsTrack({
    objectName: 'API Keys Key',
    actionName: 'Copied',
    screen: SCREEN,
    properties: {
      ...getCommonAnalyticsProperties(window?.rzp_user),
      ...props,
    },
  });
};

export const trackPluginSelect = (
  props: CustomProps & Required<Pick<CustomProps, 'plugin'>>,
): void => {
  analyticsTrack({
    objectName: 'API Keys Plugin',
    actionName: 'Selected',
    screen: SCREEN,
    properties: {
      ...getCommonAnalyticsProperties(window?.rzp_user),
      ...props,
    },
  });
};

export const trackCTAClick = (cta: CTA, props: CustomProps): void => {
  analyticsTrack({
    objectName: `API Keys ${cta}`,
    actionName: 'Clicked',
    screen: SCREEN,
    properties: {
      ...getCommonAnalyticsProperties(window?.rzp_user),
      ...props,
    },
  });
};

export const trackAsyncResult = (cta: CTA, props: CustomProps & AsyncResult): void => {
  analyticsTrack({
    objectName: `API Keys ${cta}`,
    actionName: 'Result',
    screen: SCREEN,
    properties: {
      ...getCommonAnalyticsProperties(window?.rzp_user),
      ...props,
    },
  });
};
