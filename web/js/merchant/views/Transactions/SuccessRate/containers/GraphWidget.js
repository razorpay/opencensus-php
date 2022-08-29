import React, { useState, useRef, useEffect, useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { analyticsTrack } from 'common/utils/analytics';
import Tabs, { Tab, TabPane } from 'common/ui/ReactTabs';
import MetricsCard from '../components/MetricsCard';
import GraphPanel from '../components/GraphPanel';
import MethodFilter from '../components/MethodFilter';

import debounce from 'common/utils/debounce';
import {
  setActiveTab,
  fetchSuccessRate,
  fetchMerchantErrors,
  setGroupTypeFilter,
} from 'merchant/reducers/successRate';
import { queryFilters, getMerchantErrorsPayload, getInitialGroupings } from '../helper';
import { SR_FILTERS, DEFAULT_GROUP_BY } from '../constants';
import { methodTabClick } from '../ga';

const GraphWidget = (props) => {
  const {
    isLoading,
    tabLoading,
    activeTab,
    setActiveTab,
    metrics,
    fetchSuccessRate,
    fetchMerchantErrors,
    setGroupTypeFilter,
  } = props;

  const tabContainerRef = useRef(null);
  const [tabWidth, setTabWidth] = useState();
  const tabPane = Object.values(metrics);
  const methodFilters = SR_FILTERS; //optimizer filters to be added based on tag for optimizer sr dashboard.
  const initialGroupings = getInitialGroupings(methodFilters) ?? {};
  const [selectedGroupings, setSelectedGroupings] = useState(initialGroupings);

  const handleTabWidth = useCallback(
    debounce(() => {
      if (tabContainerRef?.current) {
        const containerWidth = tabContainerRef?.current?.clientWidth;
        const newTabWidth = (containerWidth - 16 * (tabPane.length - 1)) / tabPane.length; // 16 - gutter space between Tabs
        setTabWidth(newTabWidth);
      }
    }, 250),
    [],
  );

  const handleTabChange = (tab) => {
    if (tab.name === activeTab) return;
    setActiveTab(tab.name);
    setSelectedGroupings(initialGroupings);
    setGroupTypeFilter(initialGroupings[tab?.name]?.[0]?.value ?? DEFAULT_GROUP_BY?.[tab?.name]);
    const payload = queryFilters();
    fetchSuccessRate(payload);
    const errorsPaylod = getMerchantErrorsPayload();
    fetchMerchantErrors(errorsPaylod);

    analyticsTrack(methodTabClick({ tabName: tab.name }));
  };

  const handleGroupingChange = (tabName) => (index) => ({ option }) => {
    setSelectedGroupings((prevSelectedGroupings) => {
      const newState = {
        ...prevSelectedGroupings,
        [tabName]: [
          ...prevSelectedGroupings?.[tabName]?.slice(0, index),
          option,
          ...prevSelectedGroupings?.[tabName]?.slice(index + 1),
        ],
      };
      return newState;
    });
    setGroupTypeFilter(option?.value);
    fetchSuccessRate(queryFilters());
  };

  useEffect(() => {
    handleTabWidth();
    window?.addEventListener('resize', handleTabWidth);
    return () => {
      window?.removeEventListener('resize', handleTabWidth);
    };
  }, [handleTabWidth]);

  return (
    <div ref={tabContainerRef} className="metrics-container">
      <Tabs className="sr-metrics" justified={true}>
        {tabPane.map((tab, idx) => {
          return (
            <Tab
              key={`${tab.name}-${idx}`}
              onClick={() => handleTabChange(tab)}
              style={{ width: tabWidth }}
            >
              <MetricsCard isLoading={isLoading} isActive={activeTab === tab.name} metric={tab} />
            </Tab>
          );
        })}

        {tabPane.map((tab, idx) => {
          return (
            <TabPane key={`${tab.name}-${idx}`}>
              <MethodFilter
                activeTab={activeTab}
                disabled={isLoading || tabLoading}
                filtersList={methodFilters[activeTab] ?? []}
                handleGroupingChange={handleGroupingChange(tab?.name)}
                selectedGrouping={selectedGroupings?.[tab?.name]}
              />
              <GraphPanel />
            </TabPane>
          );
        })}
      </Tabs>
    </div>
  );
};

const mapStateToProps = ({ successRate }) => {
  const { isLoading, tabLoading, activeTab, metrics } = successRate;
  return { isLoading, tabLoading, activeTab, metrics };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    { setActiveTab, setGroupTypeFilter, fetchSuccessRate, fetchMerchantErrors },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(GraphWidget);
