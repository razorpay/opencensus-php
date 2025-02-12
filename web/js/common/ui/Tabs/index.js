import React, { Component } from 'react';
import { titleCase } from 'common/utils/rzp-utils';

/*
  Definition: Simple tabbed container.
  Example: See analytics on Home page
  Props:
    tabNames: Pass array of names to be shown on tabs navigation
    iconBoolList: array of indexes for which 'iconClass' is to be put
                  (Note: iconBoolList is to have 1 for item with index = 0 to have iconClass)
    children: The current selected tab will pick content from children(treated as array)
*/

export default class TabsContainer extends Component {
  constructor(props) {
    super(props);

    this.totalTabs = props.tabNames.length || 0;

    this.state = {
      currentActiveIdx: props.defaultActive || 0,
    };
  }

  goPrev = (_) => {
    const prevTabIdx = this.state.currentActiveIdx - 1;

    if (this.isInValidTab(prevTabIdx)) {
      return;
    }

    this.setState({ currentActiveIdx: prevTabIdx });
  };

  goNext = (_) => {
    const nextTabIdx = this.state.currentActiveIdx + 1;
    if (this.isInValidTab(nextTabIdx)) {
      return;
    }

    this.setState({ currentActiveIdx: nextTabIdx });
  };

  goTo = (index) => {
    if (this.isInValidTab(index)) {
      return;
    }

    this.setState({ currentActiveIdx: index });
  };

  isInValidTab(possibleIdx) {
    return possibleIdx > this.totalTabs || possibleIdx < 0;
  }

  render() {
    let {
      className = '',
      tabNames,
      children,
      iconClass,
      iconBoolList,
      enableController = false,
    } = this.props;
    className += ' tabs-container';

    const currentContent = children[this.state.currentActiveIdx];

    return (
      <div className={className}>
        <ul className="tabs-nav">
          {tabNames.map((tab, index) => {
            let icon;
            if (iconBoolList && iconBoolList.indexOf(index + 1) > -1) {
              icon = <i className={iconClass} />;
            }

            return (
              <li
                key={index}
                className={this.state.currentActiveIdx === index ? 'selected' : ''}
                onClick={() => this.goTo(index)}
              >
                {icon}
                {tab}
              </li>
            );
          })}
        </ul>
        <div className="tabs-content">{currentContent}</div>

        {enableController && (
          <TabControl
            goPrev={this.goPrev}
            goNext={this.goNext}
            currentActiveIdx={this.state.currentActiveIdx}
            totalTabs={this.totalTabs}
          />
        )}
      </div>
    );
  }
}

const TabControl = ({ goPrev, goNext, currentActiveIdx, totalTabs }) => (
  <div className="tabs-control">
    <span
      className={`m-l pill label-semi-muted prev ${currentActiveIdx === 0 && 'hide'}`}
      onClick={goPrev}
    >
      {'< Prev'}
    </span>
    <span
      className={`m-r pill label-semi-muted next ${currentActiveIdx === totalTabs - 1 && 'hide'}`}
      onClick={goNext}
    >
      {'Next >'}
    </span>
  </div>
);
