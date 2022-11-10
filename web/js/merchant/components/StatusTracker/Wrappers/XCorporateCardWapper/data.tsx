import React from 'react';
import { StatusTrackerStepsT } from 'merchant/components/StatusTracker/statusTracker.types';
import { STATUS_TRACKER_STATUS } from 'merchant/components/StatusTracker/constants';
import { triggerEvents } from 'merchant/components/StatusTracker/Wrappers/XCorporateCardWapper/xCorporateCardWrapperUtil';

enum ApplicationStates {
  BusinessDetailsPending = 'BUSINESS_DETAILS_PENDING',
  PersonalDetailsPending = 'PERSONAL_DETAILS_PENDING',
  CreditPullFailed = 'CREDIT_PULL_FAILED',
  CreditPullPending = 'CREDIT_PULL_PENDING',
  CreditOfferPending = 'CREDIT_OFFER_PENDING',
  CreditOfferGenerated = 'CREDIT_OFFER_GENERATED',
  OfflineDocumentCollectionPending = 'OFFLINE_DOCUMENT_COLLECTION_PENDING',
  Closed = 'CLOSED',
  RzpApproved = 'RZP_APPROVED',
  RzpRejected = 'RZP_REJECTED',
  StateCreated = 'STATE_CREATED',
  StateClosed = 'STATE_CLOSED',
  StateCompleted = 'STATE_COMPLETED',
  StateRejected = 'STATE_REJECTED',
  PreverificationUploadPending = 'PREVERIFICATION_UPLOAD_PENDING',
  PreverficationFailed = 'PREVERFICATION_FAILED',
  PreverificationInProgress = 'PREVERIFICATION_IN_PROGRESS',
  PreverificationFailed = 'PREVERIFICATION_FAILED',
  ScoreGenerationPending = 'SCORE_GENERATION_PENDING',
  DocumentsUnderReview = 'DOCUMENTS_UNDER_REVIEW',
  EsignPending = 'ESIGN_PENDING',
  EsignExpired = 'ESIGN_EXPIRED',
}

enum ApplicationStepTitles {
  CreditVerification = 'Credit Verification',
  BankStatement = 'Bank Statement',
  OfferCuration = 'Offer Curation',
  KycPending = 'KYC Pending',
  ESignPending = 'E-sign pending',
  ApplicationClosed = 'Application Closed',
  StartYourJourney = 'Start your journey!',
  ApplicationExpired = 'Application Expired',
}

enum ButtonLabels {
  ContinueApplication = 'Continue application',
  TrackYourApplication = 'Track your application',
  CompleteYourApplication = 'Complete your aplication',
  UseCorporateCard = 'Use Corporate Card',
  ApplyAgain = 'Apply Again',
}

const links = {
  cards: `${window.bankingServiceUrl}/cards`,
  apply: `${window.bankingServiceUrl}/cards/apply`,
  applyApplication: `${window.bankingServiceUrl}/cards/apply/application`,
} as const;

