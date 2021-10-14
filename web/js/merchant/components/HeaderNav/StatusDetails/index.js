import React from 'react';
import { connect } from 'react-redux';

import Slider from 'common/ui/Slider';
import { openSlider } from 'merchant_common/reducers/slider';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { BANKS, PSPs, CARD_ISSUERS } from './constants';
import {
  fetchOngoingDowntimes,
  fetchScheduledDowntimes,
  fetchHistoricalDowntimes,
} from './service';
import Spinner from 'common/ui/Spinner';
import Popover, { PopoverBody } from 'common/ui/Popover';
import StatusMainInfo from './StatusMainInfo';
import CardsDetails from './CardsDetails';
import UPIDetails from './UPIDetails';
import NetBankingDetails from './NetBankingDetails';
import CardsInfoDetails from './CardsInfoDetails';
import UPIInfoDetails from './UPIInfoDetails';
import NetBankingInfoDetails from './NetBankingInfoDetails';
import UpcomingMaintenance from './UpcomingMaintenance';
import HistoricalDowntimes from './HistoricalDowntimes';
import { getTimeinTwelveHourFormat, showWarningText } from './utilities';
import { classList } from 'common/utils/rzp-utils';
import FailedStatus from './FailedStatus';

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
      .catch(() => {
        // console.log(err);
        // console.log('scheduled not working');
      });

    this.setHistoricalDowntimes(paymentMethod);
  };

  setHistoricalDowntimes = (paymentMethod) => {
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

    fetchHistoricalDowntimes(
      method,
      String(this.state.skip),
      String(this.state.count),
      startDate,
      endDate,
    )
      .then((response) => {
        let data = [];
        if (Array.isArray(response)) {
          data = response;
        } else {
          data = response.data;
        }
        // const dataLength = data.length;
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
        this.setState({
          // historicalDowntimes: data,
          isL2Loading: false,
          // length: dataLength,
          // isHistoricalLoading: false,
        });
      })
      .catch(() => {
        // console.log('Some error in fetching historical', err);
        this.setState({
          isL2Loading: false,
          // isHistoricalLoading: false
        });
      });
  };

  handleDocumentClick = (event) => {
    const userTarget = event.target;
    const sliderContent = document.querySelector('.content-wrapper.status-details');
    const sliderToggle = document.querySelector('.status-details-slide-toggle');
    const downtimeDetails = document.querySelector('.panel.panel-default.SliderPanel');
    if (
      (sliderToggle && sliderToggle.contains(userTarget)) ||
      (sliderContent && sliderContent.contains(userTarget)) ||
      (downtimeDetails && downtimeDetails.contains(userTarget))
    ) {
      return;
    }
    this.hideSlider();
  };

  componentDidMount() {
    this.intervalForDebounce = setInterval(() => {
      this.checkForDebounce();
    }, 1000);
    this.intervalForTime = setInterval(() => this.refreshData(), 300000);
    document.addEventListener('click', this.handleDocumentClick, true);
  }

  componentWillUnmount() {
    clearInterval(this.intervalForTime);
    clearInterval(this.intervalForDebounce);
    document.removeEventListener('click', this.handleDocumentClick, true);
  }

  hideSlider = () => {
    console.log('hideSlider is called');
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
              <div class="content-wrapper content-sm txn-details status-details">
                <div class="panel panel-default SliderPanel">
                  <div class="panel-heading">
                    <div class="heading-content">
                      {this.state.mode === 'summary' ? (
                        <div class="title">
                          <b>Payment Methods Status</b>
                        </div>
                      ) : (
                        <div>
                          <img
                            class="status-back"
                            src={`${window.cdnBaseUrl}/static/assets/downtimes/arrow-left.svg`}
                            onClick={this.switchToSummaryView}
                          />
                          <span class="status-heading">{this.state.paymentMethod}</span>
                          <span class="status-heading-time">Last updated {time} today </span>
                          {this.state.disableRefresh ? (
                            <span>
                              <img
                                src={`${window.cdnBaseUrl}/static/assets/downtimes/refresh-disabled.svg`}
                                class="status-refresh-icon-disabled"
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
                              class="status-refresh-icon"
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
                  <div class="SliderPanel__Body">
                    <div class="panel-body ">
                      {this.state.mode === 'info' ? (
                        <div>
                          {this.state.isL2Loading ? (
                            <div class="page-spinner-container">
                              <Spinner />
                            </div>
                          ) : (
                            <>
                              <div class="status-method-summary">
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
                            <div class="page-spinner-container">
                              <Spinner />
                            </div>
                          ) : this.state.errorInFetchingData ? (
                            <FailedStatus onUserRefresh={this.onUserRefresh} />
                          ) : (
                            <>
                              <div className="main-info">
                                <StatusMainInfo
                                  overallStatus={this.state.overallStatus}
                                  methodsDown={this.state.methodsDown}
                                />

                                <div className="date-and-time">
                                  Last updated {time} today{' '}
                                  {this.state.disableRefresh ? (
                                    <span>
                                      <img
                                        src={`${window.cdnBaseUrl}/static/assets/downtimes/refresh-disabled.svg`}
                                        class="status-refresh-icon-disabled"
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
                                      class="status-refresh-icon"
                                      onClick={() => {
                                        if (!this.state.disableRefresh) {
                                          this.onUserRefresh();
                                        }
                                      }}
                                    />
                                  )}
                                </div>
                              </div>
                              <div className="details">
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
