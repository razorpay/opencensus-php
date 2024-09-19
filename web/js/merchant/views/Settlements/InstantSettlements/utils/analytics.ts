import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

interface AnalyticsProps {
  objectName: string;
  actionName: string;
  screen: string;
  properties: Record<string, string | number | null | undefined>;
}

const COMMON_EVENTS = {
  FIELD: 'ODS Field',
  BUTTON: 'ODS Button',
  SCREEN: 'ODS Screen',
} as const;

const ACTIONS = {
  CLICKED: 'clicked',
  RENDER: 'render',
  EDITED: 'edited',
};

const trackHelper = (data: AnalyticsProps) => {
  try {
    analyticsTrackWithUserInfo({
      ...data,
      flowName: 'capital-ods-settle-now',
      pathname: location.pathname,
    });
  } catch (_) {
    // ignore
  }
};

export const trackButton = ({
  screen,
  properties = {},
  name,
}: {
  screen: string;
  name: string;
  properties?: AnalyticsProps['properties'];
}): void => {
  trackHelper({
    objectName: COMMON_EVENTS.BUTTON,
    actionName: ACTIONS.CLICKED,
    screen,
    properties: {
      name,
      ...properties,
    },
  });
};

export const trackRender = ({
  screen,
  context,
  properties = {},
}: {
  screen: string;
  /** Eg:- for flows inside a screen. Reg: onDemandV2/WithdrawalScreen */
  context?: string;
  properties?: AnalyticsProps['properties'];
}): void => {
  trackHelper({
    objectName: COMMON_EVENTS.SCREEN,
    actionName: ACTIONS.RENDER,
    screen,
    properties: {
      ...properties,
      context: context || 'default',
    },
  });
};

export const trackField = ({
  screen,
  name,
  value,
}: {
  screen: string;
  name: string;
  value?: string;
  properties?: AnalyticsProps['properties'];
}): void => {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const properties: AnalyticsProps['properties'] = {
    field_name: name,
    value,
  };

  trackHelper({
    objectName: COMMON_EVENTS.FIELD,
    actionName: ACTIONS.EDITED,
    screen,
    properties,
  });
};