const inProgressApplicationSteps: StatusTrackerStepsT[] = [
  {
    title: ApplicationStepTitles.CreditVerification,
    description:
      'Complete your application today without affecting your credit score. Get an exclusive offer curated for your business credit needs.',
    status: STATUS_TRACKER_STATUS.TO_BE_PICKED,
    buttons: [
      {
        label: ButtonLabels.ContinueApplication,
        onClick: (): void => {
          triggerEvents(
            'Continue Application',
            ApplicationStepTitles.CreditVerification,
            'Clicked',
          );
          window.open(links.applyApplication, '_blank');
        },
        style: 'primary',
      },
    ],
  },
  {
    title: ApplicationStepTitles.BankStatement,
    description:
      'You are one step closer to get your card! Submit your current account statement today to finish your application.',
    status: STATUS_TRACKER_STATUS.TO_BE_PICKED,
    buttons: [
      {
        label: ButtonLabels.ContinueApplication,
        onClick: (): void => {
          triggerEvents('Continue Application', ApplicationStepTitles.BankStatement, 'Clicked');
          window.open(links.applyApplication, '_blank');
        },
        style: 'disabled',
      },
    ],
  },
  {
    title: ApplicationStepTitles.OfferCuration,
    description:
      'Hang on! We are curating the best offer for your business needs. Sit back and relax while we process your application.',
    status: STATUS_TRACKER_STATUS.TO_BE_PICKED,
    buttons: [
      {
        label: ButtonLabels.TrackYourApplication,
        onClick: (): void => {
          triggerEvents('Track Your Application', ApplicationStepTitles.OfferCuration, 'Clicked');
          window.open(links.applyApplication, '_blank');
        },
        style: 'disabled',
      },
    ],
  },
  {
    title: ApplicationStepTitles.KycPending,
    description:
      'Congratulations! You are almost there. Complete your KYC today to start using your zero deposit corporate card.',
    status: STATUS_TRACKER_STATUS.TO_BE_PICKED,
    buttons: [
      {
        label: ButtonLabels.CompleteYourApplication,
        onClick: (): void => {
          triggerEvents('Complete Your Application', ApplicationStepTitles.KycPending, 'Clicked');
          window.open(links.applyApplication, '_blank');
        },
        style: 'disabled',
      },
    ],
  },
  {
    title: ApplicationStepTitles.ESignPending,
    description:
      'You are one sign away! We are waiting here for you to esign one small document for us to start processing your corporate card.',
    status: STATUS_TRACKER_STATUS.TO_BE_PICKED,
    buttons: [
      {
        label: ButtonLabels.CompleteYourApplication,
        onClick: (): void => {
          triggerEvents('Complete Your Application', ApplicationStepTitles.ESignPending, 'Clicked');
          window.open(links.applyApplication, '_blank');
        },
        style: 'disabled',
      },
    ],
  },
];

const closedApplicationSteps: StatusTrackerStepsT[] = [
  {
    title: ApplicationStepTitles.ApplicationExpired,
    description:
      'Due to the delay in completing the application, we can no longer process the further steps. Please re-apply to get your RazorpayX Corporate Card.',
    status: STATUS_TRACKER_STATUS.ERROR,
    buttons: [
      {
        label: ButtonLabels.ApplyAgain,
        onClick: (): void => {
          triggerEvents('Apply Again', ApplicationStepTitles.ApplicationExpired, 'Clicked');
          window.open(links.apply, '_blank');
        },
      },
    ],
    isActive: true,
  },
];

const completeApplicationSteps: StatusTrackerStepsT[] = [
  {
    title: ApplicationStepTitles.StartYourJourney,
    description: "Your corporate card is now active, and you're ready to simplify and save!",
    status: STATUS_TRACKER_STATUS.SUCCESS,
    buttons: [
      {
        label: ButtonLabels.UseCorporateCard,
        onClick: (): void => {
          triggerEvents('Use Corporate Card', ApplicationStepTitles.StartYourJourney, 'Clicked');
          window.open(links.cards, '_blank');
        },
      },
    ],
    isActive: true,
  },
];

const rejectedApplicationSteps: StatusTrackerStepsT[] = [
  {
    title: ApplicationStepTitles.ApplicationClosed,
    description: (
      <>
        We regret to inform you that we couldn't process any credit limit for your application at
        this moment. You can go ahead with a Corporate Card without credit limit and avail the
        rewards by getting in touch with{' '}
        <b style={{ color: '#2b83ea' }}>cards.services@razorpay.com</b>
      </>
    ),
    status: STATUS_TRACKER_STATUS.ERROR,
    isActive: true,
  },
];

export {
  ApplicationStates,
  ApplicationStepTitles,
  ButtonLabels,
  links,
  inProgressApplicationSteps,
  closedApplicationSteps,
  completeApplicationSteps,
  rejectedApplicationSteps,
};
