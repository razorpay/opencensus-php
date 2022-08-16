import React, { useState, useRef, useEffect, useCallback } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import Tabs, { Tab, TabPane } from 'common/ui/ReactTabs';
import MetricsCard from '../components/MetricsCard';
import GraphPanel from '../components/GraphPanel';

import debounce from 'common/utils/debounce';
import { setActiveTab, fetchSuccessRate, fetchMerchantErrors } from 'merchant/reducers/successRate';
import { queryFilters, getMerchantErrorsPayload } from '../helper';

const GraphWidget = (props) => {
  const {
    isLoading,
    tabLoading,
    activeTab,
    setActiveTab,
    metrics,
    fetchSuccessRate,
    fetchMerchantErrors,
  } = props;

  const tabContainerRef = useRef(null);
  const [tabWidth, setTabWidth] = useState();
  const tabPane = Object.values(metrics);

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
    setActiveTab(tab.name);
    const payload = queryFilters();
    fetchSuccessRate(payload);
    const errorsPaylod = getMerchantErrorsPayload();
    fetchMerchantErrors(errorsPaylod);
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
              <GraphPanel isLoading={isLoading || tabLoading} />
            </TabPane>
          );
        })}
      </Tabs>
    </div>
  );
};

const mapStateToProps = ({ successRate }) => {
  const { isLoading, tabLoading, activeTab, metrics } = successRate;
  return { activeTab, isLoading, tabLoading, metrics };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ setActiveTab, fetchSuccessRate, fetchMerchantErrors }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(GraphWidget);
