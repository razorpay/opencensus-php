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
import { getTimeinTwelveHourFormat } from './utilities';
import { classList } from 'common/utils/rzp-utils';
import FailedStatus from './FailedStatus';
import OverallStatus from './OverallStatus';

const showWarningText = () => {
  return (
    <div class="status-details-warning">
      <span class="status-warning-asterix">{`* `}</span>We only detect downtime fluctuations for the
      instruments which have sufficient payment volume
    </div>
  );
};

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
    const now = new Date();
    const time = getTimeinTwelveHourFormat(now);
    this.setState({
      time,
      timeObj: now,
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
    if (this.state.sliderOpen) this.hideSlider();
    else this.showSlider();
  };

  render() {
    const { sliderOpen, time } = this.state;

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
                    <div className="heading-content">
                      {this.state.mode === 'summary' ? (
                        <div className="title">
                          <b>Payment Methods Status</b>
                        </div>
                      ) : (
                        <div>
                          <img
                            className="status-back"
                            src={`${window.cdnBaseUrl}/static/assets/downtimes/arrow-left.svg`}
                            onClick={this.switchToSummaryView}
                          />
                          <span className="status-heading">{this.state.paymentMethod}</span>
                          <span className="status-heading-time">Last updated {time} today </span>
                          {this.state.disableRefresh ? (
                            <span>
                              <img
                                src={`${window.cdnBaseUrl}/static/assets/downtimes/refresh-disabled.svg`}
                                className="status-refresh-icon-disabled"
                              />
                              <Popover align="bottom" theme="light">
                                <PopoverBody>
                                  <div>Try again in {30 - this.state.timeForNextAPI} seconds</div>
                                </PopoverBody>
                              </Popover>
                            </span>
                          ) : (
                            <img
                              src={`${window.cdnBaseUrl}/static/assets/downtimes/refresh-cw.svg`}
                              className="status-refresh-icon"
                              onClick={() => {
                                if (!this.state.disableRefresh) {
                                  this.onUserRefresh();
                                }
                              }}
                            />
                          )}
                        </div>
                      )}
                    </div>
                  </div>
                  <div className="SliderPanel__Body">
                    <div className="panel-body ">
                      {this.state.mode === 'info' ? (
                        <div>
                          {this.state.isL2Loading ? (
                            <div className="page-spinner-container">
                              <Spinner />
                            </div>
                          ) : (
                            <>
                              <div className="status-method-summary">
                                {this.state.paymentMethod === 'Cards' ? (
                                  <CardsInfoDetails
                                    cardDowntimes={this.state.cardDowntimes}
                                    cardNetworksOperational={this.state.cardNetworksOperational}
                                    cardIssuersOperational={this.state.cardIssuersOperational}
                                  />
                                ) : this.state.paymentMethod === 'UPI' ? (
                                  <UPIInfoDetails
                                    upiDowntimes={this.state.upiDowntimes}
                                    pspOperational={this.state.pspOperational}
                                    vpaOperational={this.state.vpaOperational}
                                  />
                                ) : (
                                  <NetBankingInfoDetails
                                    netBankingDowntimes={this.state.netBankingDowntimes}
                                    netBankingOperational={this.state.netBankingOperational}
                                  />
                                )}
                              </div>
                              {showWarningText()}
                              <UpcomingMaintenance
                                paymentMethod={this.state.paymentMethod}
                                scheduledDowntimes={this.state.scheduledDowntimes}
                              />
                              <HistoricalDowntimes paymentMethod={this.state.paymentMethod} />
                            </>
                          )}
                        </div>
                      ) : (
                        <div>
                          {this.state.isL1Loading ? (
                            <div className="page-spinner-container">
                              <Spinner />
                            </div>
                          ) : this.state.errorInFetchingData ? (
                            <FailedStatus onUserRefresh={this.onUserRefresh} />
                          ) : (
                            <>
                              <div className="main-info">
                                <OverallStatus
                                  status={this.state.overallStatus}
                                  downMethods={this.state.methodsDown}
                                />

                                <div className="date-and-time">
                                  Last updated {time} today{' '}
                                  {this.state.disableRefresh ? (
                                    <span>
                                      <img
                                        src={`${window.cdnBaseUrl}/static/assets/downtimes/refresh-disabled.svg`}
                                        className="status-refresh-icon-disabled"
                                      />
                                      <Popover align="bottom" theme="light">
                                        <PopoverBody>
                                          <div>
                                            Try again in {30 - this.state.timeForNextAPI} seconds
                                          </div>
                                        </PopoverBody>
                                      </Popover>
                                    </span>
                                  ) : (
                                    <img
                                      src={`${window.cdnBaseUrl}/static/assets/downtimes/refresh-cw.svg`}
                                      className="status-refresh-icon"
                                      onClick={() => {
                                        if (!this.state.disableRefresh) {
                                          this.onUserRefresh();
                                        }
                                      }}
                                    />
                                  )}
                                </div>
                              </div>
                              <div className="details-section">
                                <CardsDetails
                                  cardDowntimes={this.state.cardDowntimes}
                                  switchToInfoView={this.switchToInfoView}
                                />
                                <UPIDetails
                                  upiDowntimes={this.state.upiDowntimes}
                                  switchToInfoView={this.switchToInfoView}
                                />
                                <NetBankingDetails
                                  netBankingDowntimes={this.state.netBankingDowntimes}
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
