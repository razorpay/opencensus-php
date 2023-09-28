import { Dispatch, SetStateAction, CSSProperties } from 'react';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';

interface XCAStatus {
  showState: APIResponseType;
  proceededBank: APIResponseType;
}

interface userAttributes {
  count: number;
  entity: string;
  items: Array<Record<string, APIResponseType>>;
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
  imageArr: Array<ArrayOfImage>;
  handleClose(): any;
}
interface ArrayOfImage {
  imageStyle?: CSSProperties;
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
  updateModalView(): any;
  tracking: any;
}

// XCATrusted
interface XCATrustedProps {
  brandName: Array<ArrayOfImage>;
}

interface NSSModalCommonProps {
  setShowState: Dispatch<SetStateAction<string>>;
  user: User;
  handleClose: () => void;
  showNotification: (payload: Record<string, unknown>) => void;
  tracking: any;
}

interface NSSCommonProps {
  user: User;
  handleClose(): any;
  tracking: any;
}

interface XStyleDropdownProps {
  label: string;
  currentValue: string;
  itemArray: Array<Record<string, string>>;
  changeFunction: Dispatch<SetStateAction<string>>;
}

interface XCACTATypes extends RouteComponentProps {
  bankStatusForCTA: string;
  proceededBank: string;
  iciciPan: string;
  currentStatusMsg: Array<Record<string, any>>;
  setActivePageName?: (name: string) => void;
  setBaseLocation?: (name: string) => void;
}

type APIResponseType = any;

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
  XStyleDropdownProps,
  APIResponseType,
  XCACTATypes,
};
