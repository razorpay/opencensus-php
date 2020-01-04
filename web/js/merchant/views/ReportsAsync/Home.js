import { connect } from 'react-redux';
import Reports from 'merchant_common/containers/ReportsAsync/Home';

import { pickProps } from 'common/utils/rzp-utils';
import { fetchLogs } from 'merchant/reducers/reports/logs';

const mapStateToProps = state => ({
  ...state.reportsAsync,
  user: pickProps(state.session.user, ['current']),
});

export default connect(mapStateToProps, { fetchLogs })(Reports);
