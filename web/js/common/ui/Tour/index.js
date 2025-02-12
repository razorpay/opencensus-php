import { Component, Children, cloneElement } from 'react';

import scrollTo from 'common/utils/scrollTo';

import TourStep, { TourStepTitle, TourStepBody } from './TourStep';

const activeTourClassName = ' tour-active';

class Tour extends Component {
  constructor(props) {
    super(props);

    this.state = {
      activeStep: 0,
      showLens: true,
    };

    this.onFinish = this.onFinish.bind(this);
    this.onStepClose = this.onStepClose.bind(this);
    this.onStepChange = this.onStepChange.bind(this);
  }

  UNSAFE_componentWillMount() {
    this.setActiveTour(this.props);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    this.setActiveTour(nextProps);
  }

  setTargetLensPos($target) {
    const clientRect = $target.getBoundingClientRect();

    const targetLensPos = {
      top: `${clientRect.top}px`,
      left: `${clientRect.left}px`,
      width: clientRect.width,
      height: clientRect.height,
    };

    this.setState({
      targetLensPos,
      showLens: true,
    });
  }

  setActiveTour(nextProps) {
    let { activeStep: tourStep, children, tourActive } = nextProps;

    if (tourActive && tourStep >= 0) {
      children = Children.toArray(children);
      let activeChild = children[tourStep];
      let target = activeChild.props.to;
      let $target = document.querySelector(target);

      if ($target) {
        $target.focus();

        const { top: eleTop, height } = $target.getBoundingClientRect(),
          eleBottom = eleTop + height,
          screenHeight = window.innerHeight,
          scrollTop = document.documentElement.scrollTop,
          isEleHiddenAboveScreen = eleTop < scrollTop,
          isEleHiddenBelowScreen = eleTop + height > screenHeight;

        if (isEleHiddenAboveScreen || isEleHiddenBelowScreen) {
          let scrollPos = isEleHiddenAboveScreen
            ? eleTop + scrollTop - 50 - 75 - 100
            : eleTop + scrollTop - (screenHeight - height) + 100;

          this.setState(
            {
              showLens: false,
            },
            () => {
              scrollTo({
                endPos: scrollPos,
                duration: 1000,
                cb: () => {
                  this.setTargetLensPos($target);
                },
              });
            }
          );
        } else {
          this.setTargetLensPos($target);
        }
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
    const { onStepChange, activeStep, children } = this.props;

    return onStepChange && onStepChange(children.length - 1, activeStep, true);
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
    let { initialized, target, targetLensPos, showLens } = this.state;

    const { align, ...activeChildProps } = activeChild.props;

    activeChildProps.className = `${
      activeChildProps.className ? activeChildProps.className + ' ' : ''
    }Tour__Overlay`;

    if (!showLens) {
      return <div className="tourstep-overlay" />;
    }

    return (
      <div {...activeChildProps}>
        <div className="Tour__TargetLens" style={targetLensPos}>
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
