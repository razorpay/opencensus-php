import React from 'react';
import {
  StatusTrackerPropsT,
  StatusTrackerStepsT,
} from 'merchant/components/StatusTracker/statusTracker.types';

import { STATUS_TRACKER_STATUS } from 'merchant/components/StatusTracker/constants';

import SbmBankLogoImage from 'assets/sbmBankLogo.svg';
import XCCSTRightIllustration from 'assets/status-tracker/x-corporate-card/xccSTRightIllustration.svg';
import {
  ApplicationStates,
  ApplicationStepTitles,
  closedApplicationSteps,
  completeApplicationSteps,
  inProgressApplicationSteps,
  rejectedApplicationSteps,
} from './data';
import { trackSegmentEvent } from 'merchant/components/StatusTracker/StatusTrackerEvents';
import { getURLQueryParams } from 'common/utils/rzp-utils';

type ApplicationData = {
  applicationStatus: ApplicationStates;
  current: ApplicationStates;
};

const getApplicationSteps = (
  type: 'inProgress' | 'closed' | 'rejected' | 'complete' = 'inProgress',
): StatusTrackerStepsT[] => {
  switch (type) {
    case 'inProgress':
      return inProgressApplicationSteps;

    case 'closed':
      return closedApplicationSteps;

    case 'complete':
      return completeApplicationSteps;

    case 'rejected':
      return rejectedApplicationSteps;

    default:
      return [];
  }
};

const triggerEvents = (objectName: string, currentStatus: string, actionName: string): void => {
  const utmParam = getURLQueryParams(location.search);
  const screen = `${location?.hostname}${location?.pathname}`;
  const properties = {
    location: `PG Dashboard | ${currentStatus}`,
    utm_params: {
      utm_source: utmParam?.utm_source,
      utm_medium: utmParam?.utm_medium,
      utm_campaign: utmParam?.utm_campaign,
    },
  };
  trackSegmentEvent({ objectName, actionName, screen, properties });
};

const getXCCStatusTrackerProps = (applicationData: ApplicationData): StatusTrackerPropsT | null => {
  const currentApplicationState = applicationData.current;
  let applicationSteps: StatusTrackerStepsT[] = [];

  let currentStepTitle: ApplicationStepTitles | null = null;

  if (currentApplicationState === ApplicationStates.Closed) {
    applicationSteps = getApplicationSteps('closed');
  } else if (
    ([
      ApplicationStates.RzpApproved,
      ApplicationStates.StateCompleted,
    ] as ApplicationStates[]).includes(currentApplicationState)
  )
    applicationSteps = getApplicationSteps('complete');
  else if (currentApplicationState === ApplicationStates.StateRejected)
    applicationSteps = getApplicationSteps('rejected');
  else {
    applicationSteps = getApplicationSteps('inProgress');

    if (
      ([
        ApplicationStates.BusinessDetailsPending,
        ApplicationStates.PersonalDetailsPending,
        ApplicationStates.CreditPullFailed,
        ApplicationStates.CreditPullPending,
      ] as ApplicationStates[]).includes(currentApplicationState)
    )
      currentStepTitle = ApplicationStepTitles.CreditVerification;
    else if (
      ([
        ApplicationStates.PreverificationUploadPending,
        ApplicationStates.PreverificationInProgress,
        ApplicationStates.PreverificationFailed,
      ] as ApplicationStates[]).includes(currentApplicationState)
    )
      currentStepTitle = ApplicationStepTitles.BankStatement;
    else if (
      ([
        ApplicationStates.ScoreGenerationPending,
        ApplicationStates.CreditOfferPending,
        ApplicationStates.CreditOfferGenerated,
      ] as ApplicationStates[]).includes(currentApplicationState)
    )
      currentStepTitle = ApplicationStepTitles.OfferCuration;
    else if (
      ([ApplicationStates.OfflineDocumentCollectionPending] as ApplicationStates[]).includes(
        currentApplicationState,
      )
    )
      currentStepTitle = ApplicationStepTitles.KycPending;
    else if (
      ([
        ApplicationStates.EsignPending,
        ApplicationStates.EsignExpired,
      ] as ApplicationStates[]).includes(currentApplicationState)
    )
      currentStepTitle = ApplicationStepTitles.ESignPending;

    if (!currentStepTitle) return null;

    let currentStepStatus: STATUS_TRACKER_STATUS = STATUS_TRACKER_STATUS.DONE;

    applicationSteps = applicationSteps.map(({ title, ...otherFields }) => {
      if (title === currentStepTitle) currentStepStatus = STATUS_TRACKER_STATUS.TO_BE_PICKED;

      return {
        ...otherFields,
        title,
        status: currentStepStatus,

        ...(title === currentStepTitle && {
          status: STATUS_TRACKER_STATUS.IN_PROGRESS,
          isActive: true,
        }),

        ...(title !== currentStepTitle && {
          description: undefined,
          buttons: undefined,
          rightIllustration: undefined,
        }),
      };
    });
  }

  const statusTrackerProps: StatusTrackerPropsT = {
    title: 'RazorpayX Corporate Card',
    description: (
      <>
        Powered by <img src={SbmBankLogoImage} alt="SBM Bank" />
      </>
    ),
    steps: applicationSteps,
    onLoad: () => {
      if (applicationSteps?.length === 1) {
        if (applicationSteps[0].title === ApplicationStepTitles.ApplicationClosed) {
          triggerEvents(
            'Application Closed Modal',
            ApplicationStepTitles.ApplicationClosed,
            'Rendered',
          );
        } else if (applicationSteps[0].title === ApplicationStepTitles.ApplicationExpired) {
          triggerEvents(
            'Application Expired Modal',
            ApplicationStepTitles.ApplicationExpired,
            'Rendered',
          );
        }
      }
    },
    rightIllustration: XCCSTRightIllustration,
  };

  return statusTrackerProps;
};

export { getXCCStatusTrackerProps, triggerEvents };
