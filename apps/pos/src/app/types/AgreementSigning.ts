import { IconComponent } from '@razorpay/blade/components';

export type FeedbackColors = 'information' | 'negative' | 'neutral' | 'notice' | 'positive';
export type AgreementModeType = 'online' | 'offline' | '';
export type TimelineStepStatus = 'not_started' | 'in_progress' | 'completed';
export type AgreementStatus = 'in_progress' | 'completed' | '';
export type AgreementStepStatus = 'pending' | 'completed';

export interface Badge {
  text: string;
  mood: FeedbackColors;
  icon: IconComponent;
}
export interface TimelineItem {
  heading: string;
  date?: string;
  badge?: Badge;
  stepIcon?: IconComponent;
  showCopyLink?: boolean;
  status?: TimelineStepStatus;
}

export enum MODULAR_AGREEMENT_FIELDS {
  AGREEMENT_STEP = 'agreement_step',
  AGREEMENT_COMPONENT = 'agreement_component',
  AGREEMENT_TYPE_FIELD = 'agreement_type_field',
  AGREEMENT_STATUS_FIELD = 'agreement_status_field',
  AGREEMENT_SENT_AT_FIELD = 'agreement_sent_at_field',
  OFFLINE_AGENT_AGREEMENT_DOC = 'agreement_documents_field',
  RETRY_SEND_AGREEMENT_FIELD = 'retry_send_agreement_field',
  AGREEMENT_CONSENTED_AT_FIELD = 'agreement_consented_at_field',
  CONSENT_STEP = 'consent_step',
  CONSENT_COMPONENT = 'consent_component',
  SIGNING_CONFIRMATION = 'Signing Confirmation',
  GENERATE_LINK_FOR_MERCHANT = 'Generate Agreement Link for the Merchant',
  MODULAR_CALLBACK = 'modular_callback',
}
