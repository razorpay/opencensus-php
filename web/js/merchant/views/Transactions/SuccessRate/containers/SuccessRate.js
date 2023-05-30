import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import Alert from 'common/ui/Forms/Alert';
// eslint-disable-next-line no-restricted-imports
import HeaderAction from 'common/ui/HeaderAction';
import ShowWhen from 'merchant/components/ShowWhen';
import SuccessRateFilter from 'merchant/views/Transactions/SuccessRate/components/SuccessRateFilter';
import GraphWidget from './GraphWidget';
import VolumePieWidget from './VolumePieWidget';
import FailureReasonsWidget from './FailureReasonsWidget';

import {
  fetchSuccessRate,
  fetchMerchantErrors,
  resetSRDashboard,
} from 'merchant/reducers/successRate';
import {
  queryFilters,
  getMerchantErrorsPayload,
} from 'merchant/views/Transactions/SuccessRate/helper';
import { OPTIMIZER_FAQ_DOC_LINK } from 'merchant/views/Transactions/SuccessRate/constants';

import {
  trackSuccessRateEvents,
  needHelpFaq,
} from 'merchant/views/Transactions/SuccessRate/trackEvents';

const SuccessRate = (props) => {
  const { activeTab, tabs, fetchSuccessRate, fetchMerchantErrors, resetSRDashboard } = props;
  const { error } = tabs[activeTab];

  const fetchData = () => {
    const payload = queryFilters();
    const errorsPaylod = getMerchantErrorsPayload();

    fetchSuccessRate({ payload });
    fetchMerchantErrors(errorsPaylod);
  };

  useEffect(() => {
    fetchData();

    return () => {
      resetSRDashboard();
    };
  }, []);

  return (
    <div className="sr-dashboard" data-testid="success-rate-container">
      <ErrorBoundary resetOnProps>
        <ShowWhen additionalCondition={(_user) => _user.isOptimizerEnabled}>
          <HeaderAction responsive>
            <div className="btn-toolbar pull-right">
              <a
                className="btn btn-link"
                href={OPTIMIZER_FAQ_DOC_LINK}
                target="_blank"
                rel="noopener noreferrer"
                role="link"
                onClick={() =>
                  trackSuccessRateEvents(needHelpFaq({ docLink: OPTIMIZER_FAQ_DOC_LINK }))
                }
              >
                <i className="i i-lightbulb" /> Need help?
              </a>
            </div>
          </HeaderAction>
        </ShowWhen>
        <SuccessRateFilter />
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
  return bindActionCreators(
    {
      fetchSuccessRate,
      fetchMerchantErrors,
      resetSRDashboard,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(SuccessRate);
