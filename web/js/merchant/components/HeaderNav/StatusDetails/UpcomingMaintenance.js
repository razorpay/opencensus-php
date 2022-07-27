import React, { useCallback, useEffect, useReducer } from 'react';
import ScheduledDowntime from './ScheduledDowntime';
import { fetchScheduledDowntimes } from './service';
import Spinner from 'common/ui/Spinner';
import { PAYMENT_METHOD_MAP } from '../StatusDetails/constants';

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
  const currentPaymentMethod = PAYMENT_METHOD_MAP[paymentMethod];

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
      ) : currentPaymentMethod in scheduledDowntimes ? (
        <div>
          {scheduledDowntimes &&
            scheduledDowntimes[currentPaymentMethod]?.map((scheduledDowntime) => (
              <ScheduledDowntime key={scheduledDowntime.id} scheduledDowntime={scheduledDowntime} />
            ))}
        </div>
      ) : (
        <p className="no-maintenance">No Upcoming Maintenance</p>
      )}
    </section>
  );
};

export default UpcomingMaintenance;
