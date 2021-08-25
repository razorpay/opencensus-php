import React, { createContext } from 'react';
import trackLJ from 'common/services/tracking/LJ';

const TrackingContext = createContext({});

function useTracking() {
  const context = React.useContext(TrackingContext);
  if (!context) {
    throw new Error(`useTracking must be used within a TrackingProvider`);
  }
  return context;
}

interface EventData {
  eventName: string;
  eventAction: string;
  eventContext: unknown;
}

const sendEvents = ({ eventName, eventAction, eventContext }: EventData) => {
  switch (eventName) {
    case 'L1_SUBMITTED':
      trackLJ({
        eventGroup: 'onbr',
        eventAction,
        eventContext,
        eventName,
      });
      break;
    default:
      // eslint-disable-next-line no-console
      console.log('No matching event');
  }
};

interface Props {
  children: React.ReactNode;
}

const TrackingProvider: React.FC<Props> = (props) => {
  return <TrackingContext.Provider value={sendEvents} {...props} />;
};

export { TrackingProvider, useTracking };
