import { titleCase } from './rzp-utils';

const sendToLumberjack = ({ eventName, properties = {} }) => {
  const body = {
    mode: 'live',
    key: window.LUMBERJACK_API_KEY,
    events: [
      {
        event_type: 'pg-dashboard',
        event: eventName,
        event_version: 'v1',
        timestamp: new Date().getTime(),
        properties: {
          ...properties,
        },
      },
    ],
  };

  fetch(window.LUMBERJACK_API_URL, {
    method: 'post',
    body: JSON.stringify(body),
    headers: {
      'Content-Type': 'application/json',
    },
  });
};

export const analyticsTrack = ({
  objectName,
  actionName,
  screen,
  properties = {},
  toLumberjack = false,
}) => {
  if (!objectName) {
    throw new Error('[analytics]: objectName cannot be empty');
  }

  if (!actionName) {
    throw new Error('[analytics]: actionName cannot be empty');
  }

  if (!screen) {
    throw new Error('[analytics]: screen cannot be empty');
  }

  if (/_/g.test(objectName)) {
    throw new Error(`[analytics]: expected objectName: ${objectName} to not have '_'`);
  }

  if (/_/g.test(actionName)) {
    throw new Error(`[analytics]: expected actionName: ${actionName} to not have '_'`);
  }

  const eventTimestamp = new Date().toISOString();
  const eventName = titleCase(`${objectName} ${actionName}`);
  if (window.analytics) {
    window.analytics.track(eventName, {
      ...properties,
      screen,
      eventTimestamp,
    });
  }

  if (toLumberjack) {
    sendToLumberjack({
      eventName,
      properties: {
        ...properties,
        screen,
        eventTimestamp,
      },
    });
  }
};
