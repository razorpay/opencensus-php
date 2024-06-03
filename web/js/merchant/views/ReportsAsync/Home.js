import { connect } from 'react-redux';

import { pickProps, uniqueArray } from 'common/utils/rzp-utils';
import { fetchAccounts } from 'merchant/reducers/marketplace/accounts';
import { fetchMerchantConfigs as fetchConfigs } from 'merchant/reducers/reports/configs';
import {
  fetchMerchantReportLogs as fetchLogs,
  createMerchantReportLog as createLog,
  pollMerchantReportLog as pollLog,
} from 'merchant/reducers/reports/logs';
import Reports from 'merchant_common/containers/ReportsAsync/Home';
import { prefixEntityValue } from 'merchant_common/helpers/data';

const mapStateToProps = (state) => {
  const sessionUser = state.session.user;

  const { email, contact_email, transaction_report_email } = sessionUser;
  const emailReportOptions = [
    email,
    contact_email,
    ...(transaction_report_email ? transaction_report_email.split(',') : []),
  ];

  const customConfigs = getCustomConfigs(sessionUser);

  const showSelectAccount = sessionUser.isMarketplaceEnabled;

  const defaultAccount = {
    name: sessionUser.name || sessionUser.user.name,
    id: prefixEntityValue('account', sessionUser.current),
    email: sessionUser.email,
    tag: 'My Account',
    tagIcon: 'i-account',
    current: true,
  };

  const accounts = showSelectAccount ? getAccounts(state.accounts, defaultAccount) : undefined;

  return {
    ...state.merchantReports,
    user: pickProps(sessionUser, ['current', 'international']),
    emailReportOptions: uniqueArray(emailReportOptions),
    mode: state.session.mode,
    showSelectAccount,
    accounts,
    customConfigs,
  };
};

export default connect(mapStateToProps, {
  fetchLogs,
  fetchConfigs,
  fetchAccounts,
  createLog,
  pollLog,
})(Reports);

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

function getCustomConfigs(sessionUser) {
  const customConfigs = [];
  /* istanbul ignore else */
  if (sessionUser.isOrgAllowedFunctionality('monthlyInvoice')) {
    customConfigs.push(customConfigMap.monthlyInvoice);
  }
  /* istanbul ignore else */
  if (sessionUser.findTag('borking_report')) {
    customConfigs.push(customConfigMap.broking);
  }
  /* istanbul ignore else */
  if (sessionUser.findTag('rpp_report')) {
    customConfigs.push(customConfigMap.rpp_report);
  }
  /* istanbul ignore else */
  if (sessionUser.findTag('dsp_report')) {
    customConfigs.push(customConfigMap.dsp_report);
  }

  return customConfigs;
}

function getAccounts(accountsData, defaultAccount) {
  const { accounts, loading } = accountsData;
  return loading
    ? accountsData
    : {
        ...accountsData,
        accounts: [defaultAccount, ...accounts],
      };
}
