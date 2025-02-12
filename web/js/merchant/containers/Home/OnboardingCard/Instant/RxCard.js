import React, { Component } from 'react';
import DoneLogo from 'assets/onboarding/done.png';
import ParliamentLogo from 'assets/onboarding/parliament.svg';
import PointLogo from 'assets/onboarding/points.svg';
import ImgRxBgRight from 'assets/onboarding/rx-bg-right.svg';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import { compose } from 'redux';

import Button from 'common/new-ui/Button';
import Image from 'common/ui/Image';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import LocalStorageService from 'common/utils/localStorage';
import { merchantFetch } from 'merchant/utils/ajax';
import { updateUser } from 'merchant_common/reducers/user';

import {
  RX_HOTJAR_DATA,
  rxHomevisitedFlag,
  rxBenefits,
  rxCaFlag,
  caReqEventType,
  rxCaExp,
} from '../data';

class RxCard extends Component {
  constructor(props) {
    super(props);
    this.state = {
      // eslint-disable-next-line import/no-named-as-default-member
      hidden: !!LocalStorageService.getItem(props.lsKey),
    };
  }

  componentDidMount() {
    if (!this.state.hidden && this.isRxCaExpEnabled) {
      const { trigger, tags } = RX_HOTJAR_DATA.CA_HOME;
      this.addVisitedFlag();
      triggerHotjarRecording(trigger, tags);
    }
  }

  addVisitedFlag = () => {
    const { user } = this.props;
    const { settings } = user.user;

    if (!settings[rxHomevisitedFlag] || settings[rxHomevisitedFlag] === '0') {
      const _settings = { ...settings };
      _settings[rxHomevisitedFlag] = '1';
      merchantFetch({
        url: 'users',
        mode: 'live',
        method: 'patch',
        data: { settings: _settings },
      }).then(() => {
        this.props.updateUser({ settings: _settings });
      });
      this.props.tracking.trackEvent(window.rzpQ.onbr().initiated('rx_home_ca_visited'));
    }
  };

  applyForCa = () => {
    const { user } = this.props;
    const { settings } = user.user;
    const { current } = user;
    const _settings = { ...settings };
    _settings[rxCaFlag] = '1'; // check if applied or not via this flag

    const payload = {
      event_type: caReqEventType,
      event_properties: {
        interested_in_current_account: 1,
        pin_code: null,
        average_monthly_balance: null,
        current_ca: null,
        use_case: null,
        product_name: 'Current_Account',
        source: 'PG-HOME',
      },
    };
    this.props.updateUser({ settings: _settings });
    merchantFetch({
      url: `merchant/${current}/salesforce_event`,
      mode: 'test',
      method: 'post',
      data: payload,
      headers: {
        'Content-Type': 'application/json',
      },
    }).then(() => {
      merchantFetch({
        url: 'users',
        mode: 'live',
        method: 'patch',
        data: { settings: _settings },
      }).then(() => {
        this.props.tracking.trackEvent(window.rzpQ.onbr().initiated('rx_home_ca_requested'));
      });
    });
  };

  handleClose = () => {
    // eslint-disable-next-line import/no-named-as-default-member
    LocalStorageService.setItem(this.props.lsKey, 1);
    this.setState({
      hidden: true,
    });
  };

  get isRxCaExpEnabled() {
    const { experiments } = this.props.user;
    return ((experiments || {})[rxCaExp] || {}).result === 'cohort-1';
  }

  render() {
    const { user } = this.props;
    const { settings } = user.user;
    // show only if the experiment is enabled
    if (this.state.hidden || !this.isRxCaExpEnabled) {
      return null;
    }

    let applied = false;
    if (settings[rxCaFlag] && settings[rxCaFlag] === '1') {
      applied = true;
    }

    return (
      <div className="rx-ca-onboarding-card">
        <div className="side" />
        <img className="right-bottom-img" src={ImgRxBgRight} alt="Right Bottom Logo" />
        <div className="cross" onClick={this.handleClose}>
          <i className="i i-close" />
        </div>
        <div className="left-container">
          <div>
            <img className="img-left" src={ParliamentLogo} alt="Parliament logo" />
          </div>
          <div className="left-info">
            <div className="title">Receive your money in RazorpayX Current Account</div>
            <div className="subtext">
              For this you will get a new current account with various benefits
            </div>
            {!applied ? (
              <>
                <Button.Primary type="button" onClick={this.applyForCa}>
                  Apply for Current Account
                </Button.Primary>
                <a
                  href="https://razorpay.com/x/current-accounts/"
                  target="_blank"
                  rel="noreferrer noopener"
                  className="learn-more"
                >
                  Learn more
                  <i className="i i-external-link" />
                </a>
              </>
            ) : (
              <div className="req-success">
                <div>
                  <Image className="req-submit" src={DoneLogo} alt="Done logo" isWebP />
                </div>
                <div className="req-info">
                  Request submitted successfully. Our executive will call you for further process
                </div>
              </div>
            )}
          </div>
        </div>
        <div className="right-container">
          {rxBenefits.map((el, i) => {
            let classes = 'single-container';
            if (i < rxBenefits.length - 1) {
              classes += ' right-info';
            }
            return (
              <div key={i} className={classes}>
                <div>
                  <img src={PointLogo} alt="Point" />
                </div>
                <div>{el}</div>
              </div>
            );
          })}
        </div>
      </div>
    );
  }
}

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
    }),
    {
      updateUser,
    },
  ),
  rTracking({
    page: 'RxCaInterestHome',
  }),
)(RxCard);
