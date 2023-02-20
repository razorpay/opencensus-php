import { RefObject } from 'react';
interface IframeActionType {
  event_type: 'product_details_updated' | 'storefront_details' | 'storefront_details_updated';
  data: any;
}

// TODO: Update this update
interface IProduct {
  [key: string]: any;
}
// TODO: Update this update
interface IStorefront {
  [key: string]: any;
}

export const onStorefrontProductChange = (products: IProduct[]): IframeActionType => ({
  event_type: 'product_details_updated',
  data: products,
});

export const onStorefrontChange = (storefrontDetails: IStorefront): IframeActionType => ({
  event_type: 'storefront_details',
  data: storefrontDetails,
});
export const onStorefrontDetailsChange = (
  storefrontDetails: Partial<IStorefront>,
): IframeActionType => ({
  event_type: 'storefront_details_updated',
  data: storefrontDetails,
});

export const getAllowedStorefrontDomain = (): string => {
  const isProd = window.location.hostname.endsWith('razorpay.com');
  // adding additional check, to avoid overriding the window object
  const envUrl = window.PP_ECOMMERCE_URL || '';
  if (isProd) {
    return envUrl.endsWith('razorpay.com')
      ? envUrl
      : 'https://frontend-payment-pages-ecommerce.razorpay.com';
  } else {
    return envUrl.endsWith('razorpay.in')
      ? envUrl
      : 'https://frontend-payment-pages-ecommerce.dev.razorpay.in';
  }
};

export const emitIframeEvent = (
  ref: RefObject<HTMLIFrameElement>,
  action: IframeActionType,
): void => {
  // Alternatively use a unique DOM node with an id & handle postMessage internally of the action
  if (!ref || !ref.current) {
    return;
  }
  const allowedDomain = getAllowedStorefrontDomain();
  ref.current.contentWindow?.postMessage(action, allowedDomain);
};
