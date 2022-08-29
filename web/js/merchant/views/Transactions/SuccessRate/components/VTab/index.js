import React from 'react';
import { ERROR_CATEGORIES, ERROR_CATEGORIES_VS_DISPLAY_TEXT } from '../../constants';
import TabPane from './TabPane';
import TabContent from './TabContent';
import ReasonsPanel from '../ReasonsPanel';

function VTab(props) {
  const { selectedTab, isLoading, onTabChange, ariaLabel, tabData } = props;
  const selectedKey = Object.values(ERROR_CATEGORIES)[selectedTab];
  const title = ERROR_CATEGORIES_VS_DISPLAY_TEXT[selectedKey] ?? '--';
  const content = tabData?.[selectedKey] ?? [];

  return (
    <div className="vtab" aria-label={ariaLabel}>
      <TabPane
        selectedTab={selectedTab}
        isLoading={isLoading}
        onTabChange={onTabChange}
        tabData={tabData}
      />
      <TabContent
        key={`vtab__tabContent-${selectedTab}`}
        id={`vtab__tabContent-${selectedTab}`}
        controlledBy={`vtab__tabPane-${selectedTab}`}
      >
        <ReasonsPanel
          isLoading={isLoading}
          title={title}
          heading={`Payment failures: ${title}`}
          data={content}
        />
      </TabContent>
    </div>
  );
}

export default VTab;
