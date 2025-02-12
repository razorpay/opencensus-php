import React, { Component } from 'react';
import { Bar } from 'react-chartjs-2';
import { connect } from 'react-redux';

import Button, { AsyncBtn } from 'common/new-ui/Button';
import Popover, { PopoverBody } from 'common/ui/Popover';
import CreditPullScoreBreakdown from 'merchant/containers/CreditPullModal/components/CreditPullScoreBreakdown';
import { fetchLoanApplicationMeta } from 'merchant/reducers/capital';

import Note from '../../components/Note';
import { isCashAdvanceProduct } from '../../utils';
import { APPLICATION_STATES, TOOLTIP_DESCRIPTIONS } from '../constants';

class CreditScoreBreakdown extends Component {
  constructor(props) {
    super(props);
    this.upperScore = 900;
    this.lowerScore = 300;
    this.chartOptions = {
      responsive: true,
      maintainAspectRatio: false,
      tooltips: {
        enabled: false,
      },
      animation: {
        duration: 10,
      },
      scales: {
        xAxes: [
          {
            stacked: true,
            display: false,
          },
        ],
        yAxes: [
          {
            stacked: true,
            display: false,
          },
        ],
      },
      legend: { display: false },
    };

    const creditScoreBreakdown = this.props.loanApplicationDetails.bureau_report_details;
    const { score } = creditScoreBreakdown.data.bureau_report;
    this.chartData = {
      labels: [],
      datasets: [
        {
          data: [score],
          backgroundColor: 'rgba(12, 54, 204, 0.9)',
        },
        {
          data: [this.upperScore - score],
          backgroundColor: 'rgba(12, 54, 204, 0.2)',
        },
      ],
    };
  }

  componentDidMount() {
    const {
      context: { data },
      meta,
    } = this.props.loanApplicationDetails;

    if (data && data.from === 'next') {
      this.props._trackNavigationActions(
        'NEXT',
        APPLICATION_STATES.CREDIT_PULL_PENDING,
        meta.configuration.getApplicationStateDescriptions()[APPLICATION_STATES.CREDIT_PULL_PENDING]
          .stages.CREDIT_REPORT,
      );
    }
  }

  handleNext = () => {
    const { id, status } = this.props.loanApplicationDetails.meta.data.application;
    if (status === APPLICATION_STATES.CREDIT_PULL_PENDING) {
      return this.props.fetchLoanApplicationMeta(id).then(() => {
        this.props.navigation.next();
      });
    } else {
      this.props.navigation.next();
    }
  };

  generateRowData = (reportData, label, key, classes) => {
    const rowData = [];
    for (const iter in label) {
      rowData.push({
        desc: label[iter],
        value: reportData[key[iter]],
        className: classes[iter],
      });
    }
    return rowData;
  };

  render() {
    const { loanApplicationDetails, navigation } = this.props;
    const creditScoreBreakdown = loanApplicationDetails.bureau_report_details;
    const { report, score, ntc_score } = creditScoreBreakdown.data.bureau_report;

    const { configuration, product } = loanApplicationDetails.meta;
    const isNTCScoreValid = ntc_score !== null && ntc_score !== undefined;
    const hasReportData = report && Object.keys(report).length;
    const showNTCScreen = isNTCScoreValid && !hasReportData;

    if (showNTCScreen || !score) {
      return (
        <div className="pending-note-wrapper">
          <Note
            product={product}
            showRazorpaySupportInstruction={false}
            message="You either don't have a bureau presence or your bureau records are not recent enough to evaluate a score."
            extraMessage={`The good news is that we will still process your application and evaluate you for ${
              isCashAdvanceProduct(product) ? 'cash advance' : 'loan'
            }.`}
          />
          <div className="actions m-l m-r pull-right">
            <Button.Transparent onClick={navigation.back}>
              <i className="i i-chevron-left" />
              Back
            </Button.Transparent>
            <AsyncBtn.Primary
              className="m-l"
              onClick={() => {
                this.props._trackNavigationActions(
                  'NEXT',
                  this.props.nextState,
                  configuration.getApplicationStateDescriptions()[
                    APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING
                  ].stages.ADDRESS_PROOF,
                );
                navigation.next();
              }}
            >
              Next
              <i className="i i-chevron-right" />
            </AsyncBtn.Primary>
          </div>
        </div>
      );
    }

    return (
      <div>
        <div className="credit-score-breakdown">
          <div className="col-md-4 rep-container rep-container-1">
            <div className="tab-title">
              Credit Score
              <small className="help-content" style={{ paddingLeft: '4px' }}>
                <i className="i i-info-outline" />
                <Popover align="top" theme="dark">
                  <PopoverBody>
                    <div style={{ textAlign: 'left' }}>{TOOLTIP_DESCRIPTIONS.credit_score}</div>
                  </PopoverBody>
                </Popover>
              </small>
            </div>
            <div className="col-md-6 credit-chart-container">
              <Bar options={this.chartOptions} data={this.chartData} />
            </div>
            <div className="col-md-6 credit-score-container">
              <div className="credit-max">{this.upperScore}</div>
              <div className="credit-score">
                <span className="score">{score}</span>
                <div className="desc">Credit Score</div>
              </div>
              <div className="credit-min">{this.lowerScore}</div>
            </div>
          </div>
          <div className="col-md-7 rep-container-row2">
            <CreditPullScoreBreakdown
              title="No. of Accounts"
              total={report.count_of_accounts}
              rowData={this.generateRowData(
                report,
                ['Active', 'Closed'],
                ['active_accounts', 'closed_accounts'],
                ['success', 'danger'],
              )}
            />
          </div>
          <div className="col-md-7 rep-container-row2">
            <CreditPullScoreBreakdown
              title="Outstanding Balance"
              total={report.total_outstanding_balance}
              amount={true}
              rowData={this.generateRowData(
                report,
                ['Secured', 'Un-secured'],
                ['secured_account_outstanding_balance', 'un_secured_account_outstanding_balance'],
                ['success', 'danger'],
              )}
            />
          </div>
          <div className="shared-note orange-pad">
            <span>Your full credit report has been shared on your e-mail.</span>
          </div>
          {score > 450 && (
            <div className="credit-score-actions m-l m-r pull-right">
              <Button.Transparent onClick={navigation.back}>
                <i className="i i-chevron-left" />
                Back
              </Button.Transparent>
              <AsyncBtn.Primary
                className="m-l"
                onClick={() => {
                  this.props._trackNavigationActions(
                    'NEXT',
                    this.props.nextState,
                    this.props.nextState === APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING
                      ? configuration.getApplicationStateDescriptions()[
                          APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING
                        ].stages.BANK_STATEMENT
                      : '',
                  );
                  this.handleNext();
                }}
              >
                Next
                <i className="i i-chevron-right" />
              </AsyncBtn.Primary>
            </div>
          )}
        </div>
      </div>
    );
  }
}

export default connect(
  (state) => ({
    loanApplicationDetails: state.loanApplicationDetails,
  }),
  {
    fetchLoanApplicationMeta,
  },
)(CreditScoreBreakdown);
