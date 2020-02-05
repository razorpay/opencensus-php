import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import Spinner from 'common/ui/Spinner';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import { showNotification } from 'merchant_common/reducers/notifications';

import LogList from './Logs/List';
import GenerateReportPanel from './GenerateReportPanel';

@connect(null, { showNotification })
export default class ReportHome extends React.PureComponent {
  componentDidMount() {
    this.props.fetchConfigs();
    this.props.fetchLogs({ count: 5 });

    if (this.props.showSelectAccount) {
      this.props.fetchAccounts();
    }

    if (typeof window.hj === 'function') {
      window.hj('trigger', 'report-async-started');
      window.hj('tagRecording', ['report-async-started']);
    }
  }

  onGenerateReport = (payload, accountId) => {
    return this.props
      .createLog(
        {
          ...payload,
          generated_by: this.props.user.current,
        },
        accountId
      )
      .then(data => {
        if (data) {
          if (data.is_already_present) {
            this.props.showNotification({
              type: 'info',
              message:
                'Request with same report type and date range is in processing. Please check your request history',
            });
          } else if (data.id) {
            const accountId =
              data.generated_by !== data.consumer ? data.consumer : undefined;
            this.props.pollLog(data.id, accountId);
          }
        }
      })
      .catch(({ errors }) => {
        const error = errors[0];
        if (error.includes('You reached maximum limit')) {
          return this.props.showNotification({
            type: 'error',
            message: (
              <>
                {error} See more details on this error{' '}
                <NavLink
                  to="https://razorpay.com/docs/payment-gateway/dashboard-guide/reports/"
                  target="_blank"
                >
                  here
                </NavLink>
                .
              </>
            ),
            closeTimeout: 5000,
          });
        } else {
          this.props.showNotification({
            type: 'error',
            message: error,
          });
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
              {configs.loading && logs.loading ? (
                <div class="page-spinner-container">
                  <Spinner />
                </div>
              ) : (
                <>
                  <GenerateReportPanel
                    configs={configs}
                    customConfigs={customConfigs}
                    onGenerateReport={this.onGenerateReport}
                    emailReportOptions={otherProps.emailReportOptions}
                    mode={otherProps.mode}
                    showSelectAccount={otherProps.showSelectAccount}
                    accounts={otherProps.accounts}
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
                </>
              )}
            </div>
          </content>
        </tabbed-container>
      </div>
    );
  }
}
