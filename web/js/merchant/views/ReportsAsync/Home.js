import { connect } from 'react-redux';
import Reports from 'merchant_common/containers/ReportsAsync/Home';

import { pickProps, uniqueArray } from 'common/utils/rzp-utils';
import {
  fetchMerchantReportLogs as fetchLogs,
  createMerchantReportLog as createLog,
  loadMoreMerchantLogs as loadMore,
  pollMerchantReportLog as pollLog,
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

  const customConfigs = getCustomConfigs(sessionUser);

  return {
    ...state.merchantReports,
    user: pickProps(sessionUser, ['current']),
    emailReportOptions: uniqueArray(emailReportOptions),
    mode: state.session.mode,
    customConfigs,
  };
};

export default connect(mapStateToProps, {
  fetchLogs,
  fetchConfigs,
  createLog,
  loadMore,
  pollLog,
})(Reports);

function getCustomConfigs(sessionUser) {
  const customConfigs = [];

  if (sessionUser.isOrgAllowedFunctionality('monthlyInvoice')) {
    customConfigs.push(customConfigMap['monthlyInvoice']);
  }

  if (sessionUser.findTag('borking_report')) {
    customConfigs.push(customConfigMap['broking']);
  }

  if (sessionUser.findTag('rpp_report')) {
    customConfigs.push(customConfigMap['rpp_report']);
  }

  if (sessionUser.findTag('dsp_report')) {
    customConfigs.push(customConfigMap['dsp_report']);
  }

  return customConfigs;
}

const customConfigMap = {
  monthlyInvoice: {
    name: 'Monthly Invoice',
    type: 'custom',
    id: 'invoice',
  },

  dsp_report: {
    name: 'DSP Transaction Report',
    type: 'custom',
    id: 'dsp_report',
  },

  broking: {
    name: 'Broking Report',
    type: 'custom',
    id: 'broking',
  },

  rpp_report: {
    name: 'e-Mitra Report',
    type: 'custom',
    id: 'rpp_report',
  },
};
