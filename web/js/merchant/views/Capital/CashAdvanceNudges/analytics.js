import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';
import { getPillRenderDate } from './utils';

export const EVENT_TYPES = {
  RENDERED: 'Rendered',
  CLICKED: 'clicked',
  HOVERED: 'hovered',
};

export const trackPill = ({ screen, variant, action, properties = {} }) => {
  analyticsTrack({
    objectName: `'${variant}' pill`,
    actionName: action,
    screen,
    properties: {
      ...getCommonSegmentProperties(),
      ...properties,
      date_user_eligible: getPillRenderDate(),
    },
  });
};

export const trackUnlockMoreFundsBanner = ({ screen }) => {
  analyticsTrack({
    objectName: "'Unlock more funds' cross-sell banner",
    actionName: EVENT_TYPES.RENDERED,
    screen,
    properties: getCommonSegmentProperties(),
  });
};

export const trackKnowMoreCtaClick = ({ screen }) => {
  analyticsTrack({
    objectName: 'Know More CTA',
    actionName: EVENT_TYPES.CLICKED,
    screen,
    properties: getCommonSegmentProperties(),
  });
};

export const trackFewStepsLeftBanner = ({ screen, status, expiresIn }) => {
  analyticsTrack({
    objectName: "'Few steps left' banner",
    actionName: EVENT_TYPES.RENDERED,
    screen,
    properties: {
      ...getCommonSegmentProperties(),
      date_user_eligible: getPillRenderDate(),
      application_status: status,
      days_to_application_expiry: expiresIn,
    },
  });
};

export const trackContinueApplyingCtaClick = ({ screen, status, expiresIn }) => {
  analyticsTrack({
    objectName: 'Continue Applying CTA on Few Steps Left Banner',
    actionName: EVENT_TYPES.CLICKED,
    screen,
    properties: {
      ...getCommonSegmentProperties(),
      date_user_eligible: getPillRenderDate(),
      application_status: status,
      days_to_application_expiry: expiresIn,
    },
  });
};

export const trackEvaluatingOfferModal = ({ screen }) => {
  analyticsTrack({
    objectName: "'Evaluating your offer' modal",
    actionName: EVENT_TYPES.RENDERED,
    screen,
    properties: {
      ...getCommonSegmentProperties(),
      date_user_eligible: getPillRenderDate(),
    },
  });
};

export const trackEvaluatingOfferModalClose = ({ screen }) => {
  analyticsTrack({
    objectName: 'Close icon on Evaluating your offer modal',
    actionName: EVENT_TYPES.CLICKED,
    screen,
    properties: {
      ...getCommonSegmentProperties(),
      date_user_eligible: getPillRenderDate(),
    },
  });
};

export const trackDocsUnderReviewModal = ({ screen }) => {
  analyticsTrack({
    objectName: "'Documents under review' modal",
    actionName: EVENT_TYPES.RENDERED,
    screen,
    properties: {
      ...getCommonSegmentProperties(),
      date_user_eligible: getPillRenderDate(),
    },
  });
};

export const trackDocsUnderReviewModalClose = ({ screen }) => {
  analyticsTrack({
    objectName: 'Close icon on Documents review modal',
    actionName: EVENT_TYPES.CLICKED,
    screen,
    properties: {
      ...getCommonSegmentProperties(),
      date_user_eligible: getPillRenderDate(),
    },
  });
};

export const trackBackToDashboardClick = ({ screen }) => {
  analyticsTrack({
    objectName: "'Back to Dashboard' CTA",
    actionName: EVENT_TYPES.CLICKED,
    screen,
    properties: {
      ...getCommonSegmentProperties(),
      date_user_eligible: getPillRenderDate(),
    },
  });
};

export const trackRepayNowCtaClickOnHoldTootip = ({ screen, action, properties }) => {
  analyticsTrack({
    objectName: 'Repay now CTA on Funds on Hold Tooltip',
    actionName: action,
    screen,
    properties: {
      ...getCommonSegmentProperties(),
      ...properties,
    },
  });
};
