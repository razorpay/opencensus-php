import { useEffect, useReducer, useCallback } from 'react';

import Pager from 'common/ui/Pager';
import Spinner from 'common/ui/Spinner';
import moment from 'moment';

import { fetchHistoricalDowntimes } from './service';

const initialState = {
  historicalDowntimes: [],
  skip: '0',
  count: '5',
  length: 0,
  isHistoricalLoading: false,
};

const reducer = (state, action) => {
  switch (action.type) {
    case 'SETDOWNTIMES':
      return {
        ...state,
        historicalDowntimes: action.payload?.historicalDowntimes,
        isHistoricalLoading: action.payload?.isHistoricalLoading,
        length: action.payload?.length,
      };
    case 'ERRINFETCHING':
      return {
        ...state,
        isHistoricalLoading: false,
      };
    case 'PAGINATE':
      return {
        ...state,
        skip: action.payload?.skip?.toString(),
        count: action.payload?.count?.toString(),
        isHistoricalLoading: true,
      };
    default:
      return {
        ...state,
      };
  }
};

const StatusIcon = ({ severity }) => {
  let icon = '';
  switch (severity) {
    case 'low':
      icon = 'yellow-status-tiny.svg';
      break;
    case 'medium':
      icon = 'orange-status-tiny.svg';
      break;
    case 'high':
      icon = 'red-status-tiny.svg';
      break;
    default:
      icon = '';
  }
  return (
    <img
      src={`${window.cdnBaseUrl}/static/assets/downtimes/${icon}`}
      alt={`${severity} severity`}
      className="icon"
    />
  );
};

const ShowHistoricalDowntime = ({ historicalDowntime }) => {
  const { id, end, instrument, providerName, severity } = historicalDowntime;
  const begin = moment(historicalDowntime.begin * 1000);
  const beginDate = begin.format('DD/MM/YYYY');
  const beginTime = begin.format('hh:mm a');
  const endTime = moment(end * 1000).format('hh:mm a');
  const getTitle = () => {
    let title = '';
    for (const property in instrument) {
      if (instrument.hasOwnProperty(property)) {
        title = `${property} - ${
          ['issuer', 'bank'].includes(property) ? providerName : instrument[property]
        }`;
      }
    }
    return title;
  };

  return (
    <div key={id} className="historical-downtime">
      <div className="heading">{getTitle()}</div>
      <div className="details">
        {beginDate} <div className="divider">|</div> {`${beginTime} to ${endTime}`}
        <div className="divider">|</div>
        <StatusIcon severity={severity} />
        <span className="severity">{`${severity} Severity`}</span>
      </div>
    </div>
  );
};

const HistoricalDowntimes = (props) => {
  const [state, dispatch] = useReducer(reducer, initialState);
  const { skip, count, length, historicalDowntimes, isHistoricalLoading } = state;
  const { paymentMethod } = props;

  const setDownTimes = useCallback(async () => {
    const params = [skip, count, paymentMethod];
    try {
      const downTimes = await fetchHistoricalDowntimes(...params);
      dispatch({
        type: 'SETDOWNTIMES',
        payload: {
          historicalDowntimes: downTimes,
          isL2Loading: false,
          length: downTimes.length,
          isHistoricalLoading: false,
        },
      });
    } catch (err) {
      dispatch({ type: 'ERRINFETCHING' });
    }
  }, [paymentMethod, skip, count]);

  const onPaginate = (params) => {
    dispatch({ type: 'PAGINATE', payload: { skip: params.skip, count: params.count } });
    setDownTimes();
  };

  useEffect(() => {
    setDownTimes();
  }, [paymentMethod, setDownTimes]);

  return (
    historicalDowntimes.length > 0 && (
      <>
        <div className="upcoming-title">Past 30 Days Incidents</div>
        {isHistoricalLoading ? (
          <div className="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <>
            {state.historicalDowntimes?.map((historicalDowntime, idx) => (
              <ShowHistoricalDowntime historicalDowntime={historicalDowntime} key={idx} />
            ))}
            <div className="status-pager">
              <Pager count={count} skip={skip} length={length} onClick={onPaginate} />
            </div>
          </>
        )}
      </>
    )
  );
};

export default HistoricalDowntimes;
