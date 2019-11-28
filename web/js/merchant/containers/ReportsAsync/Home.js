import { connect } from 'react-redux';
import Reports from 'merchant_common/containers/ReportsAsync/Home';

const mapStateToProps = state => ({
  ...state.reportsAsync,
});

export default connect(mapStateToProps)(Reports);
