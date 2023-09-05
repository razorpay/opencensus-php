import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { useSplitzService } from 'common/splitz';
import Alert from 'common/ui/Forms/Alert';
// eslint-disable-next-line no-restricted-imports
import HeaderAction from 'common/ui/HeaderAction';
import ShowWhen from 'merchant/components/ShowWhen';
import {
  fetchSuccessRate,
  fetchMerchantErrors,
  resetSRDashboard,
} from 'merchant/reducers/successRate';
import SuccessRateFilter from 'merchant/views/Transactions/v1/SuccessRate/components/SuccessRateFilter';
import { OPTIMIZER_FAQ_DOC_LINK } from 'merchant/views/Transactions/v1/SuccessRate/constants';
import {
  queryFilters,
  getMerchantErrorsPayload,
} from 'merchant/views/Transactions/v1/SuccessRate/helper';
import {
  trackSuccessRateEvents,
  needHelpFaq,
} from 'merchant/views/Transactions/v1/SuccessRate/trackEvents';
import { headerActionTarget } from 'merchant/views/Transactions/v2/common/constants';

import FailureReasonsWidget from './FailureReasonsWidget';
import GraphWidget from './GraphWidget';
import VolumePieWidget from './VolumePieWidget';

const SuccessRate = (props) => {
  const splitz = useSplitzService();
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
          <HeaderAction responsive target={headerActionTarget}>
            <div className="btn-toolbar pull-right">
              <a
                className="btn btn-link"
                href={OPTIMIZER_FAQ_DOC_LINK}
                target="_blank"
                rel="noopener noreferrer"
                role="link"
                onClick={() =>
                  trackSuccessRateEvents(needHelpFaq({ docLink: OPTIMIZER_FAQ_DOC_LINK }), splitz)
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

const mapStateToProps = ({ successRate, session }) => {
  const { user } = session;
  const { activeTab, tabs } = successRate;
  return { user, activeTab, tabs };
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
