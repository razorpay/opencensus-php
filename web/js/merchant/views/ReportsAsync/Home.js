import { connect } from 'react-redux';
import Reports from 'merchant_common/containers/ReportsAsync/Home';

import { pickProps } from 'common/utils/rzp-utils';
import { fetchLogs, createLog } from 'merchant/reducers/reports/logs';
import { fetchConfigs } from 'merchant/reducers/reports/configs';

const mapStateToProps = state => ({
  ...state.reportsAsync,
  user: pickProps(state.session.user, ['current']),
});

export default connect(mapStateToProps, { fetchLogs, fetchConfigs, createLog })(
  Reports
);
