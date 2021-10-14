import { useEffect, useReducer } from 'react';

import Pager from 'common/ui/Pager';
import Spinner from 'common/ui/Spinner';
import { getTimeinTwelveHourFormat } from './utilities';
import { fetchHistoricalDowntimes } from './service';
import { BANKS, PSPs, CARD_ISSUERS } from './constants';

const initialState = {
  historicalDowntimes: [],
  skip: 0,
  count: 5,
  length: 0,
  isHistoricalLoading: false,
};

const reducer = (state, action) => {
  switch (action.type) {
    case 'SETDOWNTIMES':
      return {
        ...state,
        historicalDowntimes: action.payload.historicalDowntimes,
        isHistoricalLoading: action.payload.isHistoricalLoading,
        length: action.payload.length,
      };
    case 'ERRINFETCHING':
      return {
        ...state,
        isHistoricalLoading: false,
      };
    case 'PAGINATE':
      return {
        ...state,
        skip: action.payload.skip,
        count: action.payload.count,
        isHistoricalLoading: true,
      };
    default:
      return {
        ...state,
      };
  }
};

const HistoricalDowntimes = (props) => {
  const [state, dispatch] = useReducer(reducer, initialState);

  const showHistoricalDowntime = (historicalDowntime) => {
    const beginDateObj = new Date(historicalDowntime?.begin * 1000);
    const endDateObj = new Date(historicalDowntime?.end * 1000);
    const beginTime = getTimeinTwelveHourFormat(beginDateObj);
    const endTime = getTimeinTwelveHourFormat(endDateObj);
    return (
      <div key={historicalDowntime.id} class="historical-downtime">
        <div class="historical-downtime-heading">
          {`${Object.keys(historicalDowntime.instrument)[0]} - ${
            historicalDowntime.mapToName
              ? historicalDowntime.providerName
              : historicalDowntime.instrument[Object.keys(historicalDowntime.instrument)[0]]
          }`}
        </div>
        <div class="historical-downtime-details">
          {`${beginDateObj.getDate()}
          /${
            String(Number(beginDateObj.getMonth()) + 1).length === 1
              ? `0${Number(beginDateObj.getMonth()) + 1}`
              : Number(beginDateObj.getMonth()) + 1
          }/${beginDateObj.getFullYear()}  |  ${beginTime}  to  ${endTime}   |`}
          {historicalDowntime.severity === 'low' ? (
            <img
              src={`${window.cdnBaseUrl}/static/assets/downtimes/yellow-status-tiny.svg`}
              class="historical-downtime-icon"
            />
          ) : historicalDowntime.severity === 'medium' ? (
            <img
              src={`${window.cdnBaseUrl}/static/assets/downtimes/orange-status-tiny.svg`}
              class="historical-downtime-icon"
            />
          ) : (
            <img
              src={`${window.cdnBaseUrl}/static/assets/downtimes/red-status-tiny.svg`}
              class="historical-downtime-icon"
            />
          )}

          <span class="historical-downtime-severity">{`${historicalDowntime.severity} Severity`}</span>
        </div>
      </div>
    );
  };

  const setHistoricalDowntimes = (paymentMethod) => {
    const todayDateObj = new Date();
    let todaymonth = Number(todayDateObj.getMonth()) + 1;
    if (String(todaymonth).length === 1) {
      todaymonth = `0${todaymonth}`;
    }
    let todayDate = todayDateObj.getDate();
    if (String(todayDate).length === 1) {
      todayDate = `0${todayDate}`;
    }
    const endDate = `${todayDateObj.getFullYear()}-${todaymonth}-${todayDate}`;
    const priorDateObj = new Date();
    priorDateObj.setDate(priorDateObj.getDate() - 30);

    let priorMonth = Number(priorDateObj.getMonth()) + 1;
    if (String(priorMonth).length === 1) {
      priorMonth = `0${priorMonth}`;
    }
    let priorDate = priorDateObj.getDate();
    if (String(priorDate).length === 1) {
      priorDate = `0${priorDate}`;
    }
    const startDate = `${priorDateObj.getFullYear()}-${priorMonth}-${priorDate}`;

    const method =
      paymentMethod === 'Cards' ? 'card' : paymentMethod === 'UPI' ? 'upi' : 'netbanking';

    fetchHistoricalDowntimes(method, String(state.skip), String(state.count), startDate, endDate)
      .then((response) => {
        // console.log('Historical downtimes - card', response);
        let data = [];
        if (Array.isArray(response)) {
          data = response;
        } else {
          data = response.data;
        }
        const dataLength = data.length;

        data.forEach((historicalDowntime) => {
          switch (paymentMethod) {
            case 'Cards':
              {
                const instrument = Object.keys(historicalDowntime.instrument)[0];
                if (instrument === 'issuer') {
                  // Mapping to card issuer name
                  historicalDowntime.mapToName = true;
                  const issuer = CARD_ISSUERS.find(
                    (element) => element.code === historicalDowntime?.instrument?.issuer,
                  );
                  if (issuer != undefined) {
                    const issuerName = issuer.issuerName;
                    historicalDowntime.providerName = issuerName;
                  }
                }
              }
              break;
            case 'UPI':
              {
                const instrument = Object.keys(historicalDowntime.instrument)[0];
                if (instrument === 'psp') {
                  // Mapping to psp name
                  historicalDowntime.mapToName = true;
                  const psp = PSPs.find(
                    (element) => element.code === historicalDowntime?.instrument?.psp,
                  );
                  if (psp != undefined) {
                    const pspName = psp.pspName;
                    historicalDowntime.providerName = pspName;
                  }
                }
              }
              break;
            case 'Net Banking':
              {
                // Mapping to bank name
                historicalDowntime.mapToName = true;
                const bank = BANKS.find(
                  (element) => element.code === historicalDowntime?.instrument?.bank,
                );
                if (bank != undefined) {
                  const bankName = bank.bankName;
                  historicalDowntime.providerName = bankName;
                }
              }
              break;
            default:
              break;
          }
        });
        dispatch({
          type: 'SETDOWNTIMES',
          payload: {
            historicalDowntimes: data,
            isL2Loading: false,
            length: dataLength,
            isHistoricalLoading: false,
          },
        });
      })
      .catch(() => {
        // console.log('Some error in fetching historical', err);
        dispatch({ type: 'ERRINFETCHING' });
      });
  };

  const paginate = (params) => {
    const skip = params.skip;
    const count = params.count;
    dispatch({ type: 'PAGINATE', payload: { skip, count } });
    setHistoricalDowntimes(props.paymentMethod);
  };

  useEffect(() => {
    setHistoricalDowntimes(props.paymentMethod);
  }, [props.paymentMethod]);

  return (
    state.historicalDowntimes.length > 0 && (
      <>
        <div class="upcoming-title">Past 30 Days Incidents</div>
        {state.isHistoricalLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <>
            {state.historicalDowntimes?.map((historicalDowntime) =>
              showHistoricalDowntime(historicalDowntime),
            )}
            <div class="status-pager">
              <Pager
                buttonClass="status-pager-button"
                textClass="status-pager-text"
                count={state.count}
                skip={state.skip}
                length={state.length}
                onClick={paginate}
              />
            </div>
          </>
        )}
      </>
    )
  );
};

export default HistoricalDowntimes;
