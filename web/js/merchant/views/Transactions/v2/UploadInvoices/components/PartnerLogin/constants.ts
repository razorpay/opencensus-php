import * as Yup from 'yup';

import GstPortalOnboardingSuccess from 'assets/cross-border/gst-portal-onboarded.png';
import NicPortalOnboardingSuccess from 'assets/cross-border/nic-portal-onboarded.png';
import GstPortalDeboarded from 'assets/cross-border/gst-portal-deboarded.png';

export const ONBOARDING_PARTNERS = {
  GST_PORTAL: 'gstportal',
  E_INVOICE: 'einvoice',
} as const;

export const ONBOARDING_STATUS = {
  ONBOARDED: 'onboarded',
  EXPIRED: 'expired',
} as const;

export const FORM_FIELDS = {
  [ONBOARDING_PARTNERS.GST_PORTAL]: [
    {
      component: 'TextInput',
      name: 'gstin',
      label: 'GSTIN',
      placeholder: '22AAAAA0000A1Z5',
      autoCapitalize: true,
    },
    {
      component: 'TextInput',
      name: 'username',
      label: 'Username',
      placeholder: 'John Doe',
      helpText: 'Username of GSTIN Portal login',
    },
    {
      component: 'CheckBox',
      name: 'terms',
      content:
        'I allow Razorpay to read and process data from my GST Portal account to create PDFs of e-invoices',
    },
  ],
  [ONBOARDING_PARTNERS.E_INVOICE]: [
    {
      component: 'TextInput',
      name: 'gstin',
      label: 'GSTIN',
      placeholder: '22AAAAA0000A1Z5',
      autoCapitalize: true,
    },
    {
      component: 'TextInput',
      name: 'username',
      label: 'Username',
      placeholder: 'John Doe',
      helpText: 'Username of GST E-Invoice Portal',
    },
    {
      component: 'PasswordInput',
      name: 'password',
      label: 'Password',
      helpText: 'Password of GST E-Invoice Portal',
    },
    {
      component: 'CheckBox',
      name: 'terms',
      content:
        'I allow Razorpay to read and process data from my GST Portal account to create PDFs of e-invoices',
    },
  ],
};

export const FORM_VALIDATION = {
  [ONBOARDING_PARTNERS.GST_PORTAL]: Yup.object().shape({
    gstin: Yup.string().required('GSTIN is a required field'),
    username: Yup.string().required('Username is a required field'),
    terms: Yup.boolean().test(
      'is-true',
      'Please accept Terms and Conditions to continue',
      (value) => value === true,
    ),
  }),
  [ONBOARDING_PARTNERS.E_INVOICE]: Yup.object().shape({
    gstin: Yup.string().required('GSTIN is a required field'),
    username: Yup.string().required('Username is a required field'),
    password: Yup.string().required('Username is a required field'),
    terms: Yup.boolean().test(
      'is-true',
      'Please accept Terms and Conditions to continue',
      (value) => value === true,
    ),
  }),
};

export const FORM_INITIAL_VALUES = {
  [ONBOARDING_PARTNERS.GST_PORTAL]: {
    gstin: '',
    username: '',
    terms: false,
  },
  [ONBOARDING_PARTNERS.E_INVOICE]: {
    gstin: '',
    username: '',
    password: '',
    terms: false,
  },
};

export const FORM_STEPS = {
  LOGIN_DETAILS: 'login_details',
  OTP: 'otp',
  SETUP_INFO: 'setup_info',
} as const;

export const OnboardingStepMapping = {
  gstPortalOnboarded: {
    image: GstPortalOnboardingSuccess,
    title: 'Wohoo! Setup is now complete for GST Portal 🎉',
    description:
      'You just need to set up GST e-invoice (NIC Portal) to experience seamless invoice sync on your dashboard!',
    question: 'Why is this step needed?',
    answer:
      "While the GST portal (that you've just set up) would be used for fetching invoices older than three days, the NIC e-Invoice portal holds invoices that are up to three days old",
  },
  nicPortalOnboarded: {
    image: NicPortalOnboardingSuccess,
    title: 'Congratulations! Setup is now complete for both GST and NIC Portal 🎉',
    description:
      'Just toggle the auto-fetch invoice switch on the Invoice page. Sit back and relax as invoices get auto-synced without the hassle of manually uploading invoices!',
  },
  gstPortalDeboarded: {
    image: GstPortalDeboarded,
    title:
      'Aw, Snap! Auto-fetching of e-invoices is currently disabled for you due to an unexpected failure.',
    description:
      'You can resume and turn on auto-fetch by following the setup guide and logging in to the GST portal again',
  },
};
