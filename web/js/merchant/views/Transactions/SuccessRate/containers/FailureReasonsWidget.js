import React, { useCallback, useEffect } from 'react';
import { connect } from 'react-redux';
import VTab from 'merchant/views/Transactions/SuccessRate/components/VTab';

const DEFAULT_ACTIVE_TAB = 0;

const FailureReasonsWidget = (props) => {
  const [activeTab, setActiveTab] = React.useState(DEFAULT_ACTIVE_TAB);
  const { isLoadingMerchantErrors, merchantErrors } = props;

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
      />
    </div>
  );
};

const mapStateToProps = ({ successRate }) => {
  const { isLoadingMerchantErrors, merchantErrors } = successRate;
  return { isLoadingMerchantErrors, merchantErrors };
};

export default connect(mapStateToProps, null)(FailureReasonsWidget);
