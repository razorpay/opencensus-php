import React, { Component } from 'react';
import { connect } from 'react-redux';
import ModalHeader from 'rzp/ui/ModalHeader';
import { openModal, closeModal } from 'rzp/modules/modals';
import { Bar } from 'react-chartjs-2';
import AsyncButton from 'react-async-button';
import Amount from 'ui/Amount';
import CreditPullAdditionalReport from './CreditPullAdditionalReport';
import { showNotification } from 'rzp/modules/notifications';
import CloseReasons from '../../components/CloseReasons';
import { CLOSE_OPTIONS } from './CreditNotInterestedReasons';
import ajax from '../../../merchantLA/utils/ajax';

@connect(state => ({ user: state.session.user }), {
  closeModal,
  openModal,
  showNotification,
})
export default class CreditPullSuccess extends Component {
  constructor(props) {
    super(props);
    this.state = {
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

  handleInterest = (reportId, consent) => {
    if (consent === 0) {
      this.props.closeModal();
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
      return;
    }
    const payload = {
      consent,
    };
    ajax(
      {
        url: `d2c_bureau_report/${reportId}`,
        method: 'patch',
        data: payload,
      },
      {},
      '/merchant/api'
    )
      .then(success => {
        this.props.closeModal();
        this.props.showNotification({
          type: 'success',
          message: 'Recorded your feedback',
          hidePrevious: true,
        });
      })
      .catch(error => {
        this.props.closeModal();
        this.props.showNotification({
          type: 'error',
          message: 'Failed to record feeback',
          hidePrevious: true,
        });
      });
  };

  render() {
    const { reportId } = this.props;
    return (
      <div className="credit-pull-success-container">
        <ModalHeader
          title={this.titleGenerator()}
          onCloseClick={() => {
            this.props.closeModal();
          }}
        />
        <div className="modal-body">
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

          <div className="col-md-8 rep-container">
            <div className="report-header">Congratulations</div>
            <div className="report-body">
              Based on your credit history, You may be eligible for a loan upto
              given amount. Please confirm your interest.
            </div>
            {this.props.maxLoan && (
              <div className="report-amount">
                <Amount value={this.props.maxLoan} currency={'INR'} />
              </div>
            )}
            <div className="report-actions">
              <AsyncButton
                class="btn btn-primary"
                text="Yes I'm interested"
                onClick={() => this.handleInterest(reportId, 1)}
              />
              <AsyncButton
                class="btn btn-secondary"
                text="No, I'm not"
                onClick={() => this.handleInterest(reportId, 0)}
              />
            </div>
          </div>

          <CreditPullAdditionalReport
            report={this.props.report}
            score={this.props.score}
          />
        </div>
        <div className="modal-footer" />
      </div>
    );
  }
}
