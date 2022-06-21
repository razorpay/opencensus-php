import React from 'react';
import { sanitizeTabName, removeUnreconciledEntity } from '../util';
import { titleCase } from 'common/utils/rzp-utils';

const Tabs = (props) => {
  let { items } = props.breakupDetails;
  const { activeTab } = props;

  const renderTabNames = () => {
    items = removeUnreconciledEntity(items);
    const tabNamesObj = items.reduce((acc, tab) => {
      if (!tab.count) return acc;

      const tabName = tab.component.split('_')[0];
      if (!acc[tabName]) {
        acc[tabName] = tab.count;
      } else {
        acc[tabName] = acc[tabName] + tab.count;
      }

      return acc;
    }, {});

    return Object.keys(tabNamesObj).map((tabKey, index) => {
      return (
        <div class={`tab ${tabKey === sanitizeTabName(activeTab) ? 'active' : ''}`} key={index}>
          <span id="source_type" onClick={props.handleTabChange}>
            {titleCase(tabKey)}{' '}
          </span>
          <b class="count">({tabNamesObj[tabKey]})</b>
        </div>
      );
    });
  };

  return <div class="entity-tabs">{renderTabNames()}</div>;
};

export default Tabs;
