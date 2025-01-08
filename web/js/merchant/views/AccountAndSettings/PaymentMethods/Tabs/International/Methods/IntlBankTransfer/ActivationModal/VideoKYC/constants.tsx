import { ClockIcon, FileIcon, HomeIcon, MapPinIcon } from '@razorpay/blade/components';

export const VIDEO_KYC_STEPS = [
  {
    title: 'All our partner agents are available from',
    subtitle: '10am-8pm IST, Mon-Fri',
    Icon: ClockIcon,
  },
  {
    title: 'Documents to be verified:',
    subtitle: 'Physical PAN and Aadhar',
    Icon: FileIcon,
  },
  {
    title: 'Your environment should be',
    subtitle: 'well lit and noise free',
    Icon: HomeIcon,
  },
  {
    title: 'Please ensure that you initiate VCIP while in',
    subtitle: 'India',
    Icon: MapPinIcon,
  },
];

export const STEPS = {
  PURPOSE_CODE: 1,
  IEC_CODE: 2,
  INTERNATIONAL_DETAILS: 3,
  VIDEO_KYC: 4,
} as const;
