import React, { Component, cloneElement } from 'react';
import { findDOMNode } from 'react-dom';
import { expandSlider, compactSlider } from 'merchant_common/reducers/slider';
import { connect } from 'react-redux';
import { analyticsTrack } from 'common/utils/analytics';

const PrimaryView = ({ children, ...rest }) => cloneElement(children, rest);
const SecondaryView = ({ children, activeEntityName, entityName, ...rest }) =>
  activeEntityName === entityName && cloneElement(children, rest);
class DualViewContainer extends Component {
  componentDidMount() {
    this.checkSecView(this.props);
  }

  checkSecView(props) {
    if (!props.secondaryView) {
      // this is temporary for tracing purposes
      analyticsTrack({
        screen: 'DualDetailView',
        objectName: 'checkSecView without secondaryView',
        actionName: 'started',
      });
      this.props.compactSlider();
      // To avoid not toggling issue when browser back btn is clicked when
      // secondary view is overlayed in dual view while small-screen
      if (this.secViewRef && findDOMNode(this.secViewRef)) {
        // this is temporary for tracing purposes
        analyticsTrack({
          screen: 'DualDetailView',
          objectName: 'checkSecView without secondaryView',
          actionName: 'toggled',
        });
        findDOMNode(this.secViewRef).classList.add('toggle-slider');
      }
    } else {
      // this is temporary for tracing purposes
      analyticsTrack({
        screen: 'DualDetailView',
        objectName: `checkSecView with secondaryView ${props?.secondaryView}`,
        actionName: 'started',
      });
      this.props.expandSlider();
      // To avoid not toggling issue when browser back btn is clicked when
      // secondary view is overlayed in dual view while small-screen
      if (this.secViewRef && findDOMNode(this.secViewRef)) {
        // this is temporary for tracing purposes
        analyticsTrack({
          screen: 'DualDetailView',
          objectName: `checkSecView with secondaryView ${props?.secondaryView}`,
          actionName: 'toggled',
        });
        findDOMNode(this.secViewRef).classList.remove('toggle-slider');
      }
    }
  }

  render() {
    const { secondaryView } = this.props;

    const children = React.Children.map(this.props.children, (child) => {
      if (child.type === SecondaryView) {
        return secondaryView
          ? cloneElement(child, {
              ref: (ele) => (this.secViewRef = ele),
              isOpenedInDualMode: true,
              activeEntityName: secondaryView,
            })
          : null;
      } else {
        return cloneElement(child, { isOpenedInDualMode: true });
      }
    });

    if (secondaryView) {
      return <div class="multi-content">{children}</div>;
    } else {
      return children;
    }
  }
}

const DualDetailView = (props) => {
  const { children, ...rest } = props;
  if (rest.secondaryView) {
    return <DualViewContainer {...rest}>{children}</DualViewContainer>;
  }
  return React.Children.map(children, (child) => {
    // Only render first child which will be the primary view
    if (child.type === PrimaryView) {
      return child;
    }
    return null;
  });
};

export { PrimaryView, SecondaryView };
export default connect(null, { compactSlider, expandSlider })(DualDetailView);
