/*
 * Generic Panel implementation in Home page, that will
 * handle the appearance, structure and the loading
 * states
 */

import React, { Component } from 'react';

import { isChildSameType, checkChildrenType } from 'rzp/utils/rzp-react-utils';
import Overlay from 'rzp/ui/Overlay';
import Spinner from 'rzp/ui/Spinner';

const NoDataMsg = ({ title = '', subtitle = '' }) => {
  return (
    <div className="no-data-msg">
      <p className="no-data-titile">
        <svg xmlns="http://www.w3.org/2000/svg">
          <path d="M20.857 16.49L11.36.457a1 1 0 0 0-1.72 0L.143 16.49a1 1 0 0 0 .86 1.51h18.994a1 1 0 0 0 .86-1.51zm-11.38-2.489a1 1 0 0 1 1-1h.046a1 1 0 0 1 0 2h-.046a1 1 0 0 1-1-1zm0-3V6.004h2.046v4.999H9.477z" />
        </svg>
        <span>&nbsp;</span>
        {title}
      </p>
      {subtitle && <div className="no-data-subtitle">{subtitle}</div>}
    </div>
  );
};

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
      noDataMsg = <NoDataMsg title="Oh snap! Couldn’t load graph data." />;
    } else if (hasNoData) {
      noDataMsg = (
        <NoDataMsg
          title="No data available."
          subtitle={
            `Tip:  You could try again by selecting ` +
            `a different filter or date range.`
          }
        />
      );
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

    if (error) {
      otherProps.className += ' has-error';
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
