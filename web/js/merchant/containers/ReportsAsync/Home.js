import { connect } from 'react-redux';
import Reports from 'merchant_common/containers/ReportsAsync/Home';

import { pickProps } from 'common/utils/rzp-utils';
import {
  fetchMerchantReportLogs as fetchLogs,
  createMerchantReportLog as createLog,
  loadMoreMerchantLogs as loadMore,
} from 'merchant/reducers/reports/logs';
import { fetchMerchantConfigs as fetchConfigs } from 'merchant/reducers/reports/configs';

const mapStateToProps = state => ({
  ...state.merchantReports,
  user: pickProps(state.session.user, ['current']),
});

export default connect(mapStateToProps, {
  fetchLogs,
  fetchConfigs,
  createLog,
  loadMore,
})(Reports);
