import { NavLink } from 'react-router-dom';
import { connect } from 'react-redux';
import TestModeBanner from 'merchant/containers/TestModeBanner';

import LogList from './Logs/List';
import GenerateReportPanel from './GenerateReportPanel';

@connect(state => ({
  user: state.session.user,
}))
export default class ReportHome extends React.PureComponent {
  componentDidMount() {
    this.props.fetchConfigs();
    this.props.fetchLogs({ count: 5 });
  }

  onGenerateReport = payload => {
    return this.props.createLog({
      ...payload,
      generated_by: this.props.user.current,
    });
  };

  onLoadMoreLogs = () => {
    this.props.loadMore({ count: 5, skip: 5 });
  };

  render() {
    const { logs, user, configs, config } = this.props;
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
                allConfigs={configs.items}
                configsLoading={configs.loading}
                config={config}
                onLoadMoreClick={this.onLoadMoreLogs}
                {...logs}
              />
            </div>
          </content>
        </tabbed-container>
      </div>
    );
  }
}
