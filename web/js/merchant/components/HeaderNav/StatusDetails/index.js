import React, { useEffect, useRef, useState, useCallback, useReducer } from 'react';
import { connect } from 'react-redux';

import Slider from 'common/ui/Slider';
import { openSlider, closeSlider } from 'merchant_common/reducers/slider';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { fetchOngoingDowntimes } from './service';
import Spinner from 'common/ui/Spinner';
import Popover, { PopoverBody } from 'common/ui/Popover';
import CardsDetails from './CardsDetails';
import UPIDetails from './UPIDetails';
import NetBankingDetails from './NetBankingDetails';
import CardsInfoDetails from './CardsInfoDetails';
import UPIInfoDetails from './UPIInfoDetails';
import NetBankingInfoDetails from './NetBankingInfoDetails';
import UpcomingMaintenance from './UpcomingMaintenance';
import HistoricalDowntimes from './HistoricalDowntimes';
import { classList } from 'common/utils/rzp-utils';
import FailedStatus from './FailedStatus';
import OverallStatus from './OverallStatus';
import { downtimeAnalyticsTrack } from './utilities';

// Initial state for status details page.
const initialState = {
  overallStatus: '',
  cardDowntimes: {},
  upiDowntimes: {},
  netBankingDowntimes: {},
  cardNetworksOperational: [],
  cardIssuersOperational: [],
  vpaOperational: [],
  pspOperational: [],
  netBankingOperational: [],
  methodsDown: [],
  time: '',
  timeObj: null,
};

const initialReduerState = {
  statusDetails: initialState,
  isLoading: false,
  errorInFetchingData: false,
};

// Action for reducer.
const SET_STATUSDETAIL_LOADING = 'SET_STATUSDETAIL_LOADING';
const SET_STATUSDETAIL = 'SET_STATUSDETAIL';
const SET_STATUSDETAIL_FAILURE = 'SET_STATUSDETAIL_FAILURE';

const reducer = (state, action) => {
  switch (action.type) {
    case SET_STATUSDETAIL_LOADING:
      return {
        ...state,
        errorInFetchingData: false,
        isLoading: true,
      };
    case SET_STATUSDETAIL:
      return {
        ...state,
        statusDetails: action.payload,
        isLoading: false,
      };
    case SET_STATUSDETAIL_FAILURE:
      return {
        ...state,
        isLoading: false,
        errorInFetchingData: true,
      };
    default:
      return state;
  }
};

