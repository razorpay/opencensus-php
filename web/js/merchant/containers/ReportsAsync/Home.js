import { connect } from 'react-redux';
import Reports from 'merchant_common/containers/ReportsAsync/Home';

import { fetchLogs } from 'merchant/reducers/reports/logs';

const mapStateToProps = state => ({
  ...state.reportsAsync,
});

export default connect(mapStateToProps, { fetchLogs })(Reports);
