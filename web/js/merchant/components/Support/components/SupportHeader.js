import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import Popover, { PopoverBody } from 'common/ui/Popover';
import { classList } from 'common/utils/rzp-utils';

@withRouter
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
      this.setState({
        showHelpTooltip: true,
      });
    }

    if (
      this.state.showHelpTooltip &&
      prevProps.isOpened !== this.props.isOpened &&
      this.props.isOpened
    ) {
      this.setState({
        showHelpTooltip: false,
      });
    }
  }

  render() {
    const { notifyCount = 0, isOpened, onToggle, history } = this.props;
    const isOnBoardingRevempScreen = history.location.pathname.includes('onboarding');
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
          isOnBoardingRevempScreen && 'onboarding-screen',
        )}
        onClick={onToggle}
      >
        {!this.state.showHelpTooltip ? (
          content
        ) : (
          <span className="help-content">
            {content}
            <Popover align="left" theme="dark" persistent={this.state.showHelpTooltip}>
              <PopoverBody>
                <div>To know how to use the Dashboard, read the Dashboard Guide</div>
              </PopoverBody>
            </Popover>
          </span>
        )}
      </div>
    );
  }
}
