import React from 'react';
import { sanitizeTabName } from '../util';
import { titleCase } from 'common/utils/rzp-utils';

const Tabs = (props) => {
  const { items } = props.breakupDetails;
  const { activeTab } = props;

  const renderTabNames = () => {
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
