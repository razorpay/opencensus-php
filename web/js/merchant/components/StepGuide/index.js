import React from 'react';
import { connect } from 'react-redux';
import { classList } from 'common/utils/rzp-utils';
import { compose } from 'redux';

class StepGuide extends React.Component {
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

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (nextProps.activeStep != this.props.activeStep) {
      this.setState({
        activeStep: nextProps.activeStep,
      });
    }
  }

  render() {
    const { className, title, children, closeBtn, isMobileResolution, org } = this.props;
    const { activeStep, showLeftIndicator, showRightIndicator } = this.state;

    const CloseBtn = closeBtn && (closeBtn || <i className="i i-close" />);

    return (
      <div
        className={classList(
          'StepGuide',
          className && `StepGuide--${className}`,
          isMobileResolution && 'StepGuide-mobile',
          org.custom_code === 'axis' && 'no-img',
        )}
      >
        {showLeftIndicator && (
          <i
            className="StepGuide-indicator i i-chevron-left"
            onClick={() => this.handleSlideIndicator(true)}
          />
        )}
        {showRightIndicator && (
          <i
            className="StepGuide-indicator i i-chevron-right"
            onClick={() => this.handleSlideIndicator(false)}
          />
        )}
        {title && <div className="StepGuide--Title">{title}</div>}

        <div className="StepGuide--Steps">
          {isMobileResolution ? children[activeStep] : children}
        </div>

        {CloseBtn && <span className="StepGuide--Close">{CloseBtn}</span>}

        {isMobileResolution && (
          <div className="StepGuide--Switcher">
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

export default compose(
  connect((state) => ({
    org: state.session.org,
    isMobileResolution: state.app.isMobileResolution,
  })),
)(StepGuide);
