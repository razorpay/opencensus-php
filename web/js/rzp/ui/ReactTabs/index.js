import React, { Component } from 'react';

import { isChildSameType, checkChildrenType } from 'rzp/utils/rzp-react-utils';

class Tab extends Component {
  constructor(props) {
    super(props);
    this.handleClick = this.handleClick.bind(this);
  }

  handleClick(e) {
    e.preventDefault();

    this.props.handleTabChange();

    return this.props.onClick && this.props.onClick(e);
  }

  render() {
    const {
      className = '',
      children,
      handleTabChange,
      isActive,
      onClick,
      ...otherProps
    } = this.props;

    otherProps.className = `${className}${isActive ? ' active' : ''}`;

    return (
      <li {...otherProps}>
        <a href="#" onClick={this.handleClick}>
          {children}
        </a>
      </li>
    );
  }
}

class TabPane extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const { className, children, ...otherProps } = this.props;

    return (
      <div className="tab-pane active in" {...otherProps}>
        {children}
      </div>
    );
  }
}

class Tabs extends Component {
  constructor(props) {
    super(props);

    const { selectedTabIndex, onSelect } = props;

    this.state = {
      selectedTabIndex: selectedTabIndex || 0,
    };

    // if selectedTabIndex and onSelect are passed,
    // the component will act as controlled component,
    // i.e it will not update itself unless the parent says so
    // using selectedTabIndex
    this.isControlled =
      typeof selectedTabIndex !== 'undefined' && typeof onSelect === 'function';
  }

  setSelectedTabIndex(index) {
    this.setState({
      selectedTabIndex: index,
    });
  }

  handleTabClick(index) {
    if (!this.isControlled) {
      return this.setSelectedTabIndex(index);
    }

    return this.props.onSelect(index);
  }

  render() {
    const tabs = [],
      tabPanes = [],
      {
        children,
        className = '',
        justified,
        onSelect,
        selectedTabIndex: selectedTabIndexFromProps,
        tabsWrapperProps = {},
        ...otherProps
      } = this.props,
      { selectedTabIndex: selectedTabIndexFromState } = this.state;

    const selectedTabIndex = this.isControlled
      ? selectedTabIndexFromProps
      : selectedTabIndexFromState;

    otherProps.className = `rzp-react-tabs${className ? ' ' : ''}${className}`;

    React.Children.forEach(children, child => {
      if (isChildSameType(child, Tab)) {
        tabs.push(child);
      } else if (isChildSameType(child, TabPane)) {
        tabPanes.push(child);
      }
    });

    const tabsLength = tabs.length,
      tabPanesLength = tabPanes.length;

    if (tabsLength !== tabPanesLength) {
      throw {
        message:
          `Number of tabs passed is ${tabsLength} and ` +
          `Number of tabPanes passed is ${tabPanesLength}`,
      };
    }

    if (tabsLength === 0) {
      return null;
    }

    tabsWrapperProps.className = `${
      tabsWrapperProps.className ? tabsWrapperProps.className + ' ' : ''
    }nav nav-tabs${justified ? ' nav-justified' : ''}`;

    return (
      <div className="rzp-react-tabs" {...otherProps}>
        <ul {...tabsWrapperProps}>
          {tabs.map((tab, index) => {
            const { children, ...otherProps } = tab.props;

            otherProps.isActive = index === selectedTabIndex;

            return (
              <tab.type
                key={index}
                handleTabChange={this.handleTabClick.bind(this, index)}
                {...otherProps}
              >
                {children}
              </tab.type>
            );
          })}
        </ul>
        <div className="tab-content">
          {tabPanes.map((tabPane, index) => {
            const {
              children: tabPaneChildren,
              style = {},
              ...otherTabPaneProps
            } = tabPane.props;

            const isSelected = selectedTabIndex === index;

            if (!isSelected) {
              style.display = 'none';
            }

            otherTabPaneProps.style = style;

            return (
              <tabPane.type key={index} {...otherTabPaneProps}>
                {isSelected && tabPaneChildren}
              </tabPane.type>
            );
          })}
        </div>
      </div>
    );
  }
}

Tabs.propTypes = {
  children: ({ children }) => checkChildrenType(children, [Tab, TabPane]),
};

Tabs.defaultProps = {
  justified: false,
};

export { Tab, TabPane };
export default Tabs;
