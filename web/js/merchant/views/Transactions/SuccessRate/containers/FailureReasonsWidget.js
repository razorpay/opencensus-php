import React, { useCallback, useEffect } from 'react';
import { connect } from 'react-redux';
import VTab from 'merchant/views/Transactions/SuccessRate/components/VTab';

const DEFAULT_ACTIVE_TAB = 0;

const FailureReasonsWidget = (props) => {
  const [activeTab, setActiveTab] = React.useState(DEFAULT_ACTIVE_TAB);
  const { isLoadingMerchantErrors, merchantErrors, tab } = props;

  useEffect(() => setActiveTab(DEFAULT_ACTIVE_TAB), [isLoadingMerchantErrors]);

  const handleTabChange = useCallback((tab) => setActiveTab(tab), []);

  return (
    <div className="box-widget reasons-container">
      <VTab
        ariaLabel="Vertical Tabs"
        selectedTab={activeTab}
        isLoading={isLoadingMerchantErrors}
        onTabChange={handleTabChange}
        tabData={merchantErrors}
        tab={tab}
      />
    </div>
  );
};

const mapStateToProps = ({ successRate }) => {
  const { isLoadingMerchantErrors, merchantErrors, activeTab, tabs } = successRate;
  return { isLoadingMerchantErrors, merchantErrors, tab: tabs[activeTab] };
};

export default connect(mapStateToProps, null)(FailureReasonsWidget);
