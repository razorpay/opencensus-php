import {
  FileIcon,
  AlertTriangleIcon,
  VideoIcon,
  ClockIcon,
  CheckCircle2Icon,
  Theme,
} from '@razorpay/blade/components';

const NoticeRekycBanner = `${window.cdnBaseUrl}/static/assets/rekyc/notice_kyc.png`;
const DangerRekycBanner = `${window.cdnBaseUrl}/static/assets/rekyc/danger_kyc.png`;
const NeutralRekycBanner = `${window.cdnBaseUrl}/static/assets/rekyc/neutral_kyc.png`;
const InactiveRekycBanner = `${window.cdnBaseUrl}/static/assets/rekyc/inactive_kyc.png`;
const RejectedRekycBanner = `${window.cdnBaseUrl}/static/assets/rekyc/rejected_kyc.png`;
const ApprovedRekycBanner = `${window.cdnBaseUrl}/static/assets/rekyc/approved_kyc.png`;

const COLOR_MAP = {
  graySubtle: 'surface.text.gray.subtle',
  primaryIcon: 'surface.icon.primary.normal',
  primarySubtle: 'surface.background.primary.subtle',
  noticeIntense: 'feedback.text.notice.intense',
  noticeFaded: 'interactive.background.notice.faded',
  negativeNormal: 'interactive.text.negative.normal',
  negativeFaded: 'interactive.background.negative.faded',
  whiteNormal: 'surface.text.staticWhite.normal',
  onSeaSubtle: 'surface.icon.onSea.onSubtle',
  positiveSubtle: 'feedback.background.positive.subtle',
  positiveNormal: 'interactive.text.positive.normal',
  primaryIntense: 'surface.background.primary.intense',
  grayNormal: 'surface.text.gray.normal',
  primaryNormal: 'surface.border.primary.normal',
  onCloudSubtle: 'surface.text.onCloud.onSubtle',
  grayMuted: 'surface.text.gray.muted',
  noticeDefault: 'interactive.background.notice.default',
  negativeDefault: 'interactive.background.negative.default',
};

