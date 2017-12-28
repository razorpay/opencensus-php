/*
 * Generic Panel implementation in Home page, that will
 * handle the appearance, structure and the loading
 * states
 */

import React, { Component } from 'react';

import { isChildSameType, checkChildrenType } from 'rzp/utils/rzp-react-utils';
import Overlay from 'rzp/ui/Overlay';
import Spinner from 'rzp/ui/Spinner';

import './styles.styl';

class PanelBody extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const { children, className, isLoading, ...otherProps } = this.props;

    otherProps.className = `panel-body${className ? ' ' + className : ''}`;

    return (
      <div {...otherProps}>
        {isLoading && (
          <Overlay>
            <Spinner />
          </Overlay>
        )}
        {children}
      </div>
    );
  }
}

class PanelFooter extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const { children, className, isLoading, ...otherProps } = this.props;

    otherProps.className = `panel-footer${className ? ' ' + className : ''}`;

    return <div {...otherProps}>{children}</div>;
  }
}

class Panel extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const { className, children, isLoading, ...otherProps } = this.props;

    otherProps.className = `panel dasboard-home-panel${className
      ? ' ' + className
      : ''}`;

    let panelBody = null,
      panelFooter = null;

    React.Children.forEach(children, child => {
      if (!panelBody && isChildSameType(child, PanelBody)) {
        panelBody = child;

        return;
      }

      if (!panelFooter && isChildSameType(child, PanelFooter)) {
        panelFooter = child;
      }
    });

    return (
      <div {...otherProps}>
        {!!panelBody && (
          <panelBody.type {...panelBody.props} isLoading={isLoading} />
        )}
        {!!panelFooter && (
          <panelFooter.type {...panelFooter.props} isLoading={isLoading} />
        )}
      </div>
    );
  }
}

Panel.propTypes = {
  children: props => {
    return checkChildrenType(props.children, [PanelBody, PanelFooter]);
  },
};

export { PanelBody, PanelFooter };

export default Panel;
