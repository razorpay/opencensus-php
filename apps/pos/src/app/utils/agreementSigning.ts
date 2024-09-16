import { ArrowRightIcon, CheckCircleIcon, InfoIcon } from '@razorpay/blade/components';
import moment from 'moment';
import { MerchantModularOnboardingDetailsSuccessResponse } from 'apps/pos/src/app/types/modular';
import {
  getComponentFromStep,
  getFieldFromComponent,
  getProgressFromModularStep,
} from 'apps/pos/src/app/utils/modularConfig';
import {
  isArrayOfDocumentsUpload,
  isStringValue,
} from 'apps/pos/src/app/utils/modularTypeResolvers';
import {
  TimelineItem,
  Badge,
  AgreementModeType,
  TimelineStepStatus,
  AgreementStatus,
  AgreementStepStatus,
  MODULAR_AGREEMENT_FIELDS,
} from 'apps/pos/src/app/types/AgreementSigning';

export const NOT_STARTED = 'not_started';
export const IN_PROGRESS = 'in_progress';
export const COMPLETED = 'completed';
export const PENDING = 'pending';
export const EXECUTED = 'executed';
export const OFFLINE = 'offline';
export const ONLINE = 'online';

export const formatUnixTimestamp = (unixTimestamp: number | string) => {
  if (!unixTimestamp) return '';
  const unixTimestampNumber = Number(unixTimestamp);
  const formattedDate = moment.unix(unixTimestampNumber).format('ddd, Do MMM’YY | h:mma');
  return formattedDate;
};

export const getAgreementSatusValue = (
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null,
): AgreementStatus => {
  if (!modularConfig) return '';
  const agreementStatusField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_AGREEMENT_FIELDS.AGREEMENT_STEP,
    component: MODULAR_AGREEMENT_FIELDS.AGREEMENT_COMPONENT,
    fieldName: MODULAR_AGREEMENT_FIELDS.AGREEMENT_STATUS_FIELD,
  });
  const agreementStatus = isStringValue(agreementStatusField)
    ? agreementStatusField?.stringValue
    : '';
  return agreementStatus as AgreementStatus;
};

export const getAgreementSentAtValue = (
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse,
) => {
  const agreementSentAtField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_AGREEMENT_FIELDS.AGREEMENT_STEP,
    component: MODULAR_AGREEMENT_FIELDS.AGREEMENT_COMPONENT,
    fieldName: MODULAR_AGREEMENT_FIELDS.AGREEMENT_SENT_AT_FIELD,
  });
  const agreementSentAt = isStringValue(agreementSentAtField)
    ? agreementSentAtField?.stringValue
    : '';
  return agreementSentAt;
};

export const getInitialTimeline = (
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse,
): TimelineItem[] => {
  if (!modularConfig) return [];
  const agreementStatus = getAgreementSatusValue(modularConfig);
  const agreementSentAt = getAgreementSentAtValue(modularConfig);
  if (!agreementStatus) {
    return [
      {
        heading: MODULAR_AGREEMENT_FIELDS.GENERATE_LINK_FOR_MERCHANT,
        status: NOT_STARTED,
      },
    ];
  }
  if (agreementStatus === IN_PROGRESS) {
    return [
      {
        heading: MODULAR_AGREEMENT_FIELDS.GENERATE_LINK_FOR_MERCHANT,
        date: `${formatUnixTimestamp(agreementSentAt)}`,
        status: COMPLETED,
        badge: {
          mood: 'positive',
          icon: CheckCircleIcon,
          text: 'Agreement Sent',
        },
        showCopyLink: true,
      },
      {
        heading: MODULAR_AGREEMENT_FIELDS.SIGNING_CONFIRMATION,
        badge: { text: 'Action Pending', mood: 'notice', icon: InfoIcon },
        status: IN_PROGRESS,
      },
    ];
  }

  if (agreementStatus === COMPLETED) {
    const agreementConsentedAtField = getFieldFromComponent({
      modularConfig,
      step: MODULAR_AGREEMENT_FIELDS.CONSENT_STEP,
      component: MODULAR_AGREEMENT_FIELDS.CONSENT_COMPONENT,
      fieldName: MODULAR_AGREEMENT_FIELDS.AGREEMENT_CONSENTED_AT_FIELD,
    });
    const agreementConsentedAt = isStringValue(agreementConsentedAtField)
      ? agreementConsentedAtField?.stringValue
      : '';
    return [
      {
        heading: MODULAR_AGREEMENT_FIELDS.GENERATE_LINK_FOR_MERCHANT,
        date: `${formatUnixTimestamp(agreementSentAt)}`,
        status: COMPLETED,
        badge: {
          mood: 'positive',
          icon: CheckCircleIcon,
          text: 'Agreement Sent',
        },
      },
      {
        heading: MODULAR_AGREEMENT_FIELDS.SIGNING_CONFIRMATION,
        date: `${formatUnixTimestamp(agreementConsentedAt)}`,
        badge: { text: 'Successful', mood: 'positive', icon: CheckCircleIcon },
        status: COMPLETED,
      },
    ];
  }
  return [];
};

interface StepToUpdate {
  stepName: string;
  stepBadge: Badge;
  stepTime?: string;
}
interface UpdateTimelineProps {
  timeline: TimelineItem[];
  stepToUpdate?: StepToUpdate;
  newStep?: TimelineItem[];
  updatedStatus: TimelineStepStatus;
}

export const getUpdatedTimeline = ({
  timeline = [],
  stepToUpdate,
  newStep,
  updatedStatus,
}: UpdateTimelineProps) => {
  if (!timeline?.length) return;
  const data = timeline.map((item) => {
    if (item.heading === stepToUpdate?.stepName) {
      return {
        ...item,
        badge: stepToUpdate.stepBadge,
        date: stepToUpdate?.stepTime || item.date,
        status: updatedStatus,
        showCopyLink: true,
      };
    }
    return item;
  });
  if (newStep) return [...data, ...newStep];
  return data;
};

export const getAgreementDocsField = (
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse,
) => {
  const agreementDocumentsField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_AGREEMENT_FIELDS.AGREEMENT_STEP,
    component: MODULAR_AGREEMENT_FIELDS.AGREEMENT_COMPONENT,
    fieldName: MODULAR_AGREEMENT_FIELDS.OFFLINE_AGENT_AGREEMENT_DOC,
  });
  return {
    posAgreementDocuments: isArrayOfDocumentsUpload(agreementDocumentsField)
      ? agreementDocumentsField.arrayOfDocumentsUploadValue
      : [],
  };
};

export const getAgreementMode = (
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null,
): AgreementModeType => {
  if (!modularConfig) return '';
  const agreementTypeField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_AGREEMENT_FIELDS.AGREEMENT_STEP,
    component: MODULAR_AGREEMENT_FIELDS.AGREEMENT_COMPONENT,
    fieldName: MODULAR_AGREEMENT_FIELDS.AGREEMENT_TYPE_FIELD,
  });
  const agreementMode = isStringValue(agreementTypeField)
    ? (agreementTypeField?.stringValue as AgreementModeType)
    : '';
  return agreementMode ? agreementMode : ONLINE;
};

export const getAgreementTypeField = (
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse,
) => {
  const agreementTypeField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_AGREEMENT_FIELDS.AGREEMENT_STEP,
    component: MODULAR_AGREEMENT_FIELDS.AGREEMENT_COMPONENT,
    fieldName: MODULAR_AGREEMENT_FIELDS.AGREEMENT_TYPE_FIELD,
  });
  return agreementTypeField;
};

interface GetSubmitBtnProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse;
  mode: AgreementModeType;
}

export const getSubmitBtnText = ({ modularConfig, mode }: GetSubmitBtnProps) => {
  if (mode === OFFLINE) return 'Submit Merchant Details';
  if (getAgreementSatusValue(modularConfig) === IN_PROGRESS) return 'Re-send Link';
  if (getAgreementSatusValue(modularConfig) === COMPLETED) return 'Submit Merchant Details';
  return 'Generate Link';
};

export const getSubmitIcon = ({
  mode,
  status,
}: {
  mode: AgreementModeType;
  status: AgreementStatus;
}) => {
  if (mode === OFFLINE) return;
  if (mode === ONLINE && (status === IN_PROGRESS || status === COMPLETED)) return;
  return ArrowRightIcon;
};

interface GetAgreementStepStatusProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
}
export const getAgreementStepStatus = ({
  modularConfig,
}: GetAgreementStepStatusProps): AgreementStepStatus => {
  if (!modularConfig) return PENDING;
  const isAgreementStepCompleted =
    getProgressFromModularStep({ modularConfig, step: MODULAR_AGREEMENT_FIELDS.AGREEMENT_STEP }) ===
    COMPLETED;
  if (isAgreementStepCompleted) return COMPLETED;
  return PENDING;
};

export const getAgreementComponentStatus = (
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null,
): boolean => {
  if (!modularConfig) return false;
  const agreementComponent = getComponentFromStep({
    modularConfig,
    step: MODULAR_AGREEMENT_FIELDS.AGREEMENT_STEP,
    component: MODULAR_AGREEMENT_FIELDS.AGREEMENT_COMPONENT,
  });
  return agreementComponent?.status === EXECUTED;
};
