import React from 'react';
import { connect } from 'react-redux';

import Slider from 'common/ui/Slider';
import { openSlider } from 'merchant_common/reducers/slider';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import {
  fetchOngoingDowntimes,
  fetchScheduledDowntimes,
  fetchHistoricalDowntimes,
} from './service';
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

import moment from 'moment';

class StatusDetails extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      sliderOpen: false,
      time: '',
      timeObj: '',
      mode: 'summary',
      paymentMethod: 'Cards',
      cardDowntimes: {},
      upiDowntimes: {},
      netBankingDowntimes: {},
      overallStatus: '',
      methodsDown: [],
      cardNetworksOperational: [],
      cardIssuersOperational: [],
      vpaOperational: [],
      pspOperational: [],
      netBankingOperational: [],
      scheduledDowntimes: {},
      isL1Loading: true,
      isL2Loading: true,
      skip: 0,
      count: 5,
      timeForNextAPI: 90,
      disableRefresh: true,
      errorInFetchingData: false,
    };
  }

  checkForDebounce = () => {
    const now = new Date();
    const diffInSec = Math.ceil((now - this.state.timeObj) / 1000);
    if (diffInSec > 30) {
      this.setState({ disableRefresh: false, timeForNextAPI: diffInSec });
    } else {
      this.setState({ disableRefresh: true, timeForNextAPI: diffInSec });
    }
  };

  refreshData = () => {
    if (this.state.mode === 'summary') {
      this.setOngoingDowntimes();
    } else {
      this.setOngoingDowntimes();
      this.setScheduledAndHistoricalDowntimes(this.state.paymentMethod);
    }
  };

  onUserRefresh = () => {
    this.setState({ disableRefresh: true });
    this.refreshData();
    clearInterval(this.intervalForTime);
    this.intervalForTime = setInterval(() => this.refreshData(), 300000);
  };

  switchToInfoView = (paymentMethod) => {
    this.setState({ mode: 'info', paymentMethod });
    this.setScheduledAndHistoricalDowntimes(paymentMethod);
  };

  switchToSummaryView = () => {
    this.setState({ mode: 'summary' });
  };

  componentWillMount() {
    const time = moment().format('hh:mm');
    this.setState({
      time,
      timeObj: moment(),
    });
    this.setOngoingDowntimes();
  }

  setOngoingDowntimes = () => {
    this.setState({ isL1Loading: true });
    fetchOngoingDowntimes()
      .then((response) => {
        this.setState(response);
        this.setState({ errorInFetchingData: false });
      })
      .catch(() => {
        this.setState({ isL1Loading: false, errorInFetchingData: true });
      });
  };

  setScheduledAndHistoricalDowntimes = (paymentMethod) => {
    this.setState({ isL2Loading: true });
    fetchScheduledDowntimes()
      .then((scheduledDowntimes) => {
        this.setState({ scheduledDowntimes });
      })
      .catch((err) => {
        throw new Error(err);
      });

    this.setHistoricalDowntimes(paymentMethod);
  };

  setHistoricalDowntimes = async (paymentMethod) => {
    try {
      const params = [this.state.skip, this.state.count, paymentMethod];
      const data = await fetchHistoricalDowntimes(...params);
      this.setState({
        isL2Loading: false,
      });
      return data;
    } catch (err) {
      throw new Error(err);
    }
  };

  componentDidMount() {
    this.intervalForDebounce = setInterval(() => {
      this.checkForDebounce();
    }, 1000);
    this.intervalForTime = setInterval(() => this.refreshData(), 300000);
  }

  componentWillUnmount() {
    clearInterval(this.intervalForTime);
    clearInterval(this.intervalForDebounce);
  }

  hideSlider = () => {
    this.setState({ mode: 'summary', sliderOpen: false });
  };

  showSlider = () => {
    this.props.openSlider();
    this.setState({ sliderOpen: true });
  };

  handleSliderToggleClick = () => {
    return this.state.sliderOpen ? this.hideSlider() : this.showSlider();
  };

  render() {
    const {
      sliderOpen,
      time,
      mode,
      disableRefresh,
      paymentMethod,
      isL2Loading,
      timeForNextAPI,
      cardDowntimes,
      cardNetworksOperational,
      cardIssuersOperational,
      upiDowntimes,
      pspOperational,
      vpaOperational,
      netBankingDowntimes,
      netBankingOperational,
      scheduledDowntimes,
      isL1Loading,
      errorInFetchingData,
      overallStatus,
      methodsDown,
    } = this.state;

    return (
      <main className={classList('status-details', sliderOpen && 'status-details--active')}>
        <div className="status-details-slide-toggle">
          <span onClick={this.handleSliderToggleClick}>Bank Downtimes</span>
        </div>
        {sliderOpen ? (
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
                          onClick={this.switchToSummaryView}
                          alt="Back button"
                        />
                        <span className="title">{paymentMethod}</span>
                        <span className="description">Last updated {time} today </span>
                        {disableRefresh ? (
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
                              if (!disableRefresh) {
                                this.onUserRefresh();
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
                          {isL2Loading ? (
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
                                  We only detect downtime fluctuations for the instruments which
                                  have sufficient payment volume
                                </p>
                              </section>

                              <UpcomingMaintenance
                                paymentMethod={paymentMethod}
                                scheduledDowntimes={scheduledDowntimes}
                              />
                              <HistoricalDowntimes paymentMethod={paymentMethod} />
                            </div>
                          )}
                        </div>
                      ) : (
                        <div>
                          {isL1Loading ? (
                            <div className="page-spinner-container">
                              <Spinner />
                            </div>
                          ) : errorInFetchingData ? (
                            <FailedStatus onUserRefresh={this.onUserRefresh} />
                          ) : (
                            <>
                              <div className="main-info">
                                <OverallStatus status={overallStatus} downMethods={methodsDown} />
                                <div className="date-and-time">
                                  Last updated {time} today{' '}
                                  {disableRefresh ? (
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
                                        if (!disableRefresh) {
                                          this.onUserRefresh();
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
                                  switchToInfoView={this.switchToInfoView}
                                />
                                <UPIDetails
                                  upiDowntimes={upiDowntimes}
                                  switchToInfoView={this.switchToInfoView}
                                />
                                <NetBankingDetails
                                  netBankingDowntimes={netBankingDowntimes}
                                  switchToInfoView={this.switchToInfoView}
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
}

export default connect(null, { openSlider })(StatusDetails);
