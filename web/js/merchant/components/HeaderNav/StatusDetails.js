import React from 'react';
import { connect } from 'react-redux';

import Slider from 'common/ui/Slider';
import { openSlider } from 'merchant_common/reducers/slider';
// import arrowLeft from '../../../../icons/merchant/arrow-left.svg';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { BANKS, VPA_HANDLES, PSPs, CARD_ISSUERS, CARD_NETWORKS } from './constants';
import refreshIcon from '../../../../icons/merchant/refresh-cw.svg';
import refreshDisabled from '../../../../icons/merchant/refresh-disabled.svg';
import {
  fetchOngoingDowntimes,
  fetchScheduledDowntimes,
  fetchHistoricalDowntimes,
} from 'merchant/reducers/status';
import Spinner from 'common/ui/Spinner';
import Popover, { PopoverBody } from 'common/ui/Popover';
import StatusMainInfo from './components/StatusMainInfo';
import CardsDetails from './components/CardsDetails';
import UPIDetails from './components/UPIDetails';
import NetBankingDetails from './components/NetBankingDetails';
import CardsInfoDetails from './components/CardsInfoDetails';
import UPIInfoDetails from './components/UPIInfoDetails';
import NetBankingInfoDetails from './components/NetBankingInfoDetails';
import UpcomingMaintenance from './components/UpcomingMaintenance';
import HistoricalDowntimes from './components/HistoricalDowntimes';

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

  getTimeinTwelveHourFormat = (dateObj) => {
    let hours = dateObj.getHours();
    let meridian = 'am';
    if (hours > 12) {
      hours = hours - 12;
      meridian = 'pm';
    }
    let minutes = dateObj.getMinutes();
    if (String(minutes).length === 1) {
      minutes = `0${minutes}`;
    }
    const time = `${hours}:${minutes} ${meridian}`;
    return time;
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
    const time = this.getTimeinTwelveHourFormat(now);
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
        let data = [];
        if (Array.isArray(response)) {
          data = response;
        } else {
          data = response.data;
        }

        const cardNetworksOperational = [...CARD_NETWORKS];
        const cardIssuersOperational = [...CARD_ISSUERS];
        const vpaOperational = [...VPA_HANDLES];
        const pspOperational = [...PSPs];
        const netBankingOperational = [...BANKS];

        if (data.length === 0) {
          // Setting time
          const now = new Date();
          const time = this.getTimeinTwelveHourFormat(now);
          this.setState({
            overallStatus: 'operational',
            cardDowntimes: {},
            upiDowntimes: {},
            netBankingDowntimes: {},
            cardNetworksOperational,
            cardIssuersOperational,
            vpaOperational,
            pspOperational,
            netBankingOperational,
            isL1Loading: false,
            time,
            timeObj: now,
          });
        } else {
          const cardDowntimes = {};
          const upiDowntimes = {};
          const netBankingDowntimes = {};
          let overallStatus = '';
          const methodsDown = [];

          data.forEach((downtime) => {
            switch (downtime.method) {
              case 'card':
                {
                  const instrument = Object.keys(downtime.instrument)[0];
                  if (instrument === 'network') {
                    if (!('network' in cardDowntimes)) {
                      cardDowntimes.network = {};
                    }
                    switch (downtime.severity) {
                      case 'low':
                        if ('low' in cardDowntimes.network)
                          cardDowntimes.network.low.push(downtime);
                        else cardDowntimes.network.low = [downtime];
                        break;
                      case 'medium':
                        if ('medium' in cardDowntimes.network)
                          cardDowntimes.network.medium.push(downtime);
                        else cardDowntimes.network.medium = [downtime];
                        break;
                      case 'high':
                        if ('high' in cardDowntimes.network)
                          cardDowntimes.network.high.push(downtime);
                        else cardDowntimes.network.high = [downtime];
                        break;
                      default:
                    }
                    const index = cardNetworksOperational.indexOf(downtime.instrument[instrument]);
                    if (index > -1) {
                      cardNetworksOperational.splice(index, 1);
                    }
                  } else if (instrument === 'issuer') {
                    if (!('issuer' in cardDowntimes)) {
                      cardDowntimes.issuer = {};
                    }
                    // Mapping to card issuer name
                    downtime.mapToName = true;
                    const issuer = CARD_ISSUERS.find(
                      (element) => element.code === downtime?.instrument?.issuer,
                    );
                    if (issuer != undefined) {
                      const issuerName = issuer.issuerName;
                      downtime.providerName = issuerName;
                    }

                    const index = cardIssuersOperational.findIndex(
                      (element) => element.code === downtime?.instrument?.issuer,
                    );
                    if (index > -1) {
                      cardIssuersOperational.splice(index, 1);
                    }

                    switch (downtime.severity) {
                      case 'low':
                        if ('low' in cardDowntimes.issuer) cardDowntimes.issuer.low.push(downtime);
                        else cardDowntimes.issuer.low = [downtime];
                        break;
                      case 'medium':
                        if ('medium' in cardDowntimes.issuer)
                          cardDowntimes.issuer.medium.push(downtime);
                        else cardDowntimes.issuer.medium = [downtime];
                        break;
                      case 'high':
                        if ('high' in cardDowntimes.issuer)
                          cardDowntimes.issuer.high.push(downtime);
                        else cardDowntimes.issuer.high = [downtime];
                        break;
                      default:
                    }
                  }
                }

                break;
              case 'upi':
                {
                  const instruments = Object.keys(downtime.instrument);
                  let instrument;
                  if (instruments.length > 0) instrument = instruments[0];
                  else overallStatus = 'severeDrop';

                  if (instrument === 'vpa_handle') {
                    if (!('vpa_handle' in upiDowntimes)) {
                      upiDowntimes.vpa_handle = {};
                    }
                    switch (downtime.severity) {
                      case 'low':
                        if ('low' in upiDowntimes.vpa_handle)
                          upiDowntimes.vpa_handle.low.push(downtime);
                        else upiDowntimes.vpa_handle.low = [downtime];
                        break;
                      case 'medium':
                        if ('medium' in upiDowntimes.vpa_handle)
                          upiDowntimes.vpa_handle.medium.push(downtime);
                        else upiDowntimes.vpa_handle.medium = [downtime];
                        break;
                      case 'high':
                        if ('high' in upiDowntimes.vpa_handle)
                          upiDowntimes.vpa_handle.high.push(downtime);
                        else upiDowntimes.vpa_handle.high = [downtime];
                        break;
                      default:
                    }
                    const index = vpaOperational.indexOf(downtime.instrument[instrument]);
                    if (index > -1) {
                      vpaOperational.splice(index, 1);
                    }
                  } else if (instrument === 'psp') {
                    if (!('psp' in cardDowntimes)) {
                      upiDowntimes.psp = {};
                    }
                    // Mapping to psp name
                    downtime.mapToName = true;
                    const psp = PSPs.find((element) => element.code === downtime?.instrument?.psp);
                    if (psp != undefined) {
                      const pspName = psp.pspName;
                      downtime.providerName = pspName;
                    }

                    const index = pspOperational.findIndex(
                      (element) => element.code === downtime?.instrument?.psp,
                    );
                    if (index > -1) {
                      pspOperational.splice(index, 1);
                    }

                    switch (downtime.severity) {
                      case 'low':
                        if ('low' in upiDowntimes.psp) upiDowntimes.psp.low.push(downtime);
                        else upiDowntimes.psp.low = [downtime];
                        break;
                      case 'medium':
                        if ('medium' in upiDowntimes.psp) upiDowntimes.psp.medium.push(downtime);
                        else upiDowntimes.psp.medium = [downtime];
                        break;
                      case 'high':
                        if ('high' in upiDowntimes.psp) upiDowntimes.psp.high.push(downtime);
                        else upiDowntimes.psp.high = [downtime];
                        break;
                      default:
                    }
                  }
                }
                break;
              case 'netbanking':
                {
                  // Mapping to bank name
                  downtime.mapToName = true;
                  const bank = BANKS.find((element) => element.code === downtime?.instrument?.bank);
                  if (bank != undefined) {
                    const bankName = bank.bankName;
                    downtime.providerName = bankName;
                  }

                  const index = netBankingOperational.findIndex(
                    (element) => element.code === downtime?.instrument?.bank,
                  );
                  if (index > -1) {
                    netBankingOperational.splice(index, 1);
                  }

                  switch (downtime.severity) {
                    case 'low':
                      if ('low' in netBankingDowntimes) netBankingDowntimes.low.push(downtime);
                      else netBankingDowntimes.low = [downtime];
                      break;
                    case 'medium':
                      if ('medium' in netBankingDowntimes)
                        netBankingDowntimes.medium.push(downtime);
                      else netBankingDowntimes.medium = [downtime];
                      break;
                    case 'high':
                      if ('high' in netBankingDowntimes) netBankingDowntimes.high.push(downtime);
                      else netBankingDowntimes.high = [downtime];
                      break;
                    default:
                  }
                }
                break;
              default:
            }
          });

          // Checking for the overall status
          if (overallStatus === 'severeDrop') {
            console.log('Severe drop');
          } else if (
            cardDowntimes?.network?.high?.length > 1 ||
            cardDowntimes?.issuer?.high?.length > 2 ||
            upiDowntimes?.vpa_handle?.high?.length > 2 ||
            upiDowntimes?.psp?.high?.length > 1 ||
            netBankingDowntimes.high > 2
          )
            overallStatus = 'majorDrops';
          else overallStatus = 'fewDrops';

          if (Object.keys(cardDowntimes).length > 0) {
            methodsDown.push('Cards');
          }
          if (Object.keys(upiDowntimes).length > 0) {
            methodsDown.push('UPI');
          }
          if (Object.keys(netBankingDowntimes).length > 0) {
            methodsDown.push('Net Banking');
          }

          // Setting time
          const now = new Date();
          const time = this.getTimeinTwelveHourFormat(now);

          this.setState({
            overallStatus,
            methodsDown,
            cardDowntimes,
            upiDowntimes,
            netBankingDowntimes,
            cardNetworksOperational,
            cardIssuersOperational,
            vpaOperational,
            pspOperational,
            netBankingOperational,
            isL1Loading: false,
            time,
            timeObj: now,
          });
        }
      })
      .catch(() => {
        // console.log('Some error in fetching ongoing', err);
        this.setState({ isL1Loading: false });
      });
  };

  setScheduledAndHistoricalDowntimes = (paymentMethod) => {
    this.setState({ isL2Loading: true });
    fetchScheduledDowntimes()
      .then((response) => {
        // console.log('Scheduled-->', response);
        let data = [];
        if (Array.isArray(response)) {
          data = response;
        } else {
          data = response.data;
        }
        const scheduledDowntimes = {};
        data.forEach((scheduledDowntime) => {
          const method = scheduledDowntime.method;
          switch (method) {
            case 'card':
              {
                if (!('card' in scheduledDowntimes)) {
                  scheduledDowntimes.card = [];
                }
                const instrument = Object.keys(scheduledDowntime.instrument)[0];
                if (instrument === 'issuer') {
                  // Mapping to card issuer name
                  scheduledDowntime.mapToName = true;
                  const issuer = CARD_ISSUERS.find(
                    (element) => element.code === scheduledDowntime?.instrument?.issuer,
                  );
                  if (issuer != undefined) {
                    const issuerName = issuer.issuerName;
                    scheduledDowntime.providerName = issuerName;
                  }
                }
                scheduledDowntimes.card.push(scheduledDowntime);
              }
              break;
            case 'upi':
              {
                if (!('upi' in scheduledDowntimes)) {
                  scheduledDowntimes.upi = [];
                }
                const instrument = Object.keys(scheduledDowntime.instrument)[0];
                if (instrument === 'psp') {
                  // Mapping to psp name
                  scheduledDowntime.mapToName = true;
                  const psp = PSPs.find(
                    (element) => element.code === scheduledDowntime?.instrument?.psp,
                  );
                  if (psp != undefined) {
                    const pspName = psp.pspName;
                    scheduledDowntime.providerName = pspName;
                  }
                }
                scheduledDowntimes.upi.push(scheduledDowntime);
              }
              break;
            case 'netbanking':
              {
                if (!('netbanking' in scheduledDowntimes)) {
                  scheduledDowntimes.netbanking = [];
                }

                // Mapping to bank name
                scheduledDowntime.mapToName = true;
                const bank = BANKS.find(
                  (element) => element.code === scheduledDowntime?.instrument?.bank,
                );
                if (bank != undefined) {
                  const bankName = bank.bankName;
                  scheduledDowntime.providerName = bankName;
                }

                scheduledDowntimes.netbanking.push(scheduledDowntime);
              }
              break;

            default:
              break;
          }
        });

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
        // console.log('Historical downtimes - card', response);
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
    this.setState({ sliderOpen: false });
  };

  showSlider = () => {
    this.props.openSlider();
    this.setState({ sliderOpen: true });
  };

  handleSliderToggleClick = () => {
    const { sliderOpen } = this.state;
    if (sliderOpen) this.hideSlider();
    else this.showSlider();
  };

  render() {
    const { sliderOpen, time } = this.state;

    const disclaimerText =
      'This is the historical information about the state of our platform incidents, RCA (Root Cause Analisys), scheduled maintenance, conectivity problems with our third party and all related data about the perfomance of our services.';

    return (
      <main className="status-details">
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
                          <b>Payment Method Status</b>
                        </div>
                      ) : (
                        <div>
                          <img
                            class="status-back"
                            src="https://betacdn.razorpay.com/static/assets/downtimes/arrow-left.svg"
                            onClick={this.switchToSummaryView}
                          />
                          <span class="status-heading">{this.state.paymentMethod}</span>
                          <span class="status-heading-time">Last updated {time} today </span>
                          {this.state.disableRefresh ? (
                            <span>
                              <img src={refreshDisabled} class="status-refresh-icon-disabled" />
                              <Popover align="bottom" theme="dark">
                                <PopoverBody>
                                  <div>Try again in {30 - this.state.timeForNextAPI} seconds</div>
                                </PopoverBody>
                              </Popover>
                            </span>
                          ) : (
                            <img
                              src={refreshIcon}
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
                  {this.state.mode === 'info' ? (
                    <div class="status-info">
                      {this.state.isL2Loading ? (
                        <div class="page-spinner-container">
                          <Spinner />
                        </div>
                      ) : (
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
                          <UpcomingMaintenance
                            paymentMethod={this.state.paymentMethod}
                            scheduledDowntimes={this.state.scheduledDowntimes}
                          />

                          <HistoricalDowntimes />
                        </div>
                      )}
                    </div>
                  ) : (
                    // L1 Details
                    <div class="SliderPanel__Body">
                      {this.state.isL1Loading ? (
                        <div class="page-spinner-container">
                          <Spinner />
                        </div>
                      ) : (
                        <div class="panel-body">
                          <div className="main-info">
                            <StatusMainInfo
                              overallStatus={this.state.overallStatus}
                              methodsDown={this.state.methodsDown}
                            />

                            <div className="date-and-time">
                              Last updated {time} today{' '}
                              {this.state.disableRefresh ? (
                                <span>
                                  <img src={refreshDisabled} class="status-refresh-icon-disabled" />
                                  <Popover align="bottom" theme="light">
                                    <PopoverBody>
                                      <div class="time-left-popover">
                                        Try again in {30 - this.state.timeForNextAPI} seconds
                                      </div>
                                    </PopoverBody>
                                  </Popover>
                                </span>
                              ) : (
                                <img
                                  src={refreshIcon}
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
                          <div className="status-disclaimer">{disclaimerText}</div>
                        </div>
                      )}
                    </div>
                  )}
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
