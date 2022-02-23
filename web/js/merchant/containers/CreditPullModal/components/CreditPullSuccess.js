import React, { Component } from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { Bar } from 'react-chartjs-2';
import AsyncButton from 'react-async-button';
import CreditPullAdditionalReport from './CreditPullAdditionalReport';
import { showNotification } from 'merchant_common/reducers/notifications';
import CloseReasons from './CloseReasons';
import { CLOSE_OPTIONS } from './CreditNotInterestedReasons';
import ajax from 'merchant/utils/ajax';

@connect((state) => ({ user: state.session.user }), {
  closeModal,
  openModal,
  showNotification,
})
export default class CreditPullSuccess extends Component {
  constructor(props) {
    super(props);
    this.state = {
      // eslint-disable-next-line react/no-unused-state
      moreInfo: false,
    };
    this.upperScore = 900;
    this.lowerScore = 300;
    this.chartData = {
      labels: [],
      datasets: [
        {
          data: [props.score],
          backgroundColor: 'rgba(12, 54, 204, 0.9)',
        },
        {
          data: [this.upperScore - props.score],
          backgroundColor: 'rgba(12, 54, 204, 0.2)',
        },
      ],
    };
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
  }

  generateRowData = (reportData, label, key) => {
    const rowData = [];
    // eslint-disable-next-line guard-for-in
    for (const iter in label) {
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

  handleInterest = (consent) => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - D2C',
      eventAction: consent === 0 ? 'Not Interested' : 'Interested',
    });
    this.props.closeModal();
    ajax(
      {
        url: `d2c_bureau_reports/${this.props.reportId}`,
        method: 'patch',
        data: {
          interested: consent,
        },
      },
      {},
      '/merchant/api',
    )
      .then(() => {
        if (consent === 0) {
          this.props.openModal({
            component: (
              <CloseReasons
                eventCategory="Dashboard - D2C"
                eventAction="Reason- Not Interested"
                closeReasons={CLOSE_OPTIONS}
              />
            ),
            size: 'small',
          });
        } else {
          this.props.showNotification({
            type: 'success',
            message: 'Recorded your feedback',
            hidePrevious: true,
          });
        }
      })
      .catch(() => {
        this.props.showNotification({
          type: 'error',
          message: 'Error while recording your feedback',
          hidePrevious: true,
        });
      });
  };

  render() {
    return (
      <div className={`${!!this.props.ntcScore ? 'ntc-container' : ''}`}>
        <div className="credit-pull-success-container">
          <ModalHeader
            title={this.titleGenerator()}
            onCloseClick={() => {
              window.rzpAnalytics?.({
                eventCategory: 'Dashboard - D2C',
                eventAction: 'Closed from Score Screen w/o Interest',
              });
              this.props.closeModal();
            }}
          />
          <div className="modal-body">
            {!this.props.ntcScore && (
              <div>
                <div className="col-md-4 rep-container rep-container-1">
                  <div className="tab-title">Credit Score</div>
                  <div className="col-md-6 credit-chart-container">
                    <Bar options={this.chartOptions} data={this.chartData} />
                  </div>
                  <div className="col-md-6 credit-score-container">
                    <div className="credit-max">{this.upperScore}</div>
                    <div className="credit-score">
                      <span className="score">{this.props.score}</span>
                      <div className="desc">Credit Score</div>
                    </div>
                    <div className="credit-min">{this.lowerScore}</div>
                  </div>
                </div>
              </div>
            )}
            <div
              className={`background-col ${
                !!this.props.ntcScore ? 'col-md-12 small-container' : 'col-md-8 rep-container'
              }`}
            >
              <div className="report-header">Congratulations</div>
              {!!this.props.ntcScore ? (
                <div className="report-body">
                  Though we could not find any credit records on your name, you are still eligible
                  Please confirm your interest.
                </div>
              ) : (
                <div className="report-body">
                  Based on your credit history, You may be eligible for a loan. Please confirm your
                  interest.
                </div>
              )}
              <div className="report-actions">
                <AsyncButton
                  class="btn btn-primary"
                  text="Yes I'm interested"
                  onClick={() => this.handleInterest(1)}
                />
                <span> </span>
                <AsyncButton
                  class="btn btn-secondary"
                  text="No, I'm not"
                  onClick={() => this.handleInterest(0)}
                />
              </div>
              <div className="d2c-support">
                <h5>Need help?</h5>
                <div>
                  For any queries or assistance, please contact:&nbsp;
                  <a
                    href="mailto:capital.support@razorpay.com?Subject=Loan%20eligibility%20follow%20up"
                    target="_top"
                  >
                    Razorpay Support
                  </a>
                </div>
              </div>
            </div>
            {!this.props.ntcScore && (
              <div>
                <CreditPullAdditionalReport report={this.props.report} score={this.props.score} />
              </div>
            )}
            <div className="col-md-12 foot-box">
              {!this.props.ntcScore && (
                <div className="orange-pad">
                  Your full credit report has been shared on your e-mail.
                </div>
              )}
              <img
                className="exp-logo"
                src="https://cdn.razorpay.com/static/assets/experian_logo.png"
              />
              <span className="exp-logo-text">Powered by</span>
            </div>
          </div>
          <div className="modal-footer" />
        </div>
      </div>
    );
  }
}
