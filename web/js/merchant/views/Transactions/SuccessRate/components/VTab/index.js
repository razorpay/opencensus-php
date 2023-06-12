import React, { useEffect } from 'react';
import {
  ERROR_CATEGORIES,
  ERROR_CATEGORIES_VS_DISPLAY_TEXT,
} from 'merchant/views/Transactions/SuccessRate/constants';
import TabPane from './TabPane';
import TabContent from './TabContent';
import ReasonsPanel from 'merchant/views/Transactions/SuccessRate/components/ReasonsPanel';
import {
  methodFailureReasonClick,
  trackSuccessRateEvents,
} from 'merchant/views/Transactions/SuccessRate/trackEvents';

function VTab(props) {
  const {
    selectedTab,
    isLoading,
    onTabChange,
    ariaLabel,
    tabData,
    tab,
    toggleOption,
    enabledToggleOption,
    toggleErrorType,
  } = props;
  const selectedKey = Object.values(ERROR_CATEGORIES)[selectedTab];
  const title = ERROR_CATEGORIES_VS_DISPLAY_TEXT[selectedKey] ?? '--';
  const content = tabData?.[selectedKey] ?? [];

  useEffect(() => {
    trackSuccessRateEvents(methodFailureReasonClick({ tabName: selectedKey }));
  }, [selectedKey]);

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
          tab={tab}
          heading={`Top payment failure reasons: ${title}`}
          data={content}
          panelData={tabData}
          toggleOption={toggleOption}
          enabledToggleOption={enabledToggleOption}
          toggleErrorType={toggleErrorType}
        />
      </TabContent>
    </div>
  );
}

export default VTab;
