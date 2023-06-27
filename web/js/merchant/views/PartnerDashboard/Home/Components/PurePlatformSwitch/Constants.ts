import * as Yup from 'yup';
import ServiceProvided from './ApplicationFlow/Components/ServiceProvided';
import EvaluateUseCase from './ApplicationFlow/Components/EvaluateUseCase';
import ApplicationForm from './ApplicationFlow/Components/ApplicationForm';
import ApplicationReceived from './ApplicationFlow/Components/ApplicationReceived';
import HaveAllCapabilities from './ApplicationFlow/Components/HaveAllCapabilities';
import { ShowNotificationT } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';

export const SERVICE_PROVIDED = 'SERVICE_PROVIDED';
export const EVALUATE_USE_CASE = 'EVALUATE_USE_CASE';
export const APPLICATION_FORM = 'APPLICATION_FORM';
export const APPLICATION_RECEIVED = 'APPLICATION_RECEIVED';
export const HAVE_ALL_CAPABILITIES = 'HAVE_ALL_CAPABILITIES';

export const STEPS = {
  SERVICE_PROVIDED,
  EVALUATE_USE_CASE,
  APPLICATION_FORM,
  APPLICATION_RECEIVED,
  HAVE_ALL_CAPABILITIES,
};

export const STEP_COMPONENTS = {
  [SERVICE_PROVIDED]: ServiceProvided,
  [EVALUATE_USE_CASE]: EvaluateUseCase,
  [APPLICATION_FORM]: ApplicationForm,
  [APPLICATION_RECEIVED]: ApplicationReceived,
  [HAVE_ALL_CAPABILITIES]: HaveAllCapabilities,
};

export const SERVICES_PROVIDED_OPTIONS = [
  'Freelancers',
  'Entrepreneur',
  'Designer',
  'Digital Service Provider',
  'Accounting Software',
  'Web Developers',
  'Manage Platform',
  'CRM',
  'Early Stage Investor',
  'Booking Platform',
  'ERP',
  'Plugins for E-commerce platforms',
  'Others',
];

export const RESELLER_PARTNER_SERVICES = [
  'Freelancers',
  'Entrepreneur',
  'Designer',
  'Web Developers',
  'Early Stage Investor',
];

export const validationSchema = Yup.object().shape({
  phoneNumber: Yup.string()
    .trim()
    .required('Mobile Number is a required field')
    .length(10, 'Please enter a valid 10-digit mobile number')
    .matches(/^$|\+?[0-9]{8,15}$/, 'Please enter a valid number'),
  websiteURL: Yup.string()
    .required('Required!')
    .matches(
      /(http(s)?:\/\/.)?(www\.)?[-a-zA-Z0-9@:%._+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_+.~#?&//=]*)/,
      'Please enter a valid URL',
    ),
  otherInfo: Yup.string(),
});

export interface trackingExperimentsProps {
  shorterKYC: string;
  onboardAllAsReseller: string;
}

export interface StepComponentProps {
  setStep: (val: string) => void;
  setIsOpen: (val: boolean) => void;
  trackingExperiments: trackingExperimentsProps;
  user: any;
  closeModal: () => void;
}

export interface ApplicationReceivedProps {
  setIsOpen: (val: boolean) => void;
  trackingExperiments: trackingExperimentsProps;
  closeModal: () => void;
  user: any;
  setPartnerSwitchFlag: any;
}

export interface ApplicationFormProps {
  setStep: (val: string) => void;
  setIsOpen: (val: boolean) => void;
  trackingExperiments: trackingExperimentsProps;
  user: any;
  showNotification: ShowNotificationT;
}

export interface progressProps {
  firstStep?: number;
  secondStep?: number;
  thirdStep?: number;
}

export const trackingExperimentsTestProp = {
  shorterKYC: '',
  onboardAllAsReseller: '',
};

export const PARTNER_SWITCH_KEY = 'PARTNER_SWITCH_KEY';

export const PARTNER_SWITCH_TERMS_AND_CONDITIONS =
  'https://razorpay.com/s/terms/partner/aggregator-and-platform';
export const PARTNER_SWITCH_PRIVACY_POLICY = 'https://razorpay.com/privacy';
