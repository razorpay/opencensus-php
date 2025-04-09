// RazorAnalytics SDK type definition file for reference - https://github.com/razorpay/frontend-universe/blob/master/packages/universe-utils/src/analytics/v2/index.d.ts

export type RazorAnalyticsPlugins = {
  lumberjack: (config: LumberjackConfig) => RazorAnalyticsPluginInstance;
  segment: () => RazorAnalyticsPluginInstance;
};

export type RazorAnalytics = {
  init: (config: InitConfig) => void;
  setIdentity: (userId: string, traits: Record<string, any>) => void;
  removeIdentity: () => void;
  debug: (value: boolean) => void;
  enableTracking: () => void;
  disableTracking: () => void;
  onError?: (err: Error) => void;

  setEventProperties: (props: Record<string, any>) => void;
  setExperiments: (props: Record<string, string>) => void;
  setElementProperties: (element: HTMLElement, props: Record<string, any>) => void;

  track: Track;
  trackMilestoneEvent: TrackMilestoneEvent;
  trackStepEvent: TrackStepEvent;
  trackUserInteraction: TrackUserInteraction;
  trackErrorResponse: TrackErrorResponse;

  createTracker: (
    category: string,
  ) => (subCategory: string) => (eventName: string) => (attributes?: Attributes) => void;
};

type EventData = {
  category: string;
  subCategory: string;
  eventName: string;
  attributes?: Record<string, any>;
};

type Attributes = Record<string, any>;

type Track = (eventData: EventData) => void;

type TrackMilestoneEvent = (eventData: {
  eventName: string;
  attributes?: Attributes;
}) => void;

type TrackStepEvent = (eventData: { eventName: string; attributes?: Attributes }) => void;

type TrackUserInteraction = (eventData: {
  subCategory: string;
  eventName: string;
  attributes?: Attributes;
}) => void;

type TrackErrorResponse = (eventData: {
  eventName: string;
  attributes?: Attributes;
}) => void;

type InitConfig = {
  mode: 'debug' | 'live';
  coreVersion?: string;
  plugins?: Array<RazorAnalyticsPluginInstance>;
  clientId?: string;
  session?: {
    timeout?: number;
  };
  pageConfig?: {
    captureSearchParams?: boolean;
    captureHashParams?: boolean;
  };
  utm?: {
    captureExtraParams?: Array<string>;
  };
  sendEventToWebViewHost?: boolean;
  sendMetaEvent?: boolean;
  eventProperties?: Record<string, any>;
  identity?: { userId: string } & Record<string, any>;
  experiments?: Record<string, string>;
  autoCapture?: AutoCaptureConfig;
};

type AutoCaptureConfig = {
  enabled?: boolean;
  customComponentAttributeName?: string;

  buttonClick?: {
    enabled?: boolean;
    captureTextContent?: boolean;
  };
  linkClick?: {
    enabled?: boolean;
    captureTextContent?: boolean;
    captureSearchParams?: boolean;
    captureHashParams?: boolean;
  };
  formSubmit?: {
    enabled?: boolean;
    captureTextContent?: boolean;
  };
  pageView?: {
    enabled?: boolean;
    pageReadSuccessTimeout?: number;
  };
  elementView?: {
    enabled?: boolean;
    viewThreshold?: number;
    viewTimeout?: number;
  };
  inputChange?: {
    enabled?: boolean;
    captureInputValue?: boolean;
  };
  inputError?: {
    enabled?: boolean;
  };
  tab?: {
    enabled?: boolean;
  };
  scroll?: {
    enabled?: boolean;
  };
  performance?: {
    enabled?: boolean;
  };
};

type RazorAnalyticsPluginInstance = {
  init: (analyticsInstance: RazorAnalytics) => void;
  setIdentity: () => void;
  removeIdentity: () => void;

  debug: () => void;
  setEventProperties: () => void;
  setExperiments: () => void;

  track: (eventData: EventData) => void;
};

type LumberjackConfig =
  | {
    environment: 'production' | 'staging';
  }
  | {
    transportConfig: {
      key: string;
      appName: string;
      url: string;
    };
  };