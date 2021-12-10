import { connect } from 'react-redux';
import Reports from 'merchant_common/containers/ReportsAsync/Home';

import { pickProps, uniqueArray } from 'common/utils/rzp-utils';

import {
  fetchPartnerReportLogs as fetchLogs,
  createPartnerReportLog as createLog,
  pollPartnerReportLog as pollLog,
} from 'merchant/reducers/reports/logs';
import { fetchPartnerConfigs as fetchConfigs } from 'merchant/reducers/reports/configs';

const mapStateToProps = (state) => {
  const sessionUser = state.session.user;

  const { email, contact_email, transaction_report_email } = sessionUser;

  const emailReportOptions = [
    email,
    contact_email,
    ...(transaction_report_email ? transaction_report_email.split(',') : []),
  ];

  return {
    ...state.partnerReports,
    user: pickProps(sessionUser, ['current', 'international']),
    emailReportOptions: uniqueArray(emailReportOptions),
    mode: state.session.mode,
    showSelectAccount: false,
    onlyDailyOptionsInReferredAccounts: true,
  };
};

export default connect(mapStateToProps, {
  fetchLogs,
  fetchConfigs,
  createLog,
  pollLog,
})(Reports);
