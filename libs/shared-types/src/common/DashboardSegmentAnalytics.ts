// Generic type for event properties (strictly typed with only valid types)
interface AnalyticsProperties {
  [key: string]: string | number | boolean | null | object;
}

// Type for traits used in user identification and group tracking
interface UserTraits {
  [key: string]: string | number | boolean | null | object;
}

// Define type for SegmentAnalytics using strict type definitions
export interface DashboardSegmentAnalytics {
  _loadOptions: unknown;
  trackSubmit?: <T extends AnalyticsProperties>(
    form: HTMLFormElement,
    event: string,
    properties?: T,
  ) => void;
  trackClick?: <T extends AnalyticsProperties>(
    element: HTMLElement,
    event: string,
    properties?: T,
  ) => void;
  trackLink?: <T extends AnalyticsProperties>(
    elements: HTMLElement[],
    event: string,
    properties?: T,
  ) => void;
  trackForm?: <T extends AnalyticsProperties>(
    forms: HTMLFormElement[],
    event: string,
    properties?: T,
  ) => void;
  pageview?: (url?: string) => void;
  identify?: <T extends UserTraits>(userId: string, traits?: T) => void;
  reset?: () => void;
  group?: <T extends UserTraits>(groupId: string, traits?: T) => void;
  track?: <T extends AnalyticsProperties>(
    event: string,
    properties?: T,
    config?: {
      integrations: Record<string, boolean>;
    },
  ) => void;
  ready?: (callback: () => void) => void;
  alias?: (userId: string, previousId?: string) => void;
  debug?: (enabled: boolean) => void;
  page?: <T extends AnalyticsProperties>(category?: string, name?: string, properties?: T) => void;
  once?: (event: string, callback: () => void) => void;
  off?: (event: string, callback?: () => void) => void;
  on?: (event: string, callback: () => void) => void;
  addSourceMiddleware?: (middleware: (payload: AnalyticsProperties) => void) => void;
  addIntegrationMiddleware?: (middleware: (payload: AnalyticsProperties) => void) => void;
  setAnonymousId?: (id?: string) => void;
  addDestinationMiddleware?: (middleware: (payload: AnalyticsProperties) => void) => void;
  load?: (apiKey: string, options?: Record<string, string | number | boolean>) => void;
  invoked?: boolean;
  methods?: string[];
  SNIPPET_VERSION?: string;
  factory?: (methodName: string) => void;
  [key: string]: unknown;
  push?: <T>(x: T) => void;
}
