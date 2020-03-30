import React, { Component } from 'react';
import CreditPullScoreBreakdown from './CreditPullScoreBreakdown';

export default class CreditPullAdditionalReport extends Component {
  constructor(props) {
    super(props);
    this.state = {
      moreInfo: false,
    };
  }

  generateRowData = (reportData, label, key) => {
    let rowData = [];
    for (let iter in label) {
      rowData.push({
        desc: label[iter],
        value: reportData[key[iter]],
      });
    }
    return rowData;
  };

  titleGenerator = () => {
    return <span>Credit Report</span>;
  };

  returnExtra = () => {
    return (
      <>
        {this.state.moreInfo && (
          <div className="col-md-5 rep-container-row2 rep-container-2">
            <CreditPullScoreBreakdown
              title="No. of Accounts"
              total={this.props.report.count_of_accounts}
              rowData={this.generateRowData(
                this.props.report,
                ['Active', 'Closed'],
                ['active_accounts', 'closed_accounts']
              )}
            />
          </div>
        )}

        {this.state.moreInfo && (
          <div className="col-md-7 rep-container-row2">
            <CreditPullScoreBreakdown
              title="Outstanding Balance"
              total={this.props.report.total_outstanding_balance}
              amount={true}
              rowData={this.generateRowData(
                this.props.report,
                ['Secured', 'Un-secured'],
                [
                  'secured_account_outstanding_balance',
                  'un_secured_account_outstanding_balance',
                ]
              )}
            />
          </div>
        )}

        <div className="col-md-4 foot-box">
          <button
            className="btn btn-secondary btn-credit"
            onClick={() => {
              this.setState({ moreInfo: !this.state.moreInfo });
            }}
          >
            More Credit Details{' '}
            {
              <i
                className={`i i-chevron-${this.state.moreInfo ? 'up' : 'down'}`}
              />
            }
          </button>
        </div>
        <div className="col-md-8 foot-box">
          <div className="mod-faq">
            Finding it difficult to understand the Report or terms?{' '}
            <a
              target="_blank"
              href="https://razorpay.com/capital/credit-report-faq"
              onClick={() => {
                window.rzpAnalytics({
                  eventCategory: 'Dashboard - D2C',
                  eventAction: 'Show FAQs',
                });
              }}
            >
              Show FAQ
            </a>
          </div>
        </div>
        <div className="col-md-12 foot-box">
          <div className="orange-pad">
            Your full credit report has been shared on your e-mail.
          </div>
          <img
            className="exp-logo"
            src="https://cdn.razorpay.com/static/assets/experian_logo.png"
          />
          <span className="exp-logo-text">Powered by</span>
        </div>
      </>
    );
  };

  render() {
    return this.returnExtra();
  }
}
