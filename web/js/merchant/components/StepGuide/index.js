import React from 'react';
import { connect } from 'react-redux';
import { classList } from 'common/utils/rzp-utils';
@connect((state) => ({
  org: state.session.org,
  isMobileResolution: state.app.isMobileResolution,
}))
export default class StepGuide extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      activeStep: props.activeStep || 0,
      showLeftIndicator: false,
      showRightIndicator: true,
    };
  }

  /* Method to get the step number based on which indicator is clicked */
  handleSlideIndicator = (slideLeft) => {
    const { activeStep } = this.state;
    const stepNumber = slideLeft ? activeStep - 1 : activeStep + 1;
    this.setActiveStep(stepNumber);
  };
  /* Method to update the state Regarding the active Step */
  setActiveStep = (activeStep = 0) => {
    const { children } = this.props;
    this.setState({
      showLeftIndicator: !(activeStep === 0),
      showRightIndicator: !(activeStep === children?.length - 1),
      activeStep,
    });
  };

  componentWillReceiveProps(nextProps) {
    if (nextProps.activeStep != this.props.activeStep) {
      this.setState({
        activeStep: nextProps.activeStep,
      });
    }
  }

  render() {
    const { className, title, children, closeBtn, isMobileResolution, org } = this.props;
    const { activeStep, showLeftIndicator, showRightIndicator } = this.state;

    const CloseBtn = closeBtn && (closeBtn || <i class="i i-close" />);

    return (
      <div
        class={classList(
          'StepGuide',
          className && `StepGuide--${className}`,
          isMobileResolution && 'StepGuide-mobile',
          org.custom_code === 'axis' && 'no-img',
        )}
      >
        {showLeftIndicator && (
          <i
            class="StepGuide-indicator i i-chevron-left"
            onClick={() => this.handleSlideIndicator(true)}
          />
        )}
        {showRightIndicator && (
          <i
            class="StepGuide-indicator i i-chevron-right"
            onClick={() => this.handleSlideIndicator(false)}
          />
        )}
        {title && <div class="StepGuide--Title">{title}</div>}

        <div class="StepGuide--Steps">{isMobileResolution ? children[activeStep] : children}</div>

        {CloseBtn && <span class="StepGuide--Close">{CloseBtn}</span>}

        {isMobileResolution && (
          <div class="StepGuide--Switcher">
            {children.map((ele, stepNum) => (
              <div
                key={stepNum}
                className={classList('Switcher-switch', activeStep === stepNum && 'active')}
                onClick={() => this.setActiveStep(stepNum)}
              />
            ))}
          </div>
        )}
      </div>
    );
  }
}
