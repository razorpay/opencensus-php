import { NavLink } from 'react-router-dom';

import TestModeBanner from 'merchant/containers/TestModeBanner';

import LogList from './Logs/List';
import GenerateReportPanel from './GenerateReportPanel';

export default class ReportHome extends React.PureComponent {
  componentDidMount() {
    this.props.fetchConfigs();
    this.props.fetchLogs({ count: 5 });
  }

  onGenerateReport = payload => {
    return this.props
      .createLog({
        ...payload,
        generated_by: this.props.user.current,
      })
      .then(data => {
        if (data && data.id) {
          this.props.pollLog(data.id);
        }
      });
  };

  onLoadMoreLogs = () => {
    this.props.loadMore({ count: 5, skip: 5 });
  };

  render() {
    const { logs, user, configs, customConfigs, ...otherProps } = this.props;
    return (
      <div>
        <tabbed-container>
          <header>
            <NavLink to="/reports">Reports</NavLink>
          </header>
          <TestModeBanner />
          <content>
            <div class="content-wrapper Reporting--ContentWrapper">
              <GenerateReportPanel
                configs={configs}
                customConfigs={customConfigs}
                onGenerateReport={this.onGenerateReport}
                emailReportOptions={otherProps.emailReportOptions}
                mode={otherProps.mode}
              />
              <div class="m-t" />
              <LogList
                currentMerchantId={user.current}
                allConfigs={configs.items}
                configsLoading={configs.loading}
                onLoadMoreClick={this.onLoadMoreLogs}
                pollLog={otherProps.pollLog}
                {...logs}
              />
            </div>
          </content>
        </tabbed-container>
      </div>
    );
  }
}
