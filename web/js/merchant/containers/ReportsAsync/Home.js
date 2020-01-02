import { connect } from 'react-redux';
import Reports from 'merchant_common/containers/ReportsAsync/Home';

import { pickProps, uniqueArray } from 'common/utils/rzp-utils';
import {
  fetchMerchantReportLogs as fetchLogs,
  createMerchantReportLog as createLog,
  loadMoreMerchantLogs as loadMore,
} from 'merchant/reducers/reports/logs';
import { fetchMerchantConfigs as fetchConfigs } from 'merchant/reducers/reports/configs';

const mapStateToProps = state => {
  const sessionUser = state.session.user;

  const { email, contact_email, transaction_report_email } = sessionUser;
  const emailReportOptions = [
    email,
    contact_email,
    ...(transaction_report_email ? transaction_report_email.split(',') : []),
  ];
  return {
    ...state.merchantReports,
    user: pickProps(sessionUser, ['current']),
    emailReportOptions: uniqueArray(emailReportOptions),
  };
};

export default connect(mapStateToProps, {
  fetchLogs,
  fetchConfigs,
  createLog,
  loadMore,
})(Reports);
