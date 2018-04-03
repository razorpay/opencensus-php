import { Component, Children, cloneElement } from 'react';
import TetherComponent from 'react-tether';
import TourStep, { TourStepTitle, TourStepBody } from './TourStep';

const activeTourClassName = ' tour-active';

class Tour extends Component {
  constructor(props) {
    super(props);

    this.state = {
      initialized: false,
    };

    this.onFinish = this.onFinish.bind(this);
    this.onStepClose = this.onStepClose.bind(this);
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
      let {} = activeChild.props;
      let target = activeChild.props.to;
      let $target = document.querySelector(target);
      let targetLensPos = {};

      if ($target) {
        const { top, height } = $target.getBoundingClientRect();

        const screenHeight = window.innerHeight,
          scrollDiff = top + height - screenHeight; // 100 is extra scroll

        if (Math.abs(scrollDiff) > 0) {
          window.scrollTo(
            0,
            document.documentElement.scrollTop +
              scrollDiff +
              // 100 is extra 100px scroll
              (scrollDiff > 0 ? 100 : -100)
          );
        }

        const clientRect = $target.getBoundingClientRect();

        targetLensPos = {
          top: `${clientRect.top}px`,
          left: `${clientRect.left}px`,
          width: clientRect.width,
          height: clientRect.height,
        };

        this.setState({
          target,
          targetLensPos,
        });
      } else {
        // skip to next step if target not found
        this.onStepChange(tourStep + 1);
      }
    } else {
      this.setState({ target: null, initialized: false });
    }

    if (!this.props.tourActive && nextProps.tourActive) {
      window.setTimeout(() => {
        this.setState({ initialized: true });
      });
    }
  }

  removeGlobalClass() {
    const documentClass = document.documentElement.className;

    document.documentElement.className = documentClass.replace(
      activeTourClassName,
      ''
    );
  }

  onStepClose() {
    const { onStepChange, children } = this.props;

    return onStepChange && onStepChange(children.length - 1, true);
  }

  onStepChange(stepNum) {
    const { onStepChange } = this.props;

    return onStepChange && onStepChange(stepNum);
  }

  onFinish() {
    const { onFinish } = this.props;

    this.removeGlobalClass();
    return onFinish && onFinish();
  }

  render() {
    const children = React.Children.toArray(this.props.children),
      activeChild = children[this.props.activeStep];

    if (!this.props.tourActive || !activeChild) {
      this.removeGlobalClass();
      return null;
    }

    const documentClass = document.documentElement.className;

    if (documentClass.indexOf(activeTourClassName) < 0) {
      document.documentElement.className = `${documentClass}${activeTourClassName}`;
    }

    let { tourActive, activeStep, showOverlay } = this.props;
    let { initialized, target, targetLensPos } = this.state;

    const { align, ...activeChildProps } = activeChild.props;

    activeChildProps.className = `${
      activeChildProps.className ? activeChildProps.className + ' ' : ''
    }Tour__Overlay`;

    return (
      <div {...activeChildProps}>
        <div class="Tour__TargetLens" style={targetLensPos}>
          {/* redering different instance each time */}
          {children.map((child, index) => {
            return (
              child === activeChild && (
                <child.type
                  key={index}
                  align={align}
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
