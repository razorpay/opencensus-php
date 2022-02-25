/* eslint-disable consistent-return */
import React from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import { analyticsTrack } from 'common/utils/analytics';
import Spinner from 'common/ui/Spinner';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { showNotification } from 'merchant_common/reducers/notifications';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import ShowWhen from 'merchant/components/ShowWhen';
import ZapierLaunchBanner from 'merchant/components/Announcements/ZapierBanner/ZapierBanner';
import { getItem } from 'common/utils/localStorage';
import LogList from './Logs/List';
import GenerateReportPanel from './GenerateReportPanel';
import EasterEgg from 'merchant/components/EasterEgg';

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
      window.hj('tagRecording', ['report-async-started', this.props.user.current]);
    }
  }

  onGenerateReport = (payload, accountId) => {
    return this.props
      .createLog(
        {
          ...payload,
          generated_by: this.props.user.current,
        },
        accountId,
      )
      .then((data) => {
        if (data) {
          const selectedConfig =
            this.props.configs.items &&
            this.props.configs.items.filter((item) => item.id === payload.config_id);
          if (data.is_already_present) {
            this.props.showNotification({
              type: 'info',
              message:
                'Request with same report type and date range is in processing. Please check your request history',
            });
            analyticsTrack({
              objectName: 'generate report',
              actionName: 'result',
              screen: 'reports',
              properties: {
                location: 'generate reports',
                reportType: selectedConfig[0].name,
                periodStart: payload.start_time,
                periodEnd: payload.end_time,
                formatSelected: payload.template_overrides,
                emailSelected: !!(payload.emails && payload.emails.length > 0),
                status: 'Success',
                infoMessage:
                  'Request with same report type and date range is in processing. Please check your request history',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
          } else if (data.id) {
            // eslint-disable-next-line no-shadow
            const accountId = data.generated_by !== data.consumer ? data.consumer : undefined;
            this.props.pollLog(data.id, accountId);
            analyticsTrack({
              objectName: 'generate report',
              actionName: 'result',
              screen: 'reports',
              properties: {
                location: 'generate reports',
                reportType: selectedConfig[0].name,
                periodStart: payload.start_time,
                periodEnd: payload.end_time,
                formatSelected: payload.template_overrides,
                emailSelected: !!(payload.emails && payload.emails.length > 0),
                status: 'Success',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
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
                  rel="noreferrer noopener"
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
        analyticsTrack({
          objectName: 'generate report',
          actionName: 'result',
          screen: 'reports',
          properties: {
            location: 'generate reports',
            status: 'Failure',
            failureReason: error,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      });
  };

  onLoadMoreLogs = () => {
    const { logs } = this.props;
    analyticsTrack({
      objectName: 'load more',
      actionName: 'clicked',
      screen: 'reports',
      properties: {
        location: 'generate reports',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.props.fetchLogs({ count: 5, skip: logs.items.length });
  };

  render() {
    const { logs, user, configs, customConfigs, ...otherProps } = this.props;
    return (
      <div>
        <ShowWhen
          additionalCondition={(currentUser) =>
            currentUser.isPartOfZapierIntegrationExperiment &&
            !getItem(`zapier-integration-banner-${user.current}`)
          }
        >
          <ZapierLaunchBanner
            fromWhere="reports"
            bannerKey={`zapier-integration-banner-${user.current}`}
          />
        </ShowWhen>
        <tabbed-container>
          <header>
            <NavLink to="/reports">Reports</NavLink>
          </header>
          <TestModeBanner />
          <content>
            <div className="content-wrapper Reporting--ContentWrapper">
              {configs.loading && logs.loading ? (
                <div className="page-spinner-container">
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
                    onlyDailyOptionsInReferredAccounts={
                      otherProps.onlyDailyOptionsInReferredAccounts
                    }
                  />
                  <div className="m-t" />
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
        <EasterEgg extraClass="ftx-reports-page" page="Reports" />
      </div>
    );
  }
}
