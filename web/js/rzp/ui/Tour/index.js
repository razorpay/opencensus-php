import { Component, Children, cloneElement } from 'react';
import TetherComponent from 'react-tether';
import TourStep, { TourStepTitle, TourStepBody } from './TourStep';

class Tour extends Component {
  constructor(props) {
    super(props);

    this.state = {
      initialized: false,
    };

    this.onStepChange = this.onStepChange.bind(this);
  }

  componentWillMount() {
    this.setActiveTour(this.props);
  }

  componentWillReceiveProps(nextProps) {
    this.setActiveTour(nextProps);
  }

  setActiveTour(nextProps) {
    let { activeStep: tourStep, children, tourActive } = nextProps;
    if (tourActive && tourStep >= 0) {
      children = Children.toArray(children);
      let activeChild = children[tourStep];
      let {
        attachment,
        targetAttachment,
        offset,
        arrowTopPos,
        arrowLeftPos,
      } = activeChild.props;
      let target = activeChild.props.to;
      let $target = document.querySelector(target);
      let targetLensPos = {};

      if ($target) {
        let clientRect = $target.getBoundingClientRect();
        targetLensPos = {
          top: `${clientRect.top}px`,
          left: `${clientRect.left}px`,
          width: clientRect.width,
          height: clientRect.height,
        };
      }

      this.setState({
        target,
        targetLensPos,
        targetAttachment,
        attachment,
        offset,
        arrowTopPos,
        arrowLeftPos,
      });
    } else {
      this.setState({ target: null, initialized: false });
    }

    if (!this.props.tourActive && nextProps.tourActive) {
      window.setTimeout(() => {
        this.setState({ initialized: true });
      });
    }
  }

  onStepClose() {}

  onStepChange(stepNum) {
    const { onStepChange } = this.props;

    return onStepChange && onStepChange(stepNum);
  }

  onFinish() {}

  render() {
    const children = React.Children.toArray(this.props.children),
      activeChild = children[this.props.activeStep];

    if (!this.props.tourActive || !activeChild) {
      return null;
    }

    let {
      tourActive,
      activeStep,
      showOverlay,
      onSkip,
      onFinish,
      onStepChange,
    } = this.props;
    let {
      initialized,
      target,
      targetAttachment,
      targetLensPos,
      attachment,
      offset,
      arrowTopPos,
      arrowLeftPos,
    } = this.state;

    return (
      <div class="Tour__Overlay">
        <div class="Tour__TargetLens" style={targetLensPos}>
          {/* redering different instance each time */}
          {children.map((child, index) => {
            return (
              child === activeChild && (
                <child.type
                  {...child.props}
                  key={index}
                  index={activeStep}
                  totalSteps={children.length}
                  onStepChange={this.onStepChange}
                  onStepClose={this.onStepClose}
                  onFinish={this.onFinish}
                >
                  {child.props.children}
                </child.type>
              )
            );
          })}
        </div>
      </div>
    );
  }
}

Tour.defaultProps = {
  tourActive: false,
  tourStep: 0,
  showOverlay: true,
};

export { Tour, TourStep, TourStepTitle, TourStepBody };
