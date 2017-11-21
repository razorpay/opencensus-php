import React, { Component } from 'react';

import { observable, action } from 'mobx';
import { observer } from 'mobx-react';

class TabModel {
  @observable currentActiveIdx = 0;

  constructor({ defaultActiveInd = 0, totalTabs }) {
    // initialise
    this.totalTabs = totalTabs;
    this.currentActiveIdx = defaultActiveInd;
  }

  @action
  goPrev = _ => {
    const prevTabIdx = this.currentActiveIdx - 1;

    if (this.isInValidTab(prevTabIdx)) {
      return;
    }

    this.currentActiveIdx = prevTabIdx;
  };

  @action
  goNext = _ => {
    const nextTabIdx = this.currentActiveIdx + 1;
    if (this.isInValidTab(nextTabIdx)) {
      return;
    }

    this.currentActiveIdx = nextTabIdx;
  };

  @action
  goTo = index => {
    if (this.isInValidTab(index)) {
      return;
    }
    this.currentActiveIdx = index;
  };

  isInValidTab(possibleIdx) {
    return possibleIdx > this.totalTabs || possibleIdx < 0;
  }
}

/*
  Definition: Simple tabbed container.
  Example: See "Edit Merchant" modal in merchant details
  Props:
    tabNames: Pass array of names to be shown on tabs navigation
    iconBoolList: array of indexes for which 'iconClass' is to be put
                  (Note: iconBoolList is to have 1 for item with index = 0 to have iconClass)
    children: The current selected tab will pick content from children(treated as array)
*/

@observer
export default class TabsContainer extends Component {
  constructor(props) {
    super(props);

    this.model = new TabModel({
      defaultActiveInd: props.defaultActive,
      totalTabs: props.tabNames.length,
    });
  }

  render() {
    let {
      className = '',
      tabNames,
      children,
      iconClass,
      iconBoolList,
    } = this.props;
    className += ' tabs-container m-t m-b';

    const currentContent = children[this.model.currentActiveIdx];

    return (
      <div class={className}>
        <ul class="tabs-nav">
          {tabNames.map((tab, index) => {
            let icon;
            if (iconBoolList && iconBoolList.indexOf(index + 1) > -1) {
              icon = <i class={iconClass} />;
            }

            return (
              <li
                key={index}
                class={this.model.currentActiveIdx === index ? 'selected' : ''}
                onClick={() => this.model.goTo(index)}
              >
                {icon}
                {tab}
              </li>
            );
          })}
        </ul>
        <div class="tabs-content">{currentContent}</div>

        <TabControl model={this.model} />
      </div>
    );
  }
}

const TabControl = ({ model }) => (
  <div class="tabs-control">
    <span
      class={`m-l pills label-semi-muted prev ${model.currentActiveIdx === 0 &&
        'hide'}`}
      onClick={model.goPrev}
    >
      {'< Prev'}
    </span>
    <span
      class={`m-r pills label-semi-muted next ${model.currentActiveIdx ===
        model.totalTabs - 1 && 'hide'}`}
      onClick={model.goNext}
    >
      {'Next >'}
    </span>
  </div>
);
