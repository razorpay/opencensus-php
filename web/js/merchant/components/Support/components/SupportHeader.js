import { Component } from 'react';
import { connect } from 'react-redux';
import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';

import Popover, { PopoverBody } from 'common/ui/Popover';

import {
  COMDEL_POPOVER_TEXT as comdelText,
  SUPPORT_POPOVER_TEXT as supportText,
} from '../constants';

@connect((state) => {
  return {
    closeOnboardingStep: state.home.closeOnboardingStep,
  };
})
export default class SupportHeader extends Component {
  state = {};

  componentDidUpdate(prevProps) {
    // After onboarding is complete, always show this tooltip
    if (
      prevProps.closeOnboardingStep !== this.props.closeOnboardingStep &&
      this.props.closeOnboardingStep
    ) {
      // eslint-disable-next-line react/no-did-update-set-state
      this.setState({
        showHelpTooltip: true,
      });
    }

    if (
      this.state.showHelpTooltip &&
      prevProps.isOpened !== this.props.isOpened &&
      this.props.isOpened
    ) {
      // eslint-disable-next-line react/no-did-update-set-state
      this.setState({
        showHelpTooltip: false,
      });
    }
  }

  render() {
    const {
      notifyCount = 0,
      isOpened,
      onToggle,
      isOnBoardingRevampScreen,
      showComdelPopover,
    } = this.props;
    const content = (
      <>
        {notifyCount ? <span class="notify-icon">{notifyCount}</span> : null}
        <div class="open-icon">
          <i class="i i-headset" />
        </div>
        <div class="close-icon">
          <i class="i i-close " />
        </div>
      </>
    );

    return (
      <div
        class={classList(
          'support-launcher',
          isOpened && 'active',
          isOnBoardingRevampScreen && 'onboarding-screen',
        )}
        onClick={(...e) => {
          analyticsTrack({
            objectName: 'Help and Support',
            actionName: 'clicked',
            screen: 'home page',
            properties: {
              location: 'Help and Support',
              type: !isOpened ? 'open' : 'close',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          onToggle(...e);
        }}
      >
        <span className="help-content">
          {content}
          {(showComdelPopover || this.state.showHelpTooltip) && (
            <Popover align="left" theme="dark" persistent={this.state.showHelpTooltip}>
              <PopoverBody>
                <div>{showComdelPopover ? comdelText : supportText}</div>
              </PopoverBody>
            </Popover>
          )}
        </span>
      </div>
    );
  }
}
