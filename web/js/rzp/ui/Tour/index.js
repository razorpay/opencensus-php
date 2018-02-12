import { Component, Children, cloneElement } from 'react';
import TetherComponent from 'react-tether';
import TourStep from './TourStep';

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
          transform: `translate(${clientRect.left}px, ${clientRect.top}px)`,
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
        {showOverlay ? (
          <div class="Tour__TargetLens" style={targetLensPos} />
        ) : null}
        <TetherComponent
          class={`Tour ${initialized ? 'Tour--initialized' : ''}`}
          target={target}
          attachment={attachment}
          targetAttachment={targetAttachment}
          offset={offset}
        >
          <div />
          {/* required by react-tether */}
          <div class="TourStep__Container">
            <div
              class="arrow"
              style={{
                top: arrowTopPos,
                left: arrowLeftPos,
              }}
            />
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
