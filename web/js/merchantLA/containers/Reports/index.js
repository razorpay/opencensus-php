import { connect } from 'react-redux';

import Reports from 'merchant_common/containers/ReportsAsync/Home';

import { pickProps, uniqueArray } from 'common/utils/rzp-utils';

import {
  fetchLogs,
  createLog,
  pollLog,
} from 'merchantLA/reducers/reports/logs';

import { fetchConfigs } from 'merchantLA/reducers/reports/configs';

const mapStateToProps = state => {
  const sessionUser = state.session.user;

  const { email, contact_email, transaction_report_email } = sessionUser;

  const emailReportOptions = [
    email,
    contact_email,
    ...(transaction_report_email ? transaction_report_email.split(',') : []),
  ];

  return {
    ...state.reports,
    user: pickProps(sessionUser, ['current']),
    emailReportOptions: uniqueArray(emailReportOptions),
    mode: state.session.moide,
    showSelectAccount: false,
    onlyDailyOptionsInReferredAccounts: false,
  };
};

export default connect(mapStateToProps, {
  fetchLogs,
  fetchConfigs,
  createLog,
  pollLog,
})(Reports);
