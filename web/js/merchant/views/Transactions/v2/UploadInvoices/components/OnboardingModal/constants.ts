import GstStep1 from 'assets/cross-border/gst-step1.png';
import GstStep2 from 'assets/cross-border/gst-step2.png';
import GstStep3 from 'assets/cross-border/gst-step3.png';
import GstStep4 from 'assets/cross-border/gst-step4.png';
import NicStep1 from 'assets/cross-border/nic-step1.png';
import NicStep2 from 'assets/cross-border/nic-step2.png';
import NicStep3 from 'assets/cross-border/nic-step3.png';
import NicStep4 from 'assets/cross-border/nic-step4.png';

export const ONBOARDING_PARTNERS = {
  GST_PORTAL: 'gstportal',
  E_INVOICE: 'einvoice',
} as const;

export const ONBOARDING_STATUS = {
  ONBOARDED: 'onboarded',
  EXPIRED: 'expired',
} as const;

export const ONBOARDING_DATA = {
  [ONBOARDING_PARTNERS.GST_PORTAL]: [
    {
      description: 'Login to your GST account portal gst.gov.in',
      image: GstStep1,
    },
    {
      description: "Click on 'View profile' on GST portal dashboard.",
      image: GstStep2,
    },
    {
      description: "In the 'Quick links' section, go to 'Manage API access'",
      image: GstStep3,
    },
    {
      description: "Set 'Enable API request' to 'Yes' and set duration to '30 days' & 'Confirm'",
      image: GstStep4,
    },
  ],
  [ONBOARDING_PARTNERS.E_INVOICE]: [
    {
      description: 'Login to einvoice1.gst.gov.in. Enter your username and password.',
      image: NicStep1,
    },
    {
      description:
        'On the left nav, click on API registration > User Credentials > Create API User',
      image: NicStep2,
    },
    {
      description: 'Enter the OTP sent to your registered mobile number with the GST Portal',
      image: NicStep3,
    },
    {
      description:
        'Select "Through GSP", “Tera Software Limited”. Create your desired username and password then click on submit',
      image: NicStep4,
    },
  ],
};
