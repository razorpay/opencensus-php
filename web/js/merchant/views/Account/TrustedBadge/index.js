import React, { Component } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import PropTypes from 'prop-types';

import Feature from 'merchant/components/Feature';
import Details from 'merchant/views/Account/TrustedBadge/components/Details';
import { RTB } from './constants/data';
import LocalStorageService from 'common/utils/localStorage';

@connect((state) => {
  return {
    user: state.session.user,
  };
})
@RTracking(() => window.rzpQ.component('Trusted Badge'))
class TrustedBadge extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor() {
    super();
    this.state = {
      isJoinedWaitlist: !!LocalStorageService.getItem('rtb_join_waitlist'),
      isOptedOut: !!LocalStorageService.getItem('rtb_opt_out'),
    };
  }

  componentDidMount() {
    LocalStorageService.setItem('rtb_page_visited', 1);
    this.props.tracking.trackEvent(
      window.rzpQ &&
        window.rzpQ.merchantActions().initiated('merchant_dashboard_RTB_page', {
          opted_in: this.props.user.isRTBProgramEnabled,
        }),
    );
  }

  joinTheWaitlist = () => {
    return new Promise((resolve) => {
      setTimeout(() => {
        LocalStorageService.removeItem('rtb_opt_out');
        LocalStorageService.setItem('rtb_join_waitlist', 1);

        this.props.tracking.trackEvent(
          window.rzpQ &&
            window.rzpQ.merchantActions().success('merchant_dashboard_RTB_page_opt_in', {
              opted_in: this.props.user.isRTBProgramEnabled,
            }),
        );
        resolve('Success');

        this.setState({ isJoinedWaitlist: true });
      }, 1000);
    });
  };

  optOutConfirmation = () => {
    this.context.confirm({
      header: 'Are you sure you want to opt out?',
      message: () => (
        <div class="text-semi-muted rtb-confirm-opt-out">
          <p>
            On opt out we shall process your request and the trusted badge will be removed from
            checkout in a few days.
          </p>
          <p>
            You will need to join the waitlist again if you wish to show the badge again and stand a
            chance of increasing conversion by 5%.
          </p>
        </div>
      ),
      affirmativeLabel: 'Yes, opt out',
      affirmativePendingLabel: 'Opting out...',
      abortLabel: 'No, don’t',
      action: () => {
        LocalStorageService.removeItem('rtb_join_waitlist');
        LocalStorageService.setItem('rtb_opt_out', 1);
        this.props.tracking.trackEvent(
          window.rzpQ &&
            window.rzpQ.merchantActions().success('merchant_dashboard_RTB_page_opt_out', {
              opted_in: this.props.user.isRTBProgramEnabled,
            }),
        );
        this.setState({ isJoinedWaitlist: false, isOptedOut: true });
      },
    });
  };

  render() {
    const { isJoinedWaitlist, isOptedOut } = this.state;
    const isRTBProgramEnabled = this.props.user.isRTBProgramEnabled;
    const details = isRTBProgramEnabled ? RTB.introAfterOptIn : RTB.introBeforeOptIn;
    const qulificationDetails = isRTBProgramEnabled
      ? RTB.qualificationAfterOptIn
      : RTB.qualificationBeforeOptIn;
    const features = isRTBProgramEnabled ? RTB.featuresAfterOptIn : RTB.featuresBeforeOptIn;
    return (
      <div className="content-wrapper trusted-badge-container">
        <Details
          {...details}
          content="introduction"
          isRTBProgramEnabled={isRTBProgramEnabled}
          isJoinedWaitlist={isJoinedWaitlist}
          isOptedOut={isOptedOut}
          joinTheWaitlist={this.joinTheWaitlist}
          optOutConfirmation={this.optOutConfirmation}
        />

        <div className="row">
          <div className="col-sm-12">
            <div className="tb-title">How will it help your business grow?</div>
            <div className="small-separator green-colored" />
            <div className="trusted-badge-container--Features">
              {features && features.map((feat) => <Feature {...feat} />)}
            </div>
          </div>
        </div>

        <Details
          {...qulificationDetails}
          content="qualification"
          isRTBProgramEnabled={isRTBProgramEnabled}
          isJoinedWaitlist={isJoinedWaitlist}
          isOptedOut={isOptedOut}
          joinTheWaitlist={this.joinTheWaitlist}
          optOutConfirmation={this.optOutConfirmation}
        />
      </div>
    );
  }
}

export default TrustedBadge;
