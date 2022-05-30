import { Component } from 'react';
import { connect } from 'react-redux';
import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';

import Popover, { PopoverBody } from 'common/ui/Popover';

import {
  COMDEL_POPOVER_TEXT as comdelText,
  SUPPORT_POPOVER_TEXT as supportText,
} from '../constants';
import {
  getCommonSupportProperties,
  getSessionId,
  generateNewLinkedId,
} from 'merchant/components/Support/getCommonSupportProperties';

@connect((state) => {
  return {
    closeOnboardingStep: state.home.closeOnboardingStep,
  };
})
export default class SupportHeader extends Component {
  state = {
    lastSessionId: '',
  };

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

  handleSupportSession = () => {
    const { lastSessionId } = this.state;
    const currentSessionId = getSessionId();

    if (currentSessionId !== lastSessionId) {
      // if lastSessionId is empty then set currentSessionId as lastSessionId.
      // i.e. this will happen when user clicks on help cta for the first time after landing on dashboard
      if (lastSessionId) {
        // else if lastSessionId is not empty but both session id are not same then, generate new
        // linked id ( product requirement ) and update lastSessionId
        generateNewLinkedId();
      }

      this.setState({
        lastSessionId: currentSessionId,
      });
    }
  };

  toggleAnalytics = () => {
    const { isOpened } = this.props;
    analyticsTrack({
      objectName: 'Help and Support',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        location: 'Help and Support',
        type: !isOpened ? 'open' : 'close',
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getCommonSupportProperties(),
      },
    });
  };

  // this function will be called when user clicks on help icon
  handleToggle = () => {
    const { onToggle } = this.props;
    this.handleSupportSession();
    this.toggleAnalytics();
    onToggle();
  };

  render() {
    const {
      notifyCount = 0,
      isOpened,
      isOnBoardingRevampScreen,
      showComdelPopover,
      isWebView,
    } = this.props;
    const content = (
      <>
        {notifyCount ? <span className="notify-icon">{notifyCount}</span> : null}
        <div className="open-icon">
          <span className="support-icon" />
          <span className="support-help">Help</span>
        </div>
      </>
    );

    if (isWebView) {
      return null;
    }

    return (
      <div
        className={classList(
          'support-launcher',
          isOpened && 'active',
          isOnBoardingRevampScreen && 'onboarding-screen',
        )}
        onClick={this.handleToggle}
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
