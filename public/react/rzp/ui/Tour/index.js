import { Component, Children, cloneElement } from 'react';
import TetherComponent from 'react-tether';
import TourStep from './TourStep';
import './Tour.styl';

// Only Left Popovers are supported as of now
class Tour extends Component {
  state = {
    initialized: false,
  };

  componentWillMount() {
    this.setActiveTour(this.props);
  }

  componentWillReceiveProps(nextProps) {
    this.setActiveTour(nextProps);
  }

  setActiveTour(nextProps) {
    let { tourStep, children, isActive } = nextProps;
    if (isActive && tourStep >= 0) {
      children = Children.toArray(children);
      let activeChild = children[tourStep];
      let target = activeChild.props.to;
      let $target = document.querySelector(target);
      let targetLensPos = {};

      if ($target) {
        let clientRect = $target.getBoundingClientRect();
        targetLensPos = {
          top: clientRect.top,
          left: clientRect.left,
          width: clientRect.width,
          height: clientRect.height,
        };
      }

      this.setState({ target, targetLensPos });
    } else {
      this.setState({ target: null, initialized: false });
    }

    if (!this.props.isActive && nextProps.isActive) {
      window.setTimeout(() => {
        this.setState({ initialized: true });
      });
    }
  }

  renderChildren = children => {
    let { tourStep, isActive } = this.props;
    return Children.toArray(children).map((child, index) => {
      if (isActive && tourStep === index && child.type === TourStep) {
        return cloneElement(child, {
          className: 'TourStep--active',
        });
      }
    });
  };

  render() {
    let { isActive, showOverlay } = this.props;

    if (!isActive) {
      return null;
    }

    return (
      <div
        class="Tour__Overlay"
        style={{
          position: showOverlay ? 'fixed' : 'static',
        }}
      >
        {showOverlay
          ? <div class="Tour__TargetLens" style={this.state.targetLensPos} />
          : null}
        <TetherComponent
          class={`Tour ${this.state.initialized ? 'Tour--initialized' : ''}`}
          target={this.state.target}
          attachment="middle left"
          targetAttachment="middle right"
          offset="0 -15px"
        >
          <div />{/* required by react-tether */}
          <div class="TourStep__Container">
            <div class="arrow" />
            {this.renderChildren(this.props.children)}
          </div>
        </TetherComponent>
      </div>
    );
  }
}

Tour.defaultProps = {
  isActive: false,
  tourStep: 0,
  showOverlay: true,
};

export { Tour, TourStep };
