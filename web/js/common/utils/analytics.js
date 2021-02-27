import { titleCase } from './rzp-utils';

export const analyticsTrack = ({ objectName, actionName, screen, properties = {} }) => {
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

  analytics.track(eventName, {
    ...properties,
    screen,
    eventTimestamp,
  });
};