export const OWNER_REKYC_BANNER_INFO = {
  pending: {
    firstThirtyDays: {
      heading: 'Update your KYC',
      description:
        'As per RBI guidelines, you must verify your KYC periodically to validate the information shared during onboarding and update it if required. Please ensure all the necessary tasks are completed on time to avoid future disruptions. It will take 5-10 mins to complete the process.',
      IconComponent: FileIcon,
      showChip: false,
      iconColor: COLOR_MAP.primaryIcon,
      headingColor: COLOR_MAP.graySubtle,
      iconBackgroundColor: COLOR_MAP.primarySubtle,
      ctaText: 'Update KYC',
      dynamicDate: false,
    },
    thirtyToFifteenDays: {
      heading: (date: string) => `Update your KYC by ${date} to keep receiving settlements`,
      description:
        'As per RBI guidelines, you must verify your KYC periodically to validate the information shared during onboarding and update it if required. Please ensure all the necessary tasks are completed on time to avoid future disruptions. It will take 5-10 mins to complete the process.',
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.noticeIntense,
      headingColor: COLOR_MAP.graySubtle,
      iconBackgroundColor: COLOR_MAP.noticeFaded,
      ctaText: 'Update KYC',
      dynamicDate: true,
      chipType: 'notice',
    },
    foh: {
      heading: (date: string) =>
        `Update your KYC by ${date} to keep receiving payments and to enable settlements`,
      description:
        'As per RBI guidelines, you must verify your KYC periodically to validate the information shared during onboarding and update it if required. Please ensure all the necessary tasks are completed on time to avoid future disruptions. It will take 5-10 mins to complete the process.',
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.negativeNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeFaded,
      ctaText: 'Update KYC',
      dynamicDate: true,
      chipType: 'negative',
    },
    liveDisabled: {
      heading: 'Update your KYC to reactivate your account',
      description:
        'As per RBI guidelines, you must verify your KYC periodically to validate the information shared during onboarding and update it if required. Please ensure all the necessary tasks are completed on time to avoid future disruptions. It will take 5-10 mins to complete the process.',
      IconComponent: AlertTriangleIcon,
      showChip: false,
      iconColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeNormal,
      ctaText: 'Update KYC',
      dynamicDate: false,
    },
  },
  inProgress: {
    firstThirtyDays: {
      heading: 'Complete your KYC',
      description:
        "You're almost there! You've made progress on your KYC, but it's not complete yet. Please review your details and upload any remaining documents to finish the process.",
      IconComponent: FileIcon,
      showChip: false,
      iconColor: COLOR_MAP.primaryIcon,
      headingColor: COLOR_MAP.graySubtle,
      iconBackgroundColor: COLOR_MAP.primarySubtle,
      ctaText: 'Complete KYC',
      dynamicDate: false,
    },
    thirtyToFifteenDays: {
      heading: (date: string) => `Complete your KYC by ${date} to keep settlements active`,
      description:
        "You're almost there! You've made progress on your KYC, but it's not complete yet. Please review your details and upload any remaining documents to finish the process.",
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.noticeIntense,
      headingColor: COLOR_MAP.graySubtle,
      iconBackgroundColor: COLOR_MAP.noticeFaded,
      ctaText: 'Complete KYC',
      dynamicDate: true,
      chipType: 'notice',
    },
    foh: {
      heading: (date: string) =>
        `Complete your KYC by ${date} to keep receiving payments and to enable settlements`,
      description:
        "You're almost there! You've made progress on your KYC, but it's not complete yet. Please review your details and upload any remaining documents to finish the process.",
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.negativeNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeFaded,
      ctaText: 'Complete KYC',
      dynamicDate: true,
      chipType: 'negative',
    },
    liveDisabled: {
      heading: 'Complete your KYC to re-activate your account',
      description:
        "You're almost there! You've made progress on your KYC, but it's not complete yet. Please review your details and upload any remaining documents to finish the process.",
      IconComponent: AlertTriangleIcon,
      showChip: false,
      iconColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeNormal,
      ctaText: 'Complete KYC',
      dynamicDate: false,
    },
  },
  needsClarification: {
    firstThirtyDays: {
      heading: 'Resolve KYC issues',
      description:
        'Some of the submitted documents/details are incorrect or incomplete. Please resolve them at the earliest to complete your KYC process.',
      IconComponent: AlertTriangleIcon,
      showChip: false,
      iconColor: COLOR_MAP.noticeIntense,
      headingColor: COLOR_MAP.graySubtle,
      iconBackgroundColor: COLOR_MAP.noticeFaded,
      ctaText: 'Resolve Now',
      dynamicDate: false,
    },
    thirtyToFifteenDays: {
      heading: (date: string) => `Resolve KYC issues by ${date} to keep the settlements active`,
      description:
        'Some of the submitted documents/details are incorrect or incomplete. Please resolve them at the earliest to complete your KYC process.',
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.noticeIntense,
      headingColor: COLOR_MAP.graySubtle,
      iconBackgroundColor: COLOR_MAP.noticeFaded,
      ctaText: 'Resolve Now',
      dynamicDate: true,
      chipType: 'notice',
    },
    foh: {
      heading: (date: string) =>
        `Resolve KYC issues ${date} to keep receiving payments and to enable settlements`,
      description:
        'Some of the submitted documents/details are incorrect or incomplete. Please resolve them at the earliest to complete your KYC process.',
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.negativeNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeFaded,
      ctaText: 'Resolve Now',
      dynamicDate: true,
      chipType: 'negative',
    },
    liveDisabled: {
      heading: 'Resolve KYC issues to re-activate your account',
      description:
        'Some of the submitted documents/details are incorrect or incomplete. Please resolve them at the earliest to complete your KYC process.',
      IconComponent: AlertTriangleIcon,
      showChip: false,
      iconColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeNormal,
      ctaText: 'Resolve Now',
      dynamicDate: false,
    },
  },
  eddPending: {
    firstThirtyDays: {
      heading: 'Complete Video KYC',
      description:
        'Your KYC details has been reviewed. Please complete the video KYC process to finalize your account verification.',
      IconComponent: VideoIcon,
      showChip: false,
      iconColor: COLOR_MAP.primaryIcon,
      headingColor: COLOR_MAP.graySubtle,
      iconBackgroundColor: COLOR_MAP.primarySubtle,
      ctaText: 'Complete Video KYC',
      dynamicDate: false,
    },
    thirtyToFifteenDays: {
      heading: (date: string) => `Complete Video KYC by ${date} to keep settlements active`,
      description:
        'Your KYC details has been reviewed. Please complete the video KYC process to finalize your account verification.',
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.noticeIntense,
      headingColor: COLOR_MAP.graySubtle,
      iconBackgroundColor: COLOR_MAP.noticeFaded,
      ctaText: 'Complete Video KYC',
      dynamicDate: true,
      chipType: 'notice',
    },
    foh: {
      heading: (date: string) =>
        `Complete Video KYC by ${date} to keep payments active and enable settlements`,
      description:
        'Your KYC details has been reviewed. Please complete the video KYC process to finalize your account verification.',
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.negativeNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeFaded,
      ctaText: 'Update Video KYC',
      dynamicDate: true,
      chipType: 'negative',
    },
    liveDisabled: {
      heading: 'Complete Video KYC quickly to activate your disabled account',
      description:
        'As per RBI guidelines, your account is disabled for payments due to not completing Video KYC within time period. Complete Video KYC to resume your customer payments and settlements.',
      IconComponent: AlertTriangleIcon,
      showChip: false,
      iconColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeNormal,
      ctaText: 'Complete Video KYC',
      dynamicDate: false,
    },
  },
  underReview: {
    heading: 'Your KYC is under review',
    description:
      'We’ll notify you within 2-3 business days with a status update, or if we need any other details.',
    IconComponent: ClockIcon,
    showChip: false,
    iconColor: COLOR_MAP.primaryIcon,
    headingColor: COLOR_MAP.graySubtle,
    iconBackgroundColor: COLOR_MAP.primarySubtle,
    hideActionables: true,
    dynamicDate: false,
  },
  approved: {
    heading: 'Great News! Your KYC has been approved.',
    description: 'You can now continue doing your business with Razorpay.',
    IconComponent: CheckCircle2Icon,
    showChip: false,
    iconColor: COLOR_MAP.onSeaSubtle,
    headingColor: COLOR_MAP.positiveNormal,
    iconBackgroundColor: COLOR_MAP.positiveSubtle,
    hideActionables: true,
    dynamicDate: false,
    hideTimeline: true,
    showImageInBanner: true,
    imageSrc: ApprovedRekycBanner,
    hideIcon: true,
  },
  vkycUnderReview: {
    heading: 'Your Video KYC is under review',
    description:
      "We'll notify you within 2-3 business days with a status update, or if we need any other details.",
    IconComponent: ClockIcon,
    showChip: false,
    iconColor: COLOR_MAP.primaryIcon,
    headingColor: COLOR_MAP.graySubtle,
    iconBackgroundColor: COLOR_MAP.primarySubtle,
    hideActionables: true,
    dynamicDate: false,
  },
  rejected: {
    firstThirtyDays: {
      heading: "Your KYC wasn't approved",
      description:
        'Based on yout KYC, your business does not meet our terms of service. Please reach out to our support team for further assistance.',
      IconComponent: AlertTriangleIcon,
      showChip: false,
      iconColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeNormal,
      ctaText: 'Contact us',
      dynamicDate: false,
      hideTimeline: true,
    },
    thirtyToFifteenDays: {
      heading: (date: string) =>
        `Your KYC wasn't approved - Contact us and resolve before ${date} to keep the settlements active`,
      description:
        'Based on yout KYC, your business does not meet our terms of service. Please reach out to our support team for further assistance.',
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeNormal,
      ctaText: 'Contact us',
      dynamicDate: true,
      chipType: 'notice',
      hideTimeline: true,
    },
    foh: {
      heading: (date: string) =>
        `Your KYC wasn't approved - Contact us and resolve before ${date} to keep receiving payments`,
      description:
        'Based on yout KYC, your business does not meet our terms of service. Please reach out to our support team for further assistance.',
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeNormal,
      ctaText: 'Contact us',
      dynamicDate: true,
      chipType: 'negative',
      hideTimeline: true,
    },
    liveDisabled: {
      heading: 'Your account has been disabled for customer payments',
      description:
        'Based on yout KYC, your business does not meet our terms of service. Please reach out to our support team for further assistance.',
      IconComponent: AlertTriangleIcon,
      showChip: false,
      iconColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeNormal,
      ctaText: 'Contact us',
      dynamicDate: false,
      hideTimeline: true,
    },
  },
};

