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

/*
 * Useful to show actionable items on top of 
 * the panel
 */
class PanelTopbar extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {
      children,
      className,
      isLoading,
      hasNoData,
      error,
      ...otherProps
    } = this.props;

    otherProps.className = `panel-topbar${className ? ' ' + className : ''}`;

    return <div {...otherProps}>{children}</div>;
  }
}

/*
 * The body of the panel, where actual content
 * goes
 */
class PanelBody extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {
      children,
      className,
      isLoading,
      hasNoData,
      error,
      ...otherProps
    } = this.props;

    otherProps.className = `panel-body${className ? ' ' + className : ''}`;

    let noDataMsg = '';

    if (error) {
      noDataMsg = 'There is an error while loading data';
    } else if (hasNoData) {
      noDataMsg = 'No Data Found';
    }

    return (
      <div {...otherProps}>
        {(isLoading || noDataMsg) && (
          <Overlay>
            {isLoading ? <Spinner /> : <span>{noDataMsg}</span>}
          </Overlay>
        )}
        {children}
      </div>
    );
  }
}

/*
 * Footer of the Panel, useful to show stats and
 * info
 */
class PanelFooter extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {
      children,
      className,
      isLoading,
      hasNoData,
      error,
      ...otherProps
    } = this.props;

    otherProps.className = `panel-footer${className ? ' ' + className : ''}`;

    return <div {...otherProps}>{children}</div>;
  }
}

/*
 * Main Panel component that uses all the ^ components
 */
class Panel extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {
      className,
      children,
      isLoading,
      hasNoData,
      error,
      ...otherProps
    } = this.props;

    otherProps.className = `panel dasboard-home-panel${className
      ? ' ' + className
      : ''}`;

    const commonProps = { isLoading, hasNoData, error };

    let panelTopbar = null,
      panelBody = null,
      panelFooter = null;

    React.Children.forEach(children, child => {
      if (!panelTopbar && isChildSameType(child, PanelTopbar)) {
        panelTopbar = child;
        return;
      }

      if (!panelBody && isChildSameType(child, PanelBody)) {
        panelBody = child;
        return;
      }

      if (!panelFooter && isChildSameType(child, PanelFooter)) {
        panelFooter = child;
      }
    });

    if (panelTopbar) {
      otherProps.className += ' has-topbar';
    }

    if (panelFooter) {
      otherProps.className += ' has-footer';
    }

    return (
      <div {...otherProps}>
        {panelTopbar && (
          <panelTopbar.type {...panelTopbar.props} {...commonProps} />
        )}
        {panelBody && <panelBody.type {...panelBody.props} {...commonProps} />}
        {panelFooter && (
          <panelFooter.type {...panelFooter.props} {...commonProps} />
        )}
      </div>
    );
  }
}

Panel.propTypes = {
  children: props => {
    return checkChildrenType(props.children, [
      PanelTopbar,
      PanelBody,
      PanelFooter,
    ]);
  },
};

export { PanelTopbar, PanelBody, PanelFooter };

export default Panel;
