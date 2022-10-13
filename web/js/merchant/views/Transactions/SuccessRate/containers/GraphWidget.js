import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { getUser } from 'merchant/store';
import Tabs, { Tab, TabPane } from 'common/ui/ReactTabs';
import MetricsCard from 'merchant/views/Transactions/SuccessRate/components/MetricsCard';
import GraphPanel from 'merchant/views/Transactions/SuccessRate/components/GraphPanel';
import MethodFilter from 'merchant/views/Transactions/SuccessRate/components/MethodFilter';

import {
  setActiveTab,
  fetchSuccessRate,
  fetchMerchantErrors,
  setGroupTypeFilter,
  setSelectedDropdownFilterOptions,
  setDefaultInterval,
  setCardTypeFilter,
} from 'merchant/reducers/successRate';
import {
  queryFilters,
  getMerchantErrorsPayload,
  getBreakdownInterval,
} from 'merchant/views/Transactions/SuccessRate/helper';
import {
  methodTabClick,
  methodDropdownChange,
  trackSuccessRateEvents,
} from 'merchant/views/Transactions/SuccessRate/trackEvents';
import { INITIAL_SELECTED_CARD_TYPE } from 'merchant/views/Transactions/SuccessRate/constants';

const GraphWidget = (props) => {
  const {
    successRate,
    setActiveTab,
    fetchSuccessRate,
    fetchMerchantErrors,
    setGroupTypeFilter,
    setSelectedDropdownFilterOptions,
    setDefaultInterval,
    setCardTypeFilter,
  } = props;
  const {
    isLoading,
    tabLoading,
    activeTab,
    metrics,
    tabs,
    isDropdownFilterLoading,
    filters,
  } = successRate;

  const tabPane = Object.values(metrics);

  const handleTabChange = (tabIndex) => {
    const { startDate, endDate } = filters || {};
    const tab = tabPane[tabIndex];

    if (tab.name === activeTab) return;

    setActiveTab(tab.name);
    setDefaultInterval(getBreakdownInterval(startDate, endDate));
    if (tab.name === 'Card') {
      setCardTypeFilter(INITIAL_SELECTED_CARD_TYPE);
    }

    const updateDropdownOptions = tab.name != 'Overall';
    const payload = queryFilters(updateDropdownOptions);
    fetchSuccessRate({ payload, updateDropdownOptions });
    const errorsPaylod = getMerchantErrorsPayload(updateDropdownOptions);
    fetchMerchantErrors(errorsPaylod);

    trackSuccessRateEvents(methodTabClick({ tabName: tab.name }));
  };

  const handleGroupingChange = ({ option }) => {
    const user = getUser();
    setSelectedDropdownFilterOptions(option);
    !user?.isOptimizerEnabled && setGroupTypeFilter(option?.value);
    fetchSuccessRate({ payload: queryFilters(), resetSelectedInterval: false });
    if (user?.isOptimizerEnabled) {
      fetchMerchantErrors(getMerchantErrorsPayload());
    }
    trackSuccessRateEvents(
      methodDropdownChange({ tabName: activeTab, optionSelected: option?.value }),
    );
  };

  return (
    <div className="metrics-container">
      <Tabs
        className="sr-metrics"
        justified={true}
        onSelect={handleTabChange}
        selectedTabIndex={tabPane.findIndex(({ name }) => name === activeTab)}
      >
        {tabPane.map((tab, idx) => {
          return (
            <Tab key={`${tab.name}-${idx}`}>
              <MetricsCard isLoading={isLoading} isActive={activeTab === tab.name} metric={tab} />
            </Tab>
          );
        })}

        {tabPane.map((tab, idx) => {
          const { dropdownFilterOptions = [], selectedDropdownFilterOptions } = tabs?.[activeTab];
          return (
            <TabPane key={`${tab.name}-${idx}`}>
              {!isDropdownFilterLoading && (
                <MethodFilter
                  activeTab={activeTab}
                  disabled={isLoading || tabLoading}
                  filtersList={dropdownFilterOptions}
                  handleGroupingChange={handleGroupingChange}
                  selectedGrouping={selectedDropdownFilterOptions}
                />
              )}
              <GraphPanel />
            </TabPane>
          );
        })}
      </Tabs>
    </div>
  );
};

const mapStateToProps = ({ successRate }) => ({ successRate });

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      setActiveTab,
      setGroupTypeFilter,
      fetchSuccessRate,
      fetchMerchantErrors,
      setSelectedDropdownFilterOptions,
      setDefaultInterval,
      setCardTypeFilter,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(GraphWidget);