export const NON_OWNER_REKYC_BANNER_INFO = {
  pending: {
    firstThirtyDays: {
      heading: 'Notify the account owner to update KYC',
      description:
        'As per RBI guidelines, the account owner must verify the KYC periodically to validate the information shared during onboarding and update it if required. Please ensure all the necessary tasks are completed on time to avoid future disruptions. It will take 5-10 mins for them to complete the process.',
      IconComponent: FileIcon,
      showChip: false,
      iconColor: COLOR_MAP.primaryIcon,
      headingColor: COLOR_MAP.graySubtle,
      iconBackgroundColor: COLOR_MAP.primarySubtle,
      dynamicDate: false,
      hideActionables: true,
    },
    thirtyToFifteenDays: {
      heading: (date: string) =>
        `Notify the account owner to update KYC by ${date} to keep receiving settlements`,
      description:
        'As per RBI guidelines, the account owner must verify the KYC periodically to validate the information shared during onboarding and update it if required. Please ensure all the necessary tasks are completed on time to avoid future disruptions. It will take 5-10 mins for them to complete the process.',
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.noticeIntense,
      headingColor: COLOR_MAP.graySubtle,
      iconBackgroundColor: COLOR_MAP.noticeFaded,
      hideActionables: true,
      dynamicDate: true,
      chipType: 'notice',
    },
    foh: {
      heading: (date: string) =>
        `Notify the account owner to update KYC by ${date} to keep receiving payments and to enable settlements`,
      description:
        'As per RBI guidelines, the account owner must verify the KYC periodically to validate the information shared during onboarding and update it if required. Please ensure all the necessary tasks are completed on time to avoid future disruptions. It will take 5-10 mins for them to complete the process.',
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.negativeNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeFaded,
      hideActionables: true,
      dynamicDate: true,
      chipType: 'negative',
    },
    liveDisabled: {
      heading: 'Notify the account owner to update KYC and reactivate the account',
      description:
        'As per RBI guidelines, the account owner must verify the KYC periodically to validate the information shared during onboarding and update it if required. Please ensure all the necessary tasks are completed on time to avoid future disruptions. It will take 5-10 mins for them to complete the process.',
      IconComponent: AlertTriangleIcon,
      showChip: false,
      iconColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeNormal,
      hideActionables: true,
      dynamicDate: false,
    },
  },
  inProgress: {
    firstThirtyDays: {
      heading: 'Your KYC update is in progress!',
      description: (date: string) =>
        `The account owner is currently updating the KYC details. Please ensure that they update all the details by ${date}.`,
      IconComponent: FileIcon,
      showChip: false,
      iconColor: COLOR_MAP.primaryIcon,
      headingColor: COLOR_MAP.graySubtle,
      iconBackgroundColor: COLOR_MAP.primarySubtle,
      dynamicDate: false,
      hideActionables: true,
      dynamicDescription: true,
    },
    thirtyToFifteenDays: {
      heading: (date: string) =>
        `Notify Account Owner to complete the KYC by ${date} to keep settlements active`,
      description: (date: string) =>
        `The account owner is currently updating the KYC details. Please ensure that they update all the details by ${date}.`,
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.noticeIntense,
      headingColor: COLOR_MAP.graySubtle,
      iconBackgroundColor: COLOR_MAP.noticeFaded,
      hideActionables: true,
      dynamicDate: true,
      dynamicDescription: true,
      chipType: 'notice',
    },
    foh: {
      heading:
        'Notify Account Owner to complete the KYC to keep receiving payments and to enable settlements',
      description: (date: string) =>
        `The account owner is currently updating the KYC details. Please ensure that they update all the details by ${date}.`,
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.negativeNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeFaded,
      hideActionables: true,
      dynamicDate: true,
      dynamicDescription: true,
      chipType: 'negative',
    },
    liveDisabled: {
      heading: 'Notify Account Owner to complete the KYC and to re-activate the account',
      description: (date: string) =>
        `The account owner is currently updating the KYC details. Please ensure that they update all the details by ${date}.`,
      IconComponent: AlertTriangleIcon,
      showChip: false,
      iconColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeNormal,
      hideActionables: true,
      dynamicDate: false,
      dynamicDescription: true,
    },
  },
  eddPending: {
    firstThirtyDays: {
      heading: 'Your KYC update is in progress',
      description:
        'The account owner is currently updating the KYC details. No action is needed from you at this time. For any queries, please contact the account owner.',
      IconComponent: FileIcon,
      showChip: false,
      iconColor: COLOR_MAP.primaryIcon,
      headingColor: COLOR_MAP.graySubtle,
      iconBackgroundColor: COLOR_MAP.primarySubtle,
      dynamicDate: false,
      hideActionables: true,
    },
    thirtyToFifteenDays: {
      heading: (date: string) =>
        `Notify owner to complete KYC update by ${date} to keep settlements active`,
      description: (date: string) =>
        `The account owner is updating KYC details. To keep settlements active, ensure the update is completed by ${date}. For any queries, please contact the account owner.`,
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.noticeIntense,
      headingColor: COLOR_MAP.graySubtle,
      iconBackgroundColor: COLOR_MAP.noticeFaded,
      hideActionables: true,
      dynamicDate: true,
      dynamicDescription: true,
      chipType: 'notice',
    },
    foh: {
      heading: (date: string) =>
        `Notify owner to complete KYC update by ${date} to keep customer payments active`,
      description: (date: string) =>
        `The account owner is updating the KYC details. To keep settlements and payment active, ensure the update is completed by ${date}. For any queries, please contact the account owner.`,
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.negativeNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeFaded,
      hideActionables: true,
      dynamicDate: true,
      dynamicDescription: true,
      chipType: 'negative',
    },
    liveDisabled: {
      heading: 'Notify account owner to complete KYC and activate your disabled account',
      description:
        'As per RBI guidelines, your account is disabled for payments due to not updating KYC within time period. Update KYC to resume your customer payments and settlements.',
      IconComponent: AlertTriangleIcon,
      showChip: false,
      iconColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeNormal,
      hideActionables: true,
      dynamicDate: false,
    },
  },
  needsClarification: {
    firstThirtyDays: {
      heading: 'Notify Account Owner to Resolve KYC issues',
      description:
        'Some of the documents/details submitted by the account owner are incorrect/incomplete. Please ensure that they are able to resolve them to complete the KYC process.',
      IconComponent: FileIcon,
      showChip: false,
      iconColor: COLOR_MAP.primaryIcon,
      headingColor: COLOR_MAP.graySubtle,
      iconBackgroundColor: COLOR_MAP.primarySubtle,
      dynamicDate: false,
      hideActionables: true,
    },
    thirtyToFifteenDays: {
      heading: (date: string) =>
        `Notify account owner to resolve KYC issues by ${date} and keep settlements active`,
      description:
        'Some of the documents/details submitted by the account owner are incorrect/incomplete. Please ensure that they are able to resolve them to complete the KYC process.',
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.noticeIntense,
      headingColor: COLOR_MAP.graySubtle,
      iconBackgroundColor: COLOR_MAP.noticeFaded,
      hideActionables: true,
      dynamicDate: true,
      chipType: 'notice',
    },
    foh: {
      heading: (date: string) =>
        `Notify account owner to resolve KYC issues by ${date} and to keep receiving payments and to enable settlements`,
      description:
        'Some of the documents/details submitted by the account owner are incorrect/incomplete. Please ensure that they are able to resolve them to complete the KYC process.',
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.negativeNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeFaded,
      hideActionables: true,
      dynamicDate: true,
      chipType: 'negative',
      dynamicDescription: true,
    },
    liveDisabled: {
      heading: 'Notify account owner to resolve KYC issues and to re-activate the account',
      description:
        'Some of the documents/details submitted by the account owner are incorrect/incomplete. Please ensure that they are able to resolve them to complete the KYC process.',
      IconComponent: AlertTriangleIcon,
      showChip: false,
      iconColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeNormal,
      hideActionables: true,
      dynamicDate: false,
    },
  },
  underReview: {
    heading: 'Your KYC is under review',
    description:
      "We'll notify you within 2-3 business days with a status update, or if we need any other details.",
    IconComponent: ClockIcon,
    showChip: false,
    iconColor: COLOR_MAP.primaryIcon,
    headingColor: COLOR_MAP.graySubtle,
    iconBackgroundColor: COLOR_MAP.primarySubtle,
    hideActionables: true,
    dynamicDate: false,
  },
  approved: {
    heading: 'Great News! Your KYC has been approved.',
    description: 'You can now continue doing your business with Razorpay.',
    IconComponent: CheckCircle2Icon,
    showChip: false,
    iconColor: COLOR_MAP.onSeaSubtle,
    headingColor: COLOR_MAP.positiveNormal,
    iconBackgroundColor: COLOR_MAP.positiveSubtle,
    hideActionables: true,
    dynamicDate: false,
    hideTimeline: true,
    showImageInBanner: true,
    imageSrc: ApprovedRekycBanner,
    hideIcon: true,
  },
  vkycUnderReview: {
    heading: 'Your Video KYC is under review',
    description:
      "We'll notify you within 2-3 business days with a status update, or if we need any other details.",
    IconComponent: ClockIcon,
    showChip: false,
    iconColor: COLOR_MAP.primaryIcon,
    headingColor: COLOR_MAP.graySubtle,
    iconBackgroundColor: COLOR_MAP.primarySubtle,
    hideActionables: true,
    dynamicDate: false,
  },
  rejected: {
    firstThirtyDays: {
      heading: "Your KYC wasn't approved",
      description:
        'Based on the KYC, your business does not meet our terms of service. Please reach out to our support team for further assistance.',
      IconComponent: AlertTriangleIcon,
      showChip: false,
      iconColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeNormal,
      ctaText: 'Contact us',
      dynamicDate: false,
      hideTimeline: true,
      hideActionables: true,
    },
    thirtyToFifteenDays: {
      heading: (date: string) =>
        `Your KYC wasn't approved - Contact us and resolve before ${date} to keep the settlements active`,
      description:
        'Based on the KYC, your business does not meet our terms of service. Please reach out to our support team for further assistance.',
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeNormal,
      ctaText: 'Contact us',
      dynamicDate: true,
      chipType: 'notice',
      hideTimeline: true,
      hideActionables: true,
    },
    foh: {
      heading: (date: string) =>
        `Your KYC couldn't be approved - Contact us and resolve before ${date} to keep receiving payments`,
      description:
        'Based on the KYC, your business does not meet our terms of service. Please reach out to our support team for further assistance.',
      IconComponent: AlertTriangleIcon,
      showChip: true,
      iconColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeNormal,
      ctaText: 'Contact us',
      dynamicDate: true,
      chipType: 'negative',
      hideTimeline: true,
      hideActionables: true,
    },
    liveDisabled: {
      heading: 'Your account has been disabled for customer payments',
      description:
        'Based on the KYC, your business does not meet our terms of service. Please reach out to our support team for further assistance.',
      IconComponent: AlertTriangleIcon,
      showChip: false,
      iconColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.negativeNormal,
      iconBackgroundColor: COLOR_MAP.negativeNormal,
      ctaText: 'Contact us',
      dynamicDate: false,
      hideTimeline: true,
      hideActionables: true,
    },
  },
};

