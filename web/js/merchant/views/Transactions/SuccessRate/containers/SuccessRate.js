import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import Alert from 'common/ui/Forms/Alert';
import SucessRateFilter from '../components/SucessRateFilter';
import GraphWidget from './GraphWidget';
import VolumePieWidget from './VolumePieWidget';
import FailureReasonsWidget from './FailureReasonsWidget';

import { fetchSuccessRate, fetchMerchantErrors } from 'merchant/reducers/successRate';
import { queryFilters, getMerchantErrorsPayload } from '../helper';

const SuccessRate = (props) => {
  const { activeTab, tabs, fetchSuccessRate, fetchMerchantErrors } = props;
  const { error } = tabs[activeTab];

  const fetchData = async () => {
    const payload = queryFilters();
    await fetchSuccessRate({ payload });
    const errorsPaylod = getMerchantErrorsPayload();
    await fetchMerchantErrors(errorsPaylod);
  };

  useEffect(() => fetchData(), []);

  return (
    <div className="sr-dashboard">
      <ErrorBoundary resetOnProps>
        <SucessRateFilter />
        {error && <Alert iconBefore="i-comment-info" type="error" message={error} showDismiss />}
        <GraphWidget />
        <VolumePieWidget />
        <FailureReasonsWidget />
      </ErrorBoundary>
    </div>
  );
};

const mapStateToProps = ({ successRate }) => {
  const { activeTab, tabs } = successRate;
  return { activeTab, tabs };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ fetchSuccessRate, fetchMerchantErrors }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(SuccessRate);
