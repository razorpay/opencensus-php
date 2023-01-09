import { analyticsTrack } from 'common/services/tracking/segment';

const SHOULD_SEND_TO_LUMBERJACK = true;
const EVENT_CATEGORY = 'Dashboard - Developer Console';
const API_SCREEN = 'developer-console-api-page';
const WEBHOOK_SCREEN = 'developer-console-webhook-page';

export const trackDeveloperConsoleOpened = () => {
  analyticsTrack({
    objectName: 'developer console tab',
    actionName: 'click',
    screen: 'developer-console-page',
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

// Api tab events
export const trackApiTabOpened = () => {
  analyticsTrack({
    objectName: 'api tab',
    actionName: 'open',
    screen: API_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackDurationChange = () => {
  analyticsTrack({
    objectName: 'api tab duration',
    actionName: 'change',
    screen: API_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackDateChange = () => {
  analyticsTrack({
    objectName: 'api tab date',
    actionName: 'change',
    screen: API_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackEndpointChange = () => {
  analyticsTrack({
    objectName: 'api endpoint',
    actionName: 'change',
    screen: API_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackApiChartDurationChanged = (durationLabel) => {
  analyticsTrack({
    objectName: 'api tab chart duration',
    actionName: 'change',
    screen: API_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
      label: durationLabel,
    },
  });
};

export const trackApiChartStatusChanged = () => {
  analyticsTrack({
    objectName: 'api tab chart status code',
    actionName: 'change',
    screen: API_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackApiLogsSearchKeywordChanged = () => {
  analyticsTrack({
    objectName: 'api tab logs search keyword',
    actionName: 'change',
    screen: API_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackApiLogsSearchHttpStatusChanged = () => {
  analyticsTrack({
    objectName: 'api tab logs search http status',
    actionName: 'change',
    screen: API_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackApiLogsSearched = () => {
  analyticsTrack({
    objectName: 'api tab search logs',
    actionName: 'click',
    screen: API_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackApiLogDetailsOpened = () => {
  analyticsTrack({
    objectName: 'api log details',
    actionName: 'open',
    screen: API_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackApiLogRequestResponseDetailsOpened = (menuOpened) => {
  analyticsTrack({
    objectName: `api log details ${menuOpened?.toLowerCase()}`,
    actionName: 'open',
    screen: API_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

// Webhook tab events
export const trackWebhookTabOpened = () => {
  analyticsTrack({
    objectName: 'webhook tab',
    actionName: 'open',
    screen: WEBHOOK_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackWebhookTabDurationChange = () => {
  analyticsTrack({
    objectName: 'webhook tab duration',
    actionName: 'change',
    screen: WEBHOOK_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackWebhookTabDateChange = () => {
  analyticsTrack({
    objectName: 'webhook tab date',
    actionName: 'change',
    screen: WEBHOOK_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackWebhookTabEventTypeChange = () => {
  analyticsTrack({
    objectName: 'webhook event type',
    actionName: 'change',
    screen: WEBHOOK_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackWebhookChartDurationChanged = (durationLabel) => {
  analyticsTrack({
    objectName: 'webhook tab chart duration',
    actionName: 'change',
    screen: WEBHOOK_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
      label: durationLabel,
    },
  });
};

export const trackWebhookChartStatusChanged = () => {
  analyticsTrack({
    objectName: 'webhook tab chart status code',
    actionName: 'change',
    screen: WEBHOOK_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackWebhookLogsSearchKeywordChanged = () => {
  analyticsTrack({
    objectName: 'webhook tab logs search keyword',
    actionName: 'change',
    screen: WEBHOOK_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackWebhookLogsSearchHttpStatusChanged = () => {
  analyticsTrack({
    objectName: 'webhook tab logs search http status',
    actionName: 'change',
    screen: WEBHOOK_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackWebhookLogsSearched = () => {
  analyticsTrack({
    objectName: 'webhook tab search logs',
    actionName: 'click',
    screen: WEBHOOK_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackWebhookLogDetailsOpened = () => {
  analyticsTrack({
    objectName: 'webhook log details',
    actionName: 'open',
    screen: WEBHOOK_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};

export const trackWebhookLogRequestResponseDetailsOpened = (menuOpened) => {
  analyticsTrack({
    objectName: `webhook log details ${menuOpened?.toLowerCase()}`,
    actionName: 'open',
    screen: WEBHOOK_SCREEN,
    user: window.rzp_user,
    isLJReqiuired: SHOULD_SEND_TO_LUMBERJACK,
    properties: {
      category: EVENT_CATEGORY,
    },
  });
};
