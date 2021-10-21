import { analyticsTrack } from 'common/utils/analytics';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';

const TRACK_EVENTS = 'TRACK_EVENTS';

export const trackEvents = (props) => {
  return (_, getState) => {
    const { session } = getState() || {};

    analyticsTrack({
      ...props,
      properties: {
        ...getCommonSegmentProperties(session.user, { addUserProperties: true }),
        ...(props?.properties || {}),
      },
    });

    return {
      type: TRACK_EVENTS,
      payload: '',
    };
  };
};

const initialState = {};

export default function track(state = initialState, action) {
  switch (action.type) {
    case `${TRACK_EVENTS}`:
      return state;

    default:
      return state;
  }
}
