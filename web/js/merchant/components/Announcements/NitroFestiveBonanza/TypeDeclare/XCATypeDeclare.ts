import { Dispatch, SetStateAction } from 'react';

interface XCAStatus {
  showState: null | 'offers-for-you' | 'neostone-tracker';
  proceededBank: null | string;
}

interface userAttributes {
  count: number;
  entity: string;
  items: Array<Record<string, unknown>>;
}

interface User extends Record<string, unknown> {
  business_operation_pin: string;
  business_type: string;
  business_category: string;
  ca_activation_status: string | null | undefined;
  attributes: null | undefined | userAttributes;
}
interface XCAHeaderProps {
  XCAHeaderText: string;
  imageArr?: Array<ArrayOfImage>;
  handleClose(): any;
}
interface ArrayOfImage {
  imagePath: string;
  imageAlt: string;
  stepDetails?: string;
}
// XCADetailSteps
interface XCADetailStepsProps {
  steps: Array<ArrayOfImage>;
}

// XCAInterested

interface XCAInterestedProps {
  XCAMainText: string;
  XCASubText: string;
  XCATooltipText: string;
  save: any;
}

// XCATrusted
interface XCATrustedProps {
  brandName: Array<ArrayOfImage>;
}

interface NSSModalCommonProps {
  setShowState: Dispatch<SetStateAction<string>>;
  user: User;
  handleClose: () => void;
}

interface NSSCommonProps {
  user: User;
  handleClose(): any;
}

interface ArrayOfImage {
  image: string;
  imageAlt: string;
}

export {
  XCAHeaderProps,
  XCADetailStepsProps,
  XCAInterestedProps,
  XCATrustedProps,
  ArrayOfImage,
  NSSModalCommonProps,
  NSSCommonProps,
  User,
  XCAStatus,
};