export const DYNAMIC_REKYC_STATUS = [
  'pending',
  'inProgress',
  'needsClarification',
  'eddPending',
  'rejected',
];

export const STATUSES_TO_SHOW_TIMELINE = [
  'pending',
  'underReview',
  'needsClarification',
  'eddPending',
  'inProgress',
  'vkycUnderReview',
];

export const FINAL_STEPS_MAP = {
  pending: {
    firstThirtyDays: {
      firstStep: {
        heading: 'Submit KYC',
        IconComponent: FileIcon,
        iconColor: COLOR_MAP.whiteNormal,
        iconBackgroundColor: COLOR_MAP.primaryIntense,
        currentStep: true,
        headingColor: COLOR_MAP.grayNormal,
        textContent: 1,
        textColor: COLOR_MAP.whiteNormal,
      },
      secondStep: {
        heading: 'Under Review',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 2,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
      thirdStep: {
        heading: 'KYC Completed',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 3,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
    },
    thirtyToFifteenDays: {
      firstStep: {
        heading: 'Submit KYC',
        IconComponent: AlertTriangleIcon,
        iconColor: COLOR_MAP.noticeDefault,
        iconBackgroundColor: COLOR_MAP.noticeFaded,
        currentStep: true,
        headingColor: COLOR_MAP.grayNormal,
        textContent: 1,
        textColor: COLOR_MAP.whiteNormal,
      },
      secondStep: {
        heading: 'Under Review',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 2,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
      thirdStep: {
        heading: 'KYC Completed',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 3,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
    },
    foh: {
      firstStep: {
        heading: 'Submit KYC',
        IconComponent: AlertTriangleIcon,
        iconColor: COLOR_MAP.negativeDefault,
        iconBackgroundColor: COLOR_MAP.negativeFaded,
        currentStep: true,
        headingColor: COLOR_MAP.grayNormal,
        textContent: 1,
        textColor: COLOR_MAP.whiteNormal,
      },
      secondStep: {
        heading: 'Under Review',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 2,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
      thirdStep: {
        heading: 'KYC Completed',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 3,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
    },
    liveDisabled: {
      firstStep: {
        heading: 'Submit KYC',
        IconComponent: AlertTriangleIcon,
        iconColor: COLOR_MAP.whiteNormal,
        iconBackgroundColor: COLOR_MAP.negativeDefault,
        currentStep: true,
        headingColor: COLOR_MAP.grayNormal,
        textContent: 1,
        textColor: COLOR_MAP.whiteNormal,
      },
      secondStep: {
        heading: 'Under Review',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 2,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
      thirdStep: {
        heading: 'KYC Completed',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 3,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
    },
  },
  inProgress: {
    firstThirtyDays: {
      firstStep: {
        heading: 'Complete KYC',
        IconComponent: FileIcon,
        iconColor: COLOR_MAP.whiteNormal,
        iconBackgroundColor: COLOR_MAP.primaryIntense,
        currentStep: true,
        headingColor: COLOR_MAP.grayNormal,
        textContent: 1,
        textColor: COLOR_MAP.whiteNormal,
      },
      secondStep: {
        heading: 'Under Review',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 2,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
      thirdStep: {
        heading: 'KYC Completed',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 3,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
    },
    thirtyToFifteenDays: {
      firstStep: {
        heading: 'Complete KYC',
        IconComponent: AlertTriangleIcon,
        iconColor: COLOR_MAP.noticeDefault,
        iconBackgroundColor: COLOR_MAP.noticeFaded,
        currentStep: true,
        headingColor: COLOR_MAP.grayNormal,
        textContent: 1,
        textColor: COLOR_MAP.whiteNormal,
      },
      secondStep: {
        heading: 'Under Review',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 2,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
      thirdStep: {
        heading: 'Video KYC',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 3,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
    },
    foh: {
      firstStep: {
        heading: 'Complete KYC',
        IconComponent: AlertTriangleIcon,
        iconColor: COLOR_MAP.negativeDefault,
        iconBackgroundColor: COLOR_MAP.negativeFaded,
        currentStep: true,
        headingColor: COLOR_MAP.grayNormal,
        textContent: 1,
        textColor: COLOR_MAP.whiteNormal,
      },
      secondStep: {
        heading: 'Under Review',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 2,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
      thirdStep: {
        heading: 'KYC Completed',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 3,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
    },
    liveDisabled: {
      firstStep: {
        heading: 'Complete KYC',
        IconComponent: AlertTriangleIcon,
        iconColor: COLOR_MAP.whiteNormal,
        iconBackgroundColor: COLOR_MAP.negativeDefault,
        currentStep: true,
        headingColor: COLOR_MAP.grayNormal,
        textContent: 1,
        textColor: COLOR_MAP.whiteNormal,
      },
      secondStep: {
        heading: 'Under Review',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 2,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
      thirdStep: {
        heading: 'Video KYC',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 3,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
    },
  },
  needsClarification: {
    firstThirtyDays: {
      firstStep: {
        heading: 'KYC Submitted',
        IconComponent: CheckCircle2Icon,
        iconColor: COLOR_MAP.onSeaSubtle,
        iconBackgroundColor: COLOR_MAP.positiveSubtle,
        headingColor: COLOR_MAP.grayMuted,
        textContent: 1,
        textColor: COLOR_MAP.onCloudSubtle,
        completed: true,
      },
      secondStep: {
        heading: 'Resolve KYC',
        textContent: 2,
        currentStep: true,
        IconComponent: AlertTriangleIcon,
        iconColor: COLOR_MAP.noticeIntense,
        iconBackgroundColor: COLOR_MAP.noticeFaded,
        textColor: COLOR_MAP.whiteNormal,
        headingColor: COLOR_MAP.grayNormal,
      },
      thirdStep: {
        heading: 'Video KYC',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 3,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
    },
    thirtyToFifteenDays: {
      firstStep: {
        heading: 'KYC Submitted',
        IconComponent: CheckCircle2Icon,
        iconColor: COLOR_MAP.onSeaSubtle,
        iconBackgroundColor: COLOR_MAP.positiveSubtle,
        currentStep: true,
        headingColor: COLOR_MAP.grayMuted,
        textContent: 1,
        textColor: COLOR_MAP.onCloudSubtle,
        completed: true,
      },
      secondStep: {
        heading: 'Resolve KYC',
        textContent: 2,
        currentStep: true,
        IconComponent: AlertTriangleIcon,
        iconColor: COLOR_MAP.noticeDefault,
        iconBackgroundColor: COLOR_MAP.noticeFaded,
        textColor: COLOR_MAP.whiteNormal,
        headingColor: COLOR_MAP.grayNormal,
      },
      thirdStep: {
        heading: 'KYC Completed',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 3,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
    },
    foh: {
      firstStep: {
        heading: 'KYC Submitted',
        IconComponent: CheckCircle2Icon,
        iconColor: COLOR_MAP.onSeaSubtle,
        iconBackgroundColor: COLOR_MAP.positiveSubtle,
        currentStep: true,
        headingColor: COLOR_MAP.grayMuted,
        textContent: 1,
        textColor: COLOR_MAP.onCloudSubtle,
        completed: true,
      },
      secondStep: {
        heading: 'Resolve KYC',
        textContent: 2,
        currentStep: true,
        IconComponent: AlertTriangleIcon,
        iconColor: COLOR_MAP.negativeDefault,
        iconBackgroundColor: COLOR_MAP.negativeFaded,
        textColor: COLOR_MAP.whiteNormal,
        headingColor: COLOR_MAP.grayNormal,
      },
      thirdStep: {
        heading: 'Video KYC',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 3,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
    },
    liveDisabled: {
      firstStep: {
        heading: 'KYC Submitted',
        IconComponent: CheckCircle2Icon,
        iconColor: COLOR_MAP.onSeaSubtle,
        iconBackgroundColor: COLOR_MAP.positiveSubtle,
        currentStep: true,
        headingColor: COLOR_MAP.grayMuted,
        textContent: 1,
        textColor: COLOR_MAP.onCloudSubtle,
        completed: true,
      },
      secondStep: {
        heading: 'Resolve KYC',
        textContent: 2,
        currentStep: true,
        IconComponent: AlertTriangleIcon,
        iconColor: COLOR_MAP.whiteNormal,
        iconBackgroundColor: COLOR_MAP.negativeNormal,
        textColor: COLOR_MAP.whiteNormal,
        headingColor: COLOR_MAP.grayNormal,
      },
      thirdStep: {
        heading: 'KYC Completed',
        borderColor: COLOR_MAP.primaryNormal,
        textContent: 3,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
    },
  },
  eddPending: {
    firstThirtyDays: {
      firstStep: {
        heading: 'KYC Submitted',
        IconComponent: CheckCircle2Icon,
        iconColor: COLOR_MAP.onSeaSubtle,
        iconBackgroundColor: COLOR_MAP.positiveSubtle,
        headingColor: COLOR_MAP.grayMuted,
        textContent: 1,
        textColor: COLOR_MAP.onCloudSubtle,
        completed: true,
      },
      secondStep: {
        heading: 'KYC Reviewed',
        textContent: 2,
        completed: true,
        IconComponent: CheckCircle2Icon,
        iconColor: COLOR_MAP.onSeaSubtle,
        iconBackgroundColor: COLOR_MAP.positiveSubtle,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
      thirdStep: {
        heading: 'Video KYC',
        textContent: 3,
        textColor: COLOR_MAP.whiteNormal,
        headingColor: COLOR_MAP.grayNormal,
        currentStep: true,
        IconComponent: VideoIcon,
        iconColor: COLOR_MAP.primaryIcon,
        iconBackgroundColor: COLOR_MAP.primarySubtle,
      },
    },
    thirtyToFifteenDays: {
      firstStep: {
        heading: 'KYC Submitted',
        IconComponent: CheckCircle2Icon,
        iconColor: COLOR_MAP.onSeaSubtle,
        iconBackgroundColor: COLOR_MAP.positiveSubtle,
        headingColor: COLOR_MAP.grayMuted,
        textContent: 1,
        textColor: COLOR_MAP.onCloudSubtle,
        completed: true,
      },
      secondStep: {
        heading: 'KYC Reviewed',
        textContent: 2,
        completed: true,
        IconComponent: CheckCircle2Icon,
        iconColor: COLOR_MAP.onSeaSubtle,
        iconBackgroundColor: COLOR_MAP.positiveSubtle,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
      thirdStep: {
        heading: 'KYC Completed',
        textContent: 3,
        textColor: COLOR_MAP.whiteNormal,
        headingColor: COLOR_MAP.grayNormal,
        currentStep: true,
        IconComponent: AlertTriangleIcon,
        iconColor: COLOR_MAP.noticeDefault,
        iconBackgroundColor: COLOR_MAP.noticeFaded,
      },
    },
    foh: {
      firstStep: {
        heading: 'KYC Submitted',
        IconComponent: CheckCircle2Icon,
        iconColor: COLOR_MAP.onSeaSubtle,
        iconBackgroundColor: COLOR_MAP.positiveSubtle,
        headingColor: COLOR_MAP.grayMuted,
        textContent: 1,
        textColor: COLOR_MAP.onCloudSubtle,
        completed: true,
      },
      secondStep: {
        heading: 'KYC Reviewed',
        textContent: 2,
        completed: true,
        IconComponent: CheckCircle2Icon,
        iconColor: COLOR_MAP.onSeaSubtle,
        iconBackgroundColor: COLOR_MAP.positiveSubtle,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
      thirdStep: {
        heading: 'KYC Completed',
        textContent: 3,
        textColor: COLOR_MAP.whiteNormal,
        headingColor: COLOR_MAP.grayNormal,
        currentStep: true,
        IconComponent: AlertTriangleIcon,
        iconColor: COLOR_MAP.negativeDefault,
        iconBackgroundColor: COLOR_MAP.negativeFaded,
      },
    },
    liveDisabled: {
      firstStep: {
        heading: 'KYC Submitted',
        IconComponent: CheckCircle2Icon,
        iconColor: COLOR_MAP.onSeaSubtle,
        iconBackgroundColor: COLOR_MAP.positiveSubtle,
        headingColor: COLOR_MAP.grayMuted,
        textContent: 1,
        textColor: COLOR_MAP.onCloudSubtle,
        completed: true,
      },
      secondStep: {
        heading: 'KYC Reviewed',
        textContent: 2,
        completed: true,
        IconComponent: CheckCircle2Icon,
        iconColor: COLOR_MAP.onSeaSubtle,
        iconBackgroundColor: COLOR_MAP.positiveSubtle,
        textColor: COLOR_MAP.onCloudSubtle,
        headingColor: COLOR_MAP.grayMuted,
      },
      thirdStep: {
        heading: 'KYC Completed',
        textContent: 3,
        textColor: COLOR_MAP.whiteNormal,
        headingColor: COLOR_MAP.grayNormal,
        currentStep: true,
        IconComponent: AlertTriangleIcon,
        iconColor: COLOR_MAP.whiteNormal,
        iconBackgroundColor: COLOR_MAP.negativeDefault,
      },
    },
  },
  underReview: {
    firstStep: {
      heading: 'KYC Submitted',
      IconComponent: CheckCircle2Icon,
      iconColor: COLOR_MAP.onSeaSubtle,
      iconBackgroundColor: COLOR_MAP.positiveSubtle,
      headingColor: COLOR_MAP.grayMuted,
      textContent: 1,
      textColor: COLOR_MAP.onCloudSubtle,
      completed: true,
    },
    secondStep: {
      heading: 'Under Review',
      textContent: 2,
      currentStep: true,
      IconComponent: ClockIcon,
      iconColor: COLOR_MAP.whiteNormal,
      iconBackgroundColor: COLOR_MAP.primaryIntense,
      textColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.grayNormal,
    },
    thirdStep: {
      heading: 'KYC Completed',
      borderColor: COLOR_MAP.primaryNormal,
      textContent: 3,
      textColor: COLOR_MAP.onCloudSubtle,
      headingColor: COLOR_MAP.grayMuted,
    },
  },
  vkycUnderReview: {
    firstStep: {
      heading: 'KYC Submitted',
      IconComponent: CheckCircle2Icon,
      iconColor: COLOR_MAP.onSeaSubtle,
      iconBackgroundColor: COLOR_MAP.positiveSubtle,
      headingColor: COLOR_MAP.grayMuted,
      textContent: 1,
      textColor: COLOR_MAP.whiteNormal,
      completed: true,
    },
    secondStep: {
      heading: 'KYC Reviewed',
      textContent: 2,
      IconComponent: CheckCircle2Icon,
      iconColor: COLOR_MAP.onSeaSubtle,
      iconBackgroundColor: COLOR_MAP.positiveSubtle,
      textColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.grayMuted,
      completed: true,
    },
    thirdStep: {
      heading: 'KYC Completed',
      IconComponent: ClockIcon,
      iconColor: COLOR_MAP.whiteNormal,
      iconBackgroundColor: COLOR_MAP.primaryIntense,
      currentStep: true,
      textContent: 3,
      textColor: COLOR_MAP.whiteNormal,
      headingColor: COLOR_MAP.grayNormal,
    },
  },
};

export const OWNER_MODAL_CONTENT = {
  pending: {
    firstThirtyDays: {
      imageSrc: NeutralRekycBanner,
      heading: 'Update your KYC',
      description:
        'As per RBI guidelines, you must verify your KYC periodically to validate the information shared during onboarding and update it if required. Please ensure all the necessary tasks are completed on time to avoid future disruptions. It will take 5-10 mins to complete the process.',
      ctaText: 'Update KYC',
      badgeText: 'Action Required',
    },
    thirtyToFifteenDays: {
      imageSrc: NoticeRekycBanner,
      heading: (date: string) => `Update your KYC by ${date} to keep receiving settlements`,
      description:
        'As per RBI guidelines, you must verify your KYC periodically to validate the information shared during onboarding and update it if required. Please ensure all the necessary tasks are completed on time to avoid future disruptions. It will take 5-10 mins to complete the process.',
      ctaText: 'Update KYC',
      dynamicHeading: true,
    },
    foh: {
      imageSrc: DangerRekycBanner,
      heading: (date: string) =>
        `Update your KYC by ${date} to keep receiving payments and to enable settlements`,
      description:
        'As per RBI guidelines, you must verify your KYC periodically to validate the information shared during onboarding and update it if required. Please ensure all the necessary tasks are completed on time to avoid future disruptions. It will take 5-10 mins to complete the process.',
      ctaText: 'Update KYC',
      dynamicHeading: true,
    },
    liveDisabled: {
      imageSrc: InactiveRekycBanner,
      heading: 'Update your KYC to reactivate your account',
      description:
        'As per RBI guidelines, you must verify your KYC periodically to validate the information shared during onboarding and update it if required. Please ensure all the necessary tasks are completed on time to avoid future disruptions. It will take 5-10 mins to complete the process.',
      ctaText: 'Update KYC',
      badgeText: 'Action Required',
    },
  },
  inProgress: {
    firstThirtyDays: {
      imageSrc: NeutralRekycBanner,
      heading: 'Complete your KYC',
      description:
        "You're almost there! You've made progress on your KYC, but it's not complete yet. Please review your details and upload any remaining documents to finish the process.",
      ctaText: 'Complete KYC',
      badgeText: 'Action Required',
    },
    thirtyToFifteenDays: {
      imageSrc: NoticeRekycBanner,
      heading: (date: string) => `Complete your KYC by ${date} to keep settlements active`,
      description:
        "You're almost there! You've made progress on your KYC, but it's not complete yet. Please review your details and upload any remaining documents to finish the process.",
      ctaText: 'Complete KYC',
      dynamicHeading: true,
    },
    foh: {
      imageSrc: DangerRekycBanner,
      heading: (date: string) =>
        `Complete your KYC by ${date} to keep receiving payments and to enable settlements`,
      description:
        "You're almost there! You've made progress on your KYC, but it's not complete yet. Please review your details and upload any remaining documents to finish the process.",
      ctaText: 'Complete KYC',
      dynamicHeading: true,
    },
    liveDisabled: {
      imageSrc: InactiveRekycBanner,
      heading: 'Complete your KYC to re-activate your account',
      description:
        "You're almost there! You've made progress on your KYC, but it's not complete yet. Please review your details and upload any remaining documents to finish the process.",
      ctaText: 'Complete KYC',
      badgeText: 'Action Required ',
    },
  },
  needsClarification: {
    firstThirtyDays: {
      imageSrc: NeutralRekycBanner,
      heading: 'Resolve KYC issues',
      description:
        'Some of the submitted documents/details are incorrect or incomplete. Please resolve them at the earliest to complete your KYC process.',
      ctaText: 'Resolve Now',
      badgeText: 'Action Required',
    },
    thirtyToFifteenDays: {
      imageSrc: NoticeRekycBanner,
      heading: (date: string) => `Resolve KYC issues by ${date} to keep the settlements active`,
      description:
        'Some of the submitted documents/details are incorrect or incomplete. Please resolve them at the earliest to complete your KYC process.',
      ctaText: 'Resolve Now',
      dynamicHeading: true,
    },
    foh: {
      imageSrc: DangerRekycBanner,
      heading: (date: string) =>
        `Resolve KYC issues ${date} to keep receiving payments and to enable settlements`,
      description:
        'Some of the submitted documents/details are incorrect or incomplete. Please resolve them at the earliest to complete your KYC process.',
      ctaText: 'Resolve Now',
      dynamicHeading: true,
    },
    liveDisabled: {
      imageSrc: InactiveRekycBanner,
      heading: 'Resolve KYC issues to re-activate your account',
      description:
        'Some of the submitted documents/details are incorrect or incomplete. Please resolve them at the earliest to complete your KYC process.',
      ctaText: 'Resolve Now',
      badgeText: 'Action Required',
    },
  },
  rejected: {
    firstThirtyDays: {
      imageSrc: RejectedRekycBanner,
      heading: "Your KYC wasn't approved",
      description:
        'Based on yout KYC, your business does not meet our terms of service. Please reach out to our support team for further assistance.',
      ctaText: 'Contact us',
      badgeText: 'Action Required',
    },
    thirtyToFifteenDays: {
      imageSrc: RejectedRekycBanner,
      heading: (date: string) =>
        `Your KYC wasn't approved - Contact us and resolve before ${date} to keep the settlements active`,
      description:
        'Based on yout KYC, your business does not meet our terms of service. Please reach out to our support team for further assistance.',
      ctaText: 'Contact us',
      dynamicHeading: true,
    },
    foh: {
      imageSrc: RejectedRekycBanner,
      heading: (date: string) =>
        `Your KYC wasn't approved - Contact us and resolve before ${date} to keep receiving payments`,
      description:
        'Based on yout KYC, your business does not meet our terms of service. Please reach out to our support team for further assistance.',
      ctaText: 'Contact us',
      dynamicHeading: true,
    },
    liveDisabled: {
      imageSrc: RejectedRekycBanner,
      heading: 'Your account has been disabled for customer payments',
      description:
        'Based on yout KYC, your business does not meet our terms of service. Please reach out to our support team for further assistance.',
      ctaText: 'Contact us',
      badgeText: 'Action Required',
    },
  },
  eddPending: {
    firstThirtyDays: {
      imageSrc: NeutralRekycBanner,
      heading: 'Complete Video KYC',
      description:
        'Your KYC details have been reviewed, but video KYC is still pending. Please complete the video KYC process to finalize your account verification.',
      ctaText: 'Complete Video KYC',
      badgeText: 'Action Required',
    },
    thirtyToFifteenDays: {
      imageSrc: NoticeRekycBanner,
      heading: (date: string) => `Complete Video KYC by ${date} to keep settlements active`,
      description:
        'Your KYC details have been reviewed, but video KYC is still pending. Please complete the video KYC process to finalize your account verification.',
      ctaText: 'Complete Video KYC',
      dynamicHeading: true,
    },
    foh: {
      imageSrc: DangerRekycBanner,
      heading: (date: string) => `Complete Video KYC by ${date} to keep customer payments active`,
      description:
        'Your KYC details have been reviewed, but video KYC is still pending. Please complete the video KYC process to finalize your account verification.',
      ctaText: 'Complete Video KYC',
      dynamicHeading: true,
    },
    liveDisabled: {
      imageSrc: InactiveRekycBanner,
      heading: 'Complete Video KYC quickly to activate your disabled account',
      description:
        'Your KYC details have been reviewed, but video KYC is still pending. Please complete the video KYC process to finalize your account verification.',
      ctaText: 'Complete Video KYC',
      badgeText: 'Action Required',
    },
  },
};

export const NON_OWNER_MODAL_CONTENT = {
  pending: {
    firstThirtyDays: {
      imageSrc: NeutralRekycBanner,
      heading: 'Notify the account owner to update KYC',
      description:
        'As per RBI guidelines, the account owner must verify the KYC periodically to validate the information shared during onboarding and update it if required. Please ensure all the necessary tasks are completed on time to avoid future disruptions. It will take 5-10 mins for them to complete the process. ',
      hideCta: true,
      badgeText: 'Action Required',
    },
    thirtyToFifteenDays: {
      imageSrc: NoticeRekycBanner,
      heading: (date: string) =>
        `Notify the account owner to update KYC by ${date} and keep receiving settlements`,
      description:
        'As per RBI guidelines, the account owner must verify the KYC periodically to validate the information shared during onboarding and update it if required. Please ensure all the necessary tasks are completed on time to avoid future disruptions. It will take 5-10 mins for them to complete the process.',
      hideCta: true,
      dynamicHeading: true,
    },
    foh: {
      imageSrc: DangerRekycBanner,
      heading: (date: string) =>
        `Notify the account owner to update KYC by ${date} to keep receiving payments and to enable settlements`,
      description:
        'As per RBI guidelines, the account owner must verify the KYC periodically to validate the information shared during onboarding and update it if required. Please ensure all the necessary tasks are completed on time to avoid future disruptions. It will take 5-10 mins for them to complete the process.',
      hideCta: true,
      dynamicHeading: true,
    },
    liveDisabled: {
      imageSrc: InactiveRekycBanner,
      heading: 'Notify the account owner to update KYC and reactivate the account',
      description:
        'As per RBI guidelines, the account owner must verify the KYC periodically to validate the information shared during onboarding and update it if required. Please ensure all the necessary tasks are completed on time to avoid future disruptions. It will take 5-10 mins for them to complete the process.',
      hideCta: true,
      badgeText: 'Action Required',
    },
  },
  inProgress: {
    firstThirtyDays: {
      imageSrc: NeutralRekycBanner,
      heading: 'Your KYC update is in progress!',
      description: (date: string) =>
        `The account owner is currently updating the KYC details. Please ensure that they update all the details by ${date}.`,
      hideCta: true,
      badgeText: 'In Progress',
      dynamicDescription: true,
    },
    thirtyToFifteenDays: {
      imageSrc: NoticeRekycBanner,
      heading: (date: string) =>
        `Notify Account Owner to complete the KYC by ${date} to keep settlements active`,
      description: (date: string) =>
        `The account owner is currently updating the KYC details. Please ensure that they update all the details by ${date}.`,
      hideCta: true,
      dynamicHeading: true,
      dynamicDescription: true,
    },
    foh: {
      imageSrc: DangerRekycBanner,
      heading:
        'Notify Account Owner to complete the KYC to keep receiving payments and to enable settlements',
      description: (date: string) =>
        `The account owner is currently updating the KYC details. Please ensure that they update all the details by ${date}.`,
      hideCta: true,
      dynamicHeading: false,
      dynamicDescription: true,
    },
    liveDisabled: {
      imageSrc: InactiveRekycBanner,
      heading: 'Notify Account Owner to complete the KYC and to re-activate the account',
      description: (date: string) =>
        `The account owner is currently updating the KYC details. Please ensure that they update all the details by ${date}.`,
      hideCta: true,
      dynamicDescription: true,
      badgeText: 'Action Required',
    },
  },
  eddPending: {
    firstThirtyDays: {
      imageSrc: NeutralRekycBanner,
      heading: 'Your KYC update is in progress',
      description:
        'The account owner is currently updating the KYC details. No action is needed from you at this time. For any queries, please contact the account owner.',
      hideCta: true,
      badgeText: 'In Progress',
    },
    thirtyToFifteenDays: {
      imageSrc: NoticeRekycBanner,
      heading: (date: string) =>
        `Notify account owner to complete KYC update by ${date} to keep settlements active`,
      description: (date: string) =>
        `The account owner is updating the KYC details. Ensure it is done by ${date} to keep settlements active. For any queries, please contact the account owner.`,
      hideCta: true,
      dynamicHeading: true,
      dynamicDescription: true,
    },
    foh: {
      imageSrc: DangerRekycBanner,
      heading: (date: string) =>
        `Notify Account Owner to complete KYC update by ${date} and keep customer payments active`,
      description: (date: string) =>
        `The account owner is updating the KYC details. Ensure it is done by ${date} to keep payments and settlements active. For any queries, contact the account owner.`,
      hideCta: true,
      dynamicHeading: true,
      dynamicDescription: true,
    },
    liveDisabled: {
      imageSrc: InactiveRekycBanner,
      heading: 'Notify account owner to complete KYC update to activate your disabled account',
      description:
        'As per RBI guidelines, your account is disabled for live payments due to not updating KYC within time period. Notify account owner to update KYC and resume activity.',
      hideCta: true,
      badgeText: 'Action Required',
    },
  },
  needsClarification: {
    firstThirtyDays: {
      imageSrc: NeutralRekycBanner,
      heading: 'Notify Account Owner to Resolve KYC issues',
      description:
        'Some of the documents/details submitted by the account owner are incorrect/incomplete. Please ensure that they are able to resolve them to complete the KYC process.',
      hideCta: true,
      badgeText: 'In Progress',
    },
    thirtyToFifteenDays: {
      imageSrc: NoticeRekycBanner,
      heading: (date: string) =>
        `Notify account owner to resolve KYC issues by ${date} and keep settlements active`,
      description:
        'Some of the documents/details submitted by the account owner are incorrect/incomplete. Please ensure that they are able to resolve them to complete the KYC process.',
      hideCta: true,
      dynamicHeading: true,
    },
    foh: {
      imageSrc: DangerRekycBanner,
      heading: (date: string) =>
        `Notify account owner to resolve KYC issues by ${date} and to keep receiving payments and to enable settlements`,
      description:
        'Some of the documents/details submitted by the account owner are incorrect/incomplete. Please ensure that they are able to resolve them to complete the KYC process.',
      hideCta: true,
      dynamicHeading: true,
    },
    liveDisabled: {
      imageSrc: InactiveRekycBanner,
      heading: 'Notify account owner to resolve KYC issues and to re-activate the account',
      description:
        'Some of the documents/details submitted by the account owner are incorrect/incomplete. Please ensure that they are able to resolve them to complete the KYC process.',
      hideCta: true,
      badgeText: 'Action Required',
    },
  },
  rejected: {
    firstThirtyDays: {
      imageSrc: RejectedRekycBanner,
      heading: "Your KYC wasn't approved",
      description:
        'Based on the KYC, your business does not meet our terms of service. Please reach out to our support team for further assistance.',
      hideCta: true,
      badgeText: 'Action Required',
    },
    thirtyToFifteenDays: {
      imageSrc: RejectedRekycBanner,
      heading: (date: string) =>
        `Your KYC wasn't approved - Contact us and resolve before ${date} to keep the settlements active`,
      description:
        'Based on the KYC, your business does not meet our terms of service. Please reach out to our support team for further assistance.',
      hideCta: true,
      dynamicHeading: true,
    },
    foh: {
      imageSrc: RejectedRekycBanner,
      heading: (date: string) =>
        `Your KYC couldn't be approved - Contact us and resolve before ${date} to keep receiving payments`,
      description:
        'Based on the KYC, your business does not meet our terms of service. Please reach out to our support team for further assistance.',
      hideCta: true,
      dynamicHeading: true,
    },
    liveDisabled: {
      imageSrc: RejectedRekycBanner,
      heading: 'Your account has been disabled for customer payments',
      description:
        'Based on the KYC, your business does not meet our terms of service. Please reach out to our support team for further assistance.',
      hideCta: true,
      badgeText: 'Action Required',
    },
  },
};

export const STATUSES_TO_SHOW_MODAL = [
  'pending',
  'inProgress',
  'eddPending',
  'needsClarification',
  'rejected',
];

export const MOBILE_BREAKPOINTS: Readonly<Array<keyof Theme['breakpoints']>> = [
  'base',
  'xs',
  's',
  'm',
];

const MODULAR_ONBOARDING_URL = `${window.EASY_ONBOARDING_URL}/rekyc`;
const EASY_ONBOARDING_URL = `${window.EASY_ONBOARDING_URL}/onboarding/needs-clarification?isRekyc=true`;

export const MERCHANT_ACTION = {
  pending: MODULAR_ONBOARDING_URL,
  inProgress: MODULAR_ONBOARDING_URL,
  eddPending: MODULAR_ONBOARDING_URL,
  needsClarification: EASY_ONBOARDING_URL,
  rejected: 'contactSupport'
}

export const SELF_SERVE_REKYC_HIDE_MODAL = 'self_serve_rekyc_hide_modal';

export const REKYC_DOCUMENTATION_URL = 'https://razorpay.com/docs/payments/dashboard/re-kyc/';
