import React, { useEffect } from 'react';

import { useSplitzService } from 'common/splitz';
import ReasonsPanel from 'merchant/views/Transactions/v1/SuccessRate/components/ReasonsPanel';
import {
  ERROR_CATEGORIES,
  ERROR_CATEGORIES_VS_DISPLAY_TEXT,
} from 'merchant/views/Transactions/v1/SuccessRate/constants';
import {
  methodFailureReasonClick,
  trackSuccessRateEvents,
} from 'merchant/views/Transactions/v1/SuccessRate/trackEvents';

import TabContent from './TabContent';
import TabPane from './TabPane';

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
  const splitz = useSplitzService();
  const selectedKey = Object.values(ERROR_CATEGORIES)[selectedTab];
  const title = ERROR_CATEGORIES_VS_DISPLAY_TEXT[selectedKey] ?? '--';
  const content = tabData?.[selectedKey] ?? [];

  useEffect(() => {
    trackSuccessRateEvents(methodFailureReasonClick({ tabName: selectedKey }), splitz);
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
