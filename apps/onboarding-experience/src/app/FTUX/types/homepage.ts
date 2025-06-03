import { ReactNode } from 'react';

/**
 * Enum representing the different UI sections/components that can be displayed on the homepage
 * These elements are dynamically selected and ordered based on the merchant's profile
 */
export enum HOMEPAGE_ELEMENTS {
  ACCORDION = 'ACCORDION',
  NOCODE_NUDGE = 'NOCODE_NUDGE',
  BROWSE_ALL = 'BROWSE_ALL',
  PAYMENT_HANDLE = 'PAYMENT_HANDLE',
  WAYS_FOR_PAYMENT = 'WAYS_FOR_PAYMENT',
  WEBSITE_NUDGE = 'WEBSITE_NUDGE',
  COMPLETED_TRANSACTION = 'COMPLETED_TRANSACTION',
  PREACTIVATION_BANNER = 'PREACTIVATION_BANNER',
}

export type AccordionDataType = {
  title: string;
  content: ReactNode;
  /** Flag indicating whether this step is incomplete to keep unchecked any previous step */
  isIncomplete?: boolean;
  getTitleSuffix?: (isExpanded: boolean) => ReactNode | null;
};
