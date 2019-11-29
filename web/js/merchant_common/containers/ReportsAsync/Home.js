import { NavLink } from 'react-router-dom';
import TestModeBanner from 'merchant/containers/TestModeBanner';

import LogList from './Logs/List';

export default class ReportHome extends React.PureComponent {
  static defaultProps = {
    config: {
      id: 'config_edFWoe78RbLHwO',
      consumer: '100000Razorpay',
      report_type: 'merchant',
      type: 'transfers',
      scheduled: false,
      name: 'Transfers',
    },
  };

  componentDidMount() {
    this.props.fetchLogs();
  }

  render() {
    const { logs, config, user } = this.props;
    return (
      <div>
        <tabbed-container>
          <header>
            <NavLink to="/reports">Reports</NavLink>
          </header>
          <TestModeBanner />
          <content>
            <div class="content-wrapper">
              <LogList
                currentMerchantId={user.current}
                {...logs}
                config={config}
              />
            </div>
          </content>
        </tabbed-container>
      </div>
    );
  }
}
