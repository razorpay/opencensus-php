import React, { useCallback, useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { fetchMerchantErrors, setFailureReasonType } from 'merchant/reducers/successRate';
import VTab from 'merchant/views/Transactions/v1/SuccessRate/components/VTab';
import { CUSTOM_ERROR_TYPES } from 'merchant/views/Transactions/v1/SuccessRate/constants';
import { getMerchantErrorsPayload } from 'merchant/views/Transactions/v1/SuccessRate/helper';

const DEFAULT_ACTIVE_TAB = 0;

const FailureReasonsWidget = (props) => {
  const [activeTab, setActiveTab] = useState(DEFAULT_ACTIVE_TAB);
  const {
    isLoadingMerchantErrors,
    tab,
    currentTab,
    dropDownFilters,
    fetchMerchantErrors,
    failureReasonType,
    setFailureReasonType,
    failureReasons,
  } = props;

  const toggleOption = CUSTOM_ERROR_TYPES?.[currentTab] ?? null;
  const showtoggleOption = toggleOption && toggleOption?.additionalCondition(dropDownFilters);

  useEffect(() => {
    if (isLoadingMerchantErrors && !failureReasons?.default) {
      setActiveTab(DEFAULT_ACTIVE_TAB);
    }
  }, [isLoadingMerchantErrors, failureReasons]);

  useEffect(() => setActiveTab(DEFAULT_ACTIVE_TAB), [currentTab]);

  const handleTabChange = useCallback((tab) => setActiveTab(tab), []);

  const handleToggleChange = () => {
    if (failureReasonType !== 'default') {
      setFailureReasonType('default');
    } else {
      const { key, merchantErrorFetchOptions } = toggleOption;
      setFailureReasonType(key);
      const errorsPaylod = getMerchantErrorsPayload(false, merchantErrorFetchOptions);
      fetchMerchantErrors(errorsPaylod, key);
    }
  };

  return (
    <div className="box-widget reasons-container" data-testid="failure-reasons-widget">
      <VTab
        ariaLabel="Vertical Tabs"
        selectedTab={activeTab}
        isLoading={isLoadingMerchantErrors}
        onTabChange={handleTabChange}
        tabData={failureReasons?.[failureReasonType] ?? []}
        tab={tab}
        enabledToggleOption={failureReasonType}
        toggleOption={showtoggleOption ? toggleOption : null}
        toggleErrorType={showtoggleOption ? handleToggleChange : null}
      />
    </div>
  );
};

const mapStateToProps = ({ successRate }) => {
  const { isLoadingMerchantErrors, merchantErrors, activeTab, tabs } = successRate;
  return {
    isLoadingMerchantErrors,
    tab: tabs[activeTab],
    currentTab: activeTab,
    dropDownFilters: tabs[activeTab]?.selectedDropdownFilterOptions,
    failureReasonType: merchantErrors[activeTab]?.failureReasonType,
    failureReasons: merchantErrors?.[activeTab]?.failures,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchMerchantErrors,
      setFailureReasonType,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(FailureReasonsWidget);
