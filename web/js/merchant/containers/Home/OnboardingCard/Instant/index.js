import React, { Component } from 'react';
import { connect } from 'react-redux';

import TestModeCard from './TestMode';
import ActivationStatusCard from './ActivationStatus';
import LiveModeCard from './LiveMode';

@connect(state => ({ ...state.session, config: state.config.config }))
export default class OnboardingCardInstant extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const { mode, payments, user } = this.props,
      {
        has_key_access: hasKeyAccess,
        business_website: businessWebsite,
        instantActivation,
        isSubmitted,
        isActivated,
        isRejected,
        needsClarification,
      } = user,
      isKLA = !hasKeyAccess && !businessWebsite,
      commonModeCardProps = {
        mode,
        payments,
        hasKeyAccess,
        isKLA,
      },
      activationCardProps = {
        instantActivation,
        isSubmitted,
        needsClarification,
        isActivated,
      };

    return (
      <div className="onboarding-card-instant">
        <div className="onboarding-card-instant-content">
          <div className="onboarding-steps clearfix">
            <TestModeCard {...commonModeCardProps} />
            <ActivationStatusCard {...activationCardProps} />
            <LiveModeCard
              instantActivation={instantActivation}
              isRejected={user.isRejected}
              {...commonModeCardProps}
            />
          </div>
          <div className="onboarding-illustration-top">
            <img src="/dist/css/assets/onboarding/top_bg.png" />
          </div>
          <div className="onboarding-illustration" />
          <div className="onboarding-illustration-bottom">
            <img src="/dist/css/assets/onboarding/bottom_bg.png" />
          </div>
        </div>
      </div>
    );
  }
}
