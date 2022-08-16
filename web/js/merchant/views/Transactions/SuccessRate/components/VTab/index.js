import React from 'react';
import TabContent from './TabContent';
import TabPane from './TabPane';

function VTab(props) {
  const { defaultActiveKey, selected, tabs, onTabChange, ariaLabel } = props;

  return (
    <div className="vtab" aria-label={ariaLabel}>
      <TabPane selected={defaultActiveKey || selected} onTabChange={onTabChange} tabs={tabs} />
      <TabContent
        key={`vtab__tabContent-${selected}`}
        id={`vtab__tabContent-${selected}`}
        controlledBy={`vtab__tabPane-${selected}`}
        tabs={tabs}
      >
        {tabs[selected].panel}
      </TabContent>
    </div>
  );
}

export default VTab;
