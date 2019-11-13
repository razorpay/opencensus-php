import { connect } from 'react-redux';

import { classList } from 'common/utils/rzp-utils';

@connect(state => ({
  isMobileResolution: state.app.isMobileResolution,
}))
export default class StepGuide extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      activeStep: props.activeStep || 0,
    };
  }

  setActiveStep = (activeStep = 0) => () => {
    this.setState({
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
    const {
        className,
        title,
        children,
        closeBtn,
        isMobileResolution,
      } = this.props,
      { activeStep } = this.state;

    const CloseBtn = closeBtn && (closeBtn || <i class="i i-close" />);

    return (
      <div
        class={classList(
          'StepGuide',
          className && `StepGuide--${className}`,
          isMobileResolution && 'StepGuide-mobile'
        )}
      >
        {title && <div class="StepGuide--Title">{title}</div>}

        <div class="StepGuide--Steps">
          {isMobileResolution ? children[activeStep] : children}
        </div>

        {CloseBtn && <span class="StepGuide--Close">{CloseBtn}</span>}

        {isMobileResolution && (
          <div class="StepGuide--Switcher">
            {children.map((ele, stepNum) => (
              <div
                key={stepNum}
                className={classList(
                  'Switcher-switch',
                  activeStep === stepNum && 'active'
                )}
                onClick={this.setActiveStep(stepNum)}
              />
            ))}
          </div>
        )}
      </div>
    );
  }
}
