import { IconComponent, IconColors, HeadingProps } from '@razorpay/blade/components';

export type RekycDetailsApiData = {
  id: string,
  merchant_id: string,
  status: 'approved' | 'rejected' | 'in_progress' | 'pending' | 'edd_pending' | 'needs_clarification' | 'under_review',
  kyc_type: 'rekyc',
  deadline: number,
  created_at: number,
  updated_at: number
}

export type BadgeComponentProps = {
  daysFromDeadline: number,
  chipType: FeedbackColors,
  badgeText?: string
}

export type SelfServeRekycNotificationProps = {
  merchantId: string,
  role: string,
}

export type FeedbackColors = 'information' | 'negative' | 'neutral' | 'notice' | 'positive';

export type RekycBannerInfo = {
  heading: string | ((date: string) => string),
  description: string | ((date: string) => string),
  IconComponent: IconComponent,
  showChip: boolean,
  iconColor: IconColors,
  headingColor: HeadingProps['color'],
  iconBackgroundColor: string,
  ctaText: string,
  dynamicDate: string,
  chipType: FeedbackColors,
  hideActionables: boolean,
  hideTimeline: boolean,
  dynamicDescription: boolean,
  showImageInBanner: boolean,
  imageSrc: string,
  hideIcon: boolean
}

export type RekycStepInfo = {
  heading: string,
  IconComponent: IconComponent,
  iconColor: IconColors,
  iconBackgroundColor: string,
  headingColor: HeadingProps['color'],
  textContent: string,
  textColor: HeadingProps['color'],
  completed: boolean,
  currentStep: boolean,
  borderColor: string,
}

export type RekycStepProps = {
  isMobile: boolean,
  stepInfo: RekycStepInfo,
  showDivider: boolean
}

export type RekycBannerProps = {
  bannerDetails: RekycBannerInfo,
  deadlineDate: string,
  daysFromDeadline: number,
  stepsInfo: RekycStepInfo | null,
  rekycUrl: string,
  rekycStatus: RekycDetailsApiData['status'] | undefined,
}

export type RekycModalInfo = {
  imageSrc: string,
  heading: string | ((date: string) => string),
  description: string | ((date: string) => string),
  ctaText: string,
  badgeText: string
  hideCta: boolean,
  dynamicHeading: boolean,
  dynamicDescription: boolean
}

export type RekycModalWrapperProps = {
  modalInfo: RekycModalInfo,
  deadlineDate: string,
  daysFromDeadline: number,
  rekycUrl: string,
  rekycStatus: RekycDetailsApiData['status'] | undefined,
}

export type RekycModalProps = Omit<RekycModalWrapperProps, 'rekycStatus' | 'userRole'> & {
  isOpen: boolean,
  isMobile: boolean,
  onDismiss: () => void
  onCtaClick: (rekycUrl: string) => void
}