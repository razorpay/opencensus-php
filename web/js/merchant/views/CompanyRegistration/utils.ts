import {
  useTheme,
  ClipboardIcon,
  FileTextIcon,
  BuildingIcon,
  CalendarIcon,
  ConfettiIcon,
} from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';

import Ashoka from 'assets/rize/rize_incorporation/ashoka.svg';
import {
  WORKFLOW_MILESTONES,
  WORKFLOW_COMPONENTS,
  WORKFLOW_FIELDS,
  POST_SALESFORCE_STEPS_STATUS,
  BANNER_DATA,
  RIZE_JOURNEY,
  USER_JOURNEY_STEPS,
  USER_JOURNEY,
  DOCUMENT_FORM_STATUS
} from './constant';
import type { BannerDataT, StatusStepsType, RizeJourneyType } from './types';

export const parseBannerData = ({
  screen = RIZE_JOURNEY.RESUME_SCREEN,
}: {
  screen: RizeJourneyType;
}): BannerDataT => {
  const { main, midSection, button } = BANNER_DATA[screen];

  return {
    firstLine: main.firstLine,
    secondLineSubText: main.secondLine.subText,
    highlightedText: main.secondLine.highlightedText,
    isIconContent: midSection?.isIconContent ?? false,
    text: midSection?.text || null,
    isButtonRequire: button?.isButtonRequire ?? false,
    buttonText: button?.buttonText || '',
  };
};

export const styleBasedOnDevice = (isMobile) => {
  const commonStyle = {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    backgroundColor: 'surface.background.primary.intense',
  };
  if (isMobile) {
    return {
      ...commonStyle,
      padding: 'spacing.7',
    };
  }
  return {
    ...commonStyle,
    borderRadius: 'large',
    backgroundImage: `url(${Ashoka})`,
    backgroundRepeat: 'no-repeat',
    backgroundSize: 'contain',
    backgroundOrigin: 'border-box',
    backgroundPosition: 'right',
    marginX: 'spacing.7',
    padding: 'spacing.8',
  };
};

interface UseScreen {
  isDesktop: boolean;
  isMobile: boolean;
  isTablet: boolean;
}
/**
 * Support for Mobile, Tablet & Desktop based on breakpoint supported by Blade
 */
export const useScreen = (): UseScreen => {
  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });

  return {
    isDesktop: matchedBreakpoint === 'l' || matchedBreakpoint === 'xl',
    isMobile:
      matchedBreakpoint === 's' || matchedBreakpoint === 'xs' || matchedBreakpoint === 'base',
    isTablet: matchedBreakpoint === 'm',
  };
};

export const parseWorkflowResponse = (workflowData) => {
  const { onboarding_state: { steps: [firstStep] = [] } = {} } = workflowData || {};

  if (USER_JOURNEY_STEPS.includes(firstStep))
    return {
      screen: RIZE_JOURNEY.RESUME_SCREEN,
      user_journey: firstStep,
    };
  else if (firstStep === USER_JOURNEY.post_payment_step)
    return {
      screen: RIZE_JOURNEY.STATUS_SCREEN,
    };
  else if (firstStep === USER_JOURNEY.post_incorporation_step)
    return {
      screen: RIZE_JOURNEY.ACCOUNT_SCREEN,
    };
  return {
    screen: RIZE_JOURNEY.INITIAL_SCREEN,
  };
};

export const getSteps = (workflowData) => {
  /* 
  There are 3 fields -   Submitted will be ongoing and Approved will Completed, Rest Will be Next
    -document_form_approved       :  not_submitted/submitted/approved
    -name_approval_status         : submitted/approved
    -incorporation_form_approved :  submitted/approved 
    -incorporation_status         : ""/completed
  */
  //get the document summary component
  const documentSummaryComponent = workflowData?.milestones
    ?.find((milestone) => milestone.name === WORKFLOW_MILESTONES.L2_MILESTONE)
    ?.steps?.[1].components?.find((item) => item.name === WORKFLOW_COMPONENTS.DOCUMENT_SUMMARY);

  // get the incorporation summary component of last step of l2 milestone
  const incorporationSummaryComponent = workflowData?.milestones
    ?.find((milestone) => milestone.name === WORKFLOW_MILESTONES.L2_MILESTONE)
    ?.steps[1].components?.find((item) => item.name === WORKFLOW_COMPONENTS.INCORPORATION_SUMMARY);
  // get the document link from the onboarding summary component

  const fields = incorporationSummaryComponent?.fields!;

  // User is incorporated when this field is approved
  const isIncorporated =
    fields?.find((item) => item.name === WORKFLOW_FIELDS.INCORPORATION_STATUS)?.stringValue ===
    'completed';
  // Initial State for steps
  const steps: StatusStepsType[] = [
    {
      icon: ClipboardIcon,
      title: 'Application Received',
      status: POST_SALESFORCE_STEPS_STATUS.COMPLETED,
    },
    {
      icon: FileTextIcon,
      title: 'Documents Approval',
      status: POST_SALESFORCE_STEPS_STATUS.NEXT,
    },
    {
      icon: BuildingIcon,
      title: 'Company Name Approval',
      status: POST_SALESFORCE_STEPS_STATUS.NEXT,
    },
    {
      icon: CalendarIcon,
      title: 'Application Submission to MCA',
      status: POST_SALESFORCE_STEPS_STATUS.NEXT,
    },
    {
      icon: ConfettiIcon,
      title: 'Incorporation Complete',
      status: POST_SALESFORCE_STEPS_STATUS.FINAL,
    },
  ];

  if (isIncorporated) {
    return steps.map((item) => {
      return {
        ...item,
        status: POST_SALESFORCE_STEPS_STATUS.COMPLETED,
      };
    });
  }

  // this field is in onboarding summary component
  const documentFormApprovedField = documentSummaryComponent?.fields?.find(
    (item) => item.name === WORKFLOW_FIELDS.DOCUMENT_FORM_APPROVED,
  );
  // these fields are in incorporation summar component
  const nameApprovalStatus = fields?.find(
    (item) => item.name === WORKFLOW_FIELDS.NAME_APPROVAL_STATUS,
  );
  const incorporationFormApproved = fields?.find(
    (item) => item.name === WORKFLOW_FIELDS.INCORPORATION_FORM_APPROVED,
  );

  // switch case for Document Approval Step
  switch (documentFormApprovedField?.value) {
    case DOCUMENT_FORM_STATUS.not_submitted:
      steps[1].status = POST_SALESFORCE_STEPS_STATUS.NEXT; //Documents Approval
      break;
    case DOCUMENT_FORM_STATUS.submitted:
      steps[1].status = POST_SALESFORCE_STEPS_STATUS.ONGOING;
      break;
    case DOCUMENT_FORM_STATUS.approved:
      steps[1].status = POST_SALESFORCE_STEPS_STATUS.COMPLETED;
      break;
  }

  // updating status of Company Name Approval
  if (nameApprovalStatus?.value === DOCUMENT_FORM_STATUS.submitted) {
    steps[2].status = POST_SALESFORCE_STEPS_STATUS.ONGOING; //Company Name Approval
  }
  if (nameApprovalStatus?.value === DOCUMENT_FORM_STATUS.approved) {
    steps[2].status = POST_SALESFORCE_STEPS_STATUS.COMPLETED; //Company Name Approval
  }

  // updating status for last 2 steps;
  if (incorporationFormApproved?.value === DOCUMENT_FORM_STATUS.submitted) {
    steps[3].status = POST_SALESFORCE_STEPS_STATUS.ONGOING; //Application Submission to MCA
  }
  if (incorporationFormApproved?.value === DOCUMENT_FORM_STATUS.approved) {
    steps[3].status = POST_SALESFORCE_STEPS_STATUS.COMPLETED; //Application Submission to MCA
    steps[4].status = POST_SALESFORCE_STEPS_STATUS.ONGOING; //Incorporation Complete
  }
  return steps;
};

export const getFirstUppercaseChar = (name) => {
  if (!name || typeof name !== 'string') return 'U'; // Handle empty, null, or non-string values
  return name.charAt(0).toUpperCase();
};
