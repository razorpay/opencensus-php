import { NavLink } from 'react-router-dom';
import { connect } from 'react-redux';
import TestModeBanner from 'merchant/containers/TestModeBanner';

import LogList from './Logs/List';
import GenerateReportPanel from './GenerateReportPanel';

@connect(state => ({
  user: state.session.user,
}))
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
    this.props.fetchConfigs();
  }

  onGenerateReport = payload => {
    return this.props.createLog({
      ...payload,
      generated_by: this.props.user.current,
    });
  };

  render() {
    const { logs, config, user, configs } = this.props;
    return (
      <div>
        <tabbed-container>
          <header>
            <NavLink to="/reports">Reports</NavLink>
          </header>
          <TestModeBanner />
          <content>
            <div class="content-wrapper">
              <GenerateReportPanel
                configs={configs}
                onGenerateReport={this.onGenerateReport}
              />
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
