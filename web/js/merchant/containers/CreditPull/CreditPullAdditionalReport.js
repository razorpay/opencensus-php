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
              total={this.props.report.bureauScore}
              rowData={this.generateRowData(
                this.props.report,
                ['Active', 'Closed'],
                ['creditAccountActive', 'creditAccountClosed']
              )}
            />
          </div>
        )}

        {this.state.moreInfo && (
          <div className="col-md-7 rep-container-row2">
            <CreditPullScoreBreakdown
              title="Outstanding Balance"
              total={this.props.report.outstanding_Balance_All}
              amount={true}
              rowData={this.generateRowData(
                this.props.report,
                ['Secured', 'Un-secured'],
                ['outstanding_Balance_Secured', 'outstanding_Balance_UnSecured']
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
            <a>Show FAQ</a>
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