function StatusDetails(props) {
  const [state, dispatch] = useReducer(reducer, initialReduerState);
  const [mode, setMode] = useState('summary');
  const [paymentMethod, setPaymentMethod] = useState('Cards');
  const [isSliderOpen, setIsSliderOpen] = useState(false);
  const [isRefreshDisable, setIsRefreshDisable] = useState(false);
  const [timeForNextAPI, setTimeForNextAPI] = useState(0);

  const intervalForTime = useRef(null);
  const intervalForDebounce = useRef(null);

  const { openSlider: sliderOpen, closeSlider: sliderClose, AppMode } = props;

  const {
    statusDetails: {
      time,
      timeObj,
      cardDowntimes,
      cardNetworksOperational,
      cardIssuersOperational,
      upiDowntimes,
      pspOperational,
      vpaOperational,
      netBankingDowntimes,
      netBankingOperational,
      overallStatus,
      methodsDown,
    },
    isLoading,
    errorInFetchingData,
  } = state;

  const setOngoingDowntimes = useCallback(async () => {
    dispatch({ type: SET_STATUSDETAIL_LOADING });

    try {
      const response = await fetchOngoingDowntimes();
      dispatch({
        type: SET_STATUSDETAIL,
        payload: { ...state.statusDetails, ...response },
      });
    } catch (err) {
      dispatch({ type: SET_STATUSDETAIL_FAILURE });
    }
  }, [state.statusDetails]);

  const checkForDebounce = useCallback(() => {
    const now = new Date();
    const diffInSec = Math.floor((now - timeObj) / 1000);
    if (diffInSec > 30) {
      setIsRefreshDisable(false);
      setTimeForNextAPI(0);
      clearInterval(intervalForDebounce.current);
    } else {
      setTimeForNextAPI(diffInSec);
    }
  }, [timeObj]);

  const refreshData = useCallback(() => {
    setOngoingDowntimes();
  }, [setOngoingDowntimes]);

  const startInterval = useCallback(() => {
    intervalForDebounce.current = setInterval(() => {
      checkForDebounce();
    }, 1000);
    intervalForTime.current = setInterval(() => refreshData(), 300000);
  }, [checkForDebounce, refreshData]);

  const endInterval = () => {
    clearInterval(intervalForTime.current);
    clearInterval(intervalForDebounce.current);
  };

  useEffect(() => {
    endInterval();
    if (isSliderOpen) {
      startInterval();
    }
  }, [isSliderOpen, startInterval]);

  const onUserRefresh = () => {
    refreshData();

    setIsRefreshDisable(true);
  };

  const switchToInfoView = async (pmtMethod) => {
    let currentDowntimes = null;

    setMode('info');
    setPaymentMethod(pmtMethod);
    await setOngoingDowntimes();

    if (pmtMethod === 'Cards') {
      currentDowntimes = cardDowntimes;
    } else if (pmtMethod === 'UPI') {
      currentDowntimes = upiDowntimes;
    } else if (pmtMethod === 'Net Banking') {
      currentDowntimes = netBankingDowntimes;
    }

    // analyticsTrack
    downtimeAnalyticsTrack({
      objectName: 'Downtime Details viewed',
      method: paymentMethod,
      currentDowntimes,
    });
  };

  const switchToSummaryView = () => {
    setMode('summary');
  };

  const hideSlider = useCallback(() => {
    setMode('summary');
    endInterval();
    sliderClose();
    setIsSliderOpen(false);
  }, [sliderClose]);

  const showSlider = () => {
    setOngoingDowntimes();
    setIsRefreshDisable(true);
    setTimeForNextAPI(0);
    sliderOpen();

    setIsSliderOpen(true);

    // analyticsTrack
    downtimeAnalyticsTrack({ objectName: 'Downtime Status page visited', method: 'Summary' });
  };

  const handleDocumentClick = useCallback(
    (event) => {
      const target = event.target;
      const sliderContent = document.querySelector('.content-wrapper.status-details');
      const sliderToggle = document.querySelector('.status-details-slide-toggle');
      const statusDetails = document.querySelector(
        '.panel.panel-default.SliderPanel.status-details--container',
      );
      if (
        (sliderContent && sliderContent.contains(target)) ||
        (sliderToggle && sliderToggle.contains(target)) ||
        (statusDetails && statusDetails.contains(target))
      ) {
        return;
      }
      hideSlider();
    },
    [hideSlider],
  );

  useEffect(() => {
    document.addEventListener('click', handleDocumentClick, true);

    return () => document.removeEventListener('click', handleDocumentClick, true);
  }, [handleDocumentClick]);

  const handleSliderToggleClick = () => {
    return isSliderOpen ? hideSlider() : showSlider();
  };

  return (
    <main className={classList('status-details', isSliderOpen && 'status-details--active')}>
      {/* Hidden the Bank Downtime from Test Mode*/}
      {AppMode === 'live' && (
        <div className="status-details-slide-toggle">
          {/* 
              For not we will only use the icon not text so commented the text variant for
              Kept the code commented for future references
             */}
          {/* {!showMobileNav ? (
              <span onClick={this.handleSliderToggleClick}>Bank Downtimesss</span>
            ) : ( */}
          <i className="i i-downtime" onClick={handleSliderToggleClick} />
          {/* )} */}
        </div>
      )}
      {isSliderOpen ? (
        <Slider>
          <ErrorBoundary resetOnProps>
            <div className="content-wrapper content-sm txn-details status-details">
              <div className="panel panel-default SliderPanel">
                <div className="panel-heading">
                  {mode === 'summary' ? (
                    <b>Payment Methods Status</b>
                  ) : (
                    <>
                      <img
                        className="icon refresh-action"
                        src={`${window.cdnBaseUrl}/static/assets/downtimes/arrow-left.svg`}
                        onClick={switchToSummaryView}
                        alt="Back button"
                      />
                      <span className="title">{paymentMethod}</span>
                      <span className="description">Last updated {time} today </span>
                      {isRefreshDisable ? (
                        <span>
                          <img
                            src={`${window.cdnBaseUrl}/static/assets/downtimes/refresh-disabled.svg`}
                            className="refresh-action"
                            alt="Refresh is disabled"
                          />
                          <Popover align="bottom" theme="light">
                            <PopoverBody>
                              <div>Try again in {30 - timeForNextAPI} seconds</div>
                            </PopoverBody>
                          </Popover>
                        </span>
                      ) : (
                        <img
                          src={`${window.cdnBaseUrl}/static/assets/downtimes/refresh-cw.svg`}
                          className="icon refresh-action"
                          onClick={() => {
                            if (!isRefreshDisable) {
                              onUserRefresh();
                            }
                          }}
                          alt="Refresh"
                        />
                      )}
                    </>
                  )}
                </div>
                <div className="SliderPanel__Body">
                  <div className="panel-body ">
                    {mode === 'info' ? (
                      <div>
                        {isLoading ? (
                          <div className="page-spinner-container">
                            <Spinner />
                          </div>
                        ) : (
                          <div className="status-method">
                            <section className="summary">
                              {paymentMethod === 'Cards' ? (
                                <CardsInfoDetails
                                  cardDowntimes={cardDowntimes}
                                  cardNetworksOperational={cardNetworksOperational}
                                  cardIssuersOperational={cardIssuersOperational}
                                />
                              ) : paymentMethod === 'UPI' ? (
                                <UPIInfoDetails
                                  upiDowntimes={upiDowntimes}
                                  pspOperational={pspOperational}
                                  vpaOperational={vpaOperational}
                                />
                              ) : (
                                <NetBankingInfoDetails
                                  netBankingDowntimes={netBankingDowntimes}
                                  netBankingOperational={netBankingOperational}
                                />
                              )}
                              <p className="message">
                                We only detect downtime fluctuations for the instruments which have
                                sufficient payment volume
                              </p>
                            </section>

                            <UpcomingMaintenance paymentMethod={paymentMethod} />
                            <HistoricalDowntimes paymentMethod={paymentMethod} />
                          </div>
                        )}
                      </div>
                    ) : (
                      <div>
                        {isLoading ? (
                          <div className="page-spinner-container">
                            <Spinner />
                          </div>
                        ) : errorInFetchingData ? (
                          <FailedStatus onUserRefresh={onUserRefresh} />
                        ) : (
                          <>
                            <div className="main-info">
                              <OverallStatus status={overallStatus} downMethods={methodsDown} />
                              <div className="date-and-time">
                                Last updated {time} today{' '}
                                {isRefreshDisable ? (
                                  <span>
                                    <img
                                      src={`${window.cdnBaseUrl}/static/assets/downtimes/refresh-disabled.svg`}
                                      className="refresh-action"
                                      alt="Can't refresh"
                                    />
                                    <Popover align="bottom" theme="light">
                                      <PopoverBody>
                                        <div>Try again in {30 - timeForNextAPI} seconds</div>
                                      </PopoverBody>
                                    </Popover>
                                  </span>
                                ) : (
                                  <img
                                    src={`${window.cdnBaseUrl}/static/assets/downtimes/refresh-cw.svg`}
                                    className="icon refresh-action"
                                    onClick={() => {
                                      if (!isRefreshDisable) {
                                        onUserRefresh();
                                      }
                                    }}
                                    alt="Refresh"
                                  />
                                )}
                              </div>
                            </div>
                            <div className="details-section">
                              <CardsDetails
                                cardDowntimes={cardDowntimes}
                                switchToInfoView={switchToInfoView}
                              />
                              <UPIDetails
                                upiDowntimes={upiDowntimes}
                                switchToInfoView={switchToInfoView}
                              />
                              <NetBankingDetails
                                netBankingDowntimes={netBankingDowntimes}
                                switchToInfoView={switchToInfoView}
                              />
                            </div>
                            <div className="status-disclaimer">
                              The page publishes the most up-to-the-minute information on various
                              payment options. Check here anytime to get the current
                              status/information on Razorpay’s partner or issuing banks.
                            </div>
                          </>
                        )}
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </ErrorBoundary>
        </Slider>
      ) : null}
    </main>
  );
}

export default connect(null, { openSlider, closeSlider })(StatusDetails);
