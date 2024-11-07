import { Platform } from 'merchant/views/MagicCheckout/types';

export type NewOffering = {
  title: string;
  description: string;
  image: File;
  ctaLink?: string;
  knowMoreLink?: string;
  docLink: (isRCODEnabled: boolean) => string;
  condition: (platorm: Platform, isRCODEnabled: boolean) => boolean;
};
