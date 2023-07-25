import React from 'react';
import moment from 'moment';
import useLocalStorage from 'merchant/utils/useLocalStorage';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { getUser } from 'merchant/store';

import Tabs, { Tab, TabPane } from 'common/ui/ReactTabs';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { classList } from 'common/utils/rzp-utils';

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
  setFailureReasonType,
} from 'merchant/reducers/successRate';
import {
  queryFilters,
  getMerchantErrorsPayload,
  getBreakdownInterval,
  getTabsPane,
} from 'merchant/views/Transactions/SuccessRate/helper';
import {
  methodTabClick,
  methodDropdownChange,
  trackSuccessRateEvents,
} from 'merchant/views/Transactions/SuccessRate/trackEvents';
import { DEFAULT_GROUP_BY } from 'merchant/views/Transactions/SuccessRate/constants';
import { StyledContainer } from 'merchant/views/Transactions/SuccessRate/styles';

const GraphWidget = (props) => {
  const {
    successRate,
    setActiveTab,
    fetchSuccessRate,
    fetchMerchantErrors,
    setGroupTypeFilter,
    setSelectedDropdownFilterOptions,
    setDefaultInterval,
    user,
    setFailureReasonType,
  } = props;
  const { isLoading, tabLoading, activeTab, metrics, tabs, isDropdownFilterLoading, filters } =
    successRate;
  const tabPane = getTabsPane(metrics);

  const [isSRDashboardFirstTime, setIsSRDashboardFirstTime] = useLocalStorage(
    `isSRDashboardFirstTime_${user?.current}`,
    true,
  );
  const handleGotItClick = () => {
    setIsSRDashboardFirstTime(false);
  };

  const handleTabChange = (tabIndex) => {
    const { startDate, endDate } = filters || {};
    const tab = tabPane[tabIndex];
    const lastUpdatedAt = tabs?.[tab.name]?.lastUpdatedAt;
    const diffInSec = lastUpdatedAt ? moment().diff(moment.unix(lastUpdatedAt), 'seconds') : 0;
    const updateDropdownOptions = tab.name !== 'Overall';

    if (tab.name === activeTab) return;

    setActiveTab(tab.name);
    setDefaultInterval(getBreakdownInterval(startDate, endDate));

    if (!lastUpdatedAt || diffInSec >= 300) {
      if (tab.name === 'Card') {
        setGroupTypeFilter(DEFAULT_GROUP_BY[tab.name]);
      }

      const payload = queryFilters(updateDropdownOptions);
      fetchSuccessRate({ payload, updateDropdownOptions });
    }

    const errorsPaylod = getMerchantErrorsPayload(updateDropdownOptions);
    fetchMerchantErrors(errorsPaylod);

    trackSuccessRateEvents(methodTabClick({ tabName: tab.name }));
  };

  const handleGroupingChange = ({ option }) => {
    const user = getUser();
    const updateDropdownOptions = activeTab !== 'Overall';
    setFailureReasonType('default');
    setSelectedDropdownFilterOptions(option);

    !user?.isOptimizerEnabled && setGroupTypeFilter(option?.value);
    fetchSuccessRate({
      payload: queryFilters(updateDropdownOptions),
      resetSelectedInterval: false,
    });
    if (user?.isOptimizerEnabled) {
      fetchMerchantErrors(getMerchantErrorsPayload());
    }
    trackSuccessRateEvents(
      methodDropdownChange({ tabName: activeTab, optionSelected: option?.value }),
    );
  };

  return (
    <StyledContainer data-testid="success-rate-graph-widget">
      <Tabs
        className="sr-metrics"
        justified={true}
        onSelect={handleTabChange}
        selectedTabIndex={tabPane.findIndex(({ name }) => name === activeTab)}
      >
        {tabPane.map((tab, idx) => {
          return (
            <Tab
              key={`${tab.name}-${idx}`}
              data-testid={`${tab.name}-tab`}
              className={classList((isLoading || tabLoading) && 'loading')}
            >
              <MetricsCard
                isLoading={isLoading}
                tabLoading={tabLoading}
                isActive={activeTab === tab.name}
                metric={tab}
              />
              {!isLoading && isSRDashboardFirstTime && tab?.name === 'Card' && (
                <Popover persistent={true} theme="dark" align="bottom">
                  <PopoverBody>
                    <p>
                      To filter via various payment gateways, click on any of the above payment
                      methods - UPI, Netbanking or Cards.
                    </p>
                    <div className="clearfix">
                      <b className="pull-right" onClick={handleGotItClick}>
                        Got it
                      </b>
                    </div>
                  </PopoverBody>
                </Popover>
              )}
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
                  tab={tabs?.[activeTab]}
                  disabled={isLoading || tabLoading}
                  filtersList={dropdownFilterOptions}
                  handleGroupingChange={handleGroupingChange}
                  isOptimizerEnabled={user.isOptimizerEnabled}
                  selectedGrouping={selectedDropdownFilterOptions}
                  isInternationalEnabled={user.international}
                  user={user}
                />
              )}
              <GraphPanel />
            </TabPane>
          );
        })}
      </Tabs>
    </StyledContainer>
  );
};

const mapStateToProps = ({ successRate, session }) => ({ successRate, user: session?.user });

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      setActiveTab,
      setGroupTypeFilter,
      fetchSuccessRate,
      fetchMerchantErrors,
      setSelectedDropdownFilterOptions,
      setDefaultInterval,
      setFailureReasonType,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(GraphWidget);
