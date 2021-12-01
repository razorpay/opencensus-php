import Spinner from 'common/ui/Spinner';
import React, { useCallback, useEffect, useReducer } from 'react';
import ScheduledDowntime from './ScheduledDowntime';
import { fetchScheduledDowntimes } from './service';

const initialState = {
  scheduledDowntimes: {},
  isLoading: false,
  errorInFetchingData: false,
};

// Action for reducer.
const SET_SCHEDULEDOWNTIME_LOADING = 'SET_SCHEDULEDOWNTIME_LOADING';
const SET_SCHEDULEDOWNTIME = 'SET_SCHEDULEDOWNTIME';
const SET_SCHEDULEDOWNTIME_FAILURE = 'SET_SCHEDULEDOWNTIME_FAILURE';

const reducer = (state, action) => {
  switch (action.type) {
    case SET_SCHEDULEDOWNTIME_LOADING:
      return {
        ...state,
        errorInFetchingData: false,
        isLoading: true,
      };
    case SET_SCHEDULEDOWNTIME:
      return {
        ...state,
        scheduledDowntimes: action.payload,
        isLoading: false,
      };
    case SET_SCHEDULEDOWNTIME_FAILURE:
      return {
        ...state,
        isLoading: false,
        errorInFetchingData: true,
      };
    default:
      return state;
  }
};

const UpcomingMaintenance = (props) => {
  const [state, dispatch] = useReducer(reducer, initialState);

  const setScheduledDowntimes = useCallback(() => {
    dispatch({ type: SET_SCHEDULEDOWNTIME_LOADING });
    fetchScheduledDowntimes()
      .then((response) => {
        dispatch({
          type: SET_SCHEDULEDOWNTIME,
          payload: response,
        });
      })
      .catch(() => {
        dispatch({ type: SET_SCHEDULEDOWNTIME_FAILURE });
      });
  }, []);

  useEffect(() => {
    setScheduledDowntimes();
  }, [setScheduledDowntimes]);

  const { paymentMethod } = props;
  const { scheduledDowntimes, isLoading } = state;

  return (
    <section>
      <p className="section-title">
        <img
          src={`${window.cdnBaseUrl}/static/assets/downtimes/maintenance.svg`}
          className="maintenance-icon"
          alt="Maintenance"
        />{' '}
        Upcoming Maintenance
      </p>
      {isLoading ? (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      ) : paymentMethod === 'Cards' ? (
        'card' in scheduledDowntimes ? (
          <div>
            {scheduledDowntimes?.card.map((scheduledDowntime) => (
              <ScheduledDowntime key={scheduledDowntime.id} scheduledDowntime={scheduledDowntime} />
            ))}
          </div>
        ) : (
          <p className="no-maintenance">No Upcoming Maintenance</p>
        )
      ) : paymentMethod === 'UPI' ? (
        'upi' in scheduledDowntimes ? (
          <div>
            {scheduledDowntimes?.upi.map((scheduledDowntime) => (
              <ScheduledDowntime key={scheduledDowntime.id} scheduledDowntime={scheduledDowntime} />
            ))}
          </div>
        ) : (
          <p className="no-maintenance">No Upcoming Maintenance</p>
        )
      ) : 'netbanking' in scheduledDowntimes ? (
        scheduledDowntimes?.netbanking.map((scheduledDowntime) => (
          <ScheduledDowntime key={scheduledDowntime.id} scheduledDowntime={scheduledDowntime} />
        ))
      ) : (
        <p className="no-maintenance">No Upcoming Maintenance</p>
      )}
    </section>
  );
};

export default UpcomingMaintenance;
