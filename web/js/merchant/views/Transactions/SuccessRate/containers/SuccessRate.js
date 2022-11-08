import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import Alert from 'common/ui/Forms/Alert';
import HeaderAction from 'common/ui/HeaderAction';
import ShowWhen from 'merchant/components/ShowWhen';
import SuccessRateFilter from 'merchant/views/Transactions/SuccessRate/components/SuccessRateFilter';
import GraphWidget from './GraphWidget';
import VolumePieWidget from './VolumePieWidget';
import FailureReasonsWidget from './FailureReasonsWidget';

import { fetchSuccessRate, fetchMerchantErrors } from 'merchant/reducers/successRate';
import {
  queryFilters,
  getMerchantErrorsPayload,
} from 'merchant/views/Transactions/SuccessRate/helper';
import {
  trackSuccessRateEvents,
  needHelpFaq,
} from 'merchant/views/Transactions/SuccessRate/trackEvents';

const SuccessRate = (props) => {
  const { activeTab, tabs, fetchSuccessRate, fetchMerchantErrors } = props;
  const { error } = tabs[activeTab];

  const docLink = 'https://razorpay.com/docs/payments/optimizer/success-rate';

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
        <ShowWhen additionalCondition={(_user) => _user.isOptimizerEnabled}>
          <HeaderAction responsive>
            <div className="btn-toolbar pull-right">
              <a
                className="btn btn-link"
                href={docLink}
                target="_blank"
                rel="noopener noreferrer"
                role="link"
                onClick={() => trackSuccessRateEvents(needHelpFaq({ docLink }))}
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
  return bindActionCreators({ fetchSuccessRate, fetchMerchantErrors }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(SuccessRate);
