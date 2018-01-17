import moment from "moment";
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Breadcrumb, { BreadcrumbItem } from 'rzp/ui/Breadcrumb';
import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

import Treemap from 'merchant/containers/Home/PaymentMethods/Treemap';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import MoreOptionsButton from 'merchant/components/Home/MoreOptionsButton';
import { fetch } from 'merchant/modules/pokedex';
import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import { API_ERROR, API_INVALID_RESP } from 'merchant/components/Home/data';

import { getQuery, sampleData } from './data';
import './styles.styl';

function getLevels(hierarchy, levels = []) {
  if (hierarchy.parent) {
    getLevels(hierarchy.parent, levels);
  }

  levels.push({
    name: hierarchy.displayText,
    data: hierarchy,
  });

  return levels;
}

const csvDateFormat = 'DD-MM-YYYY';

@connect(null, { ...ModalActions, showNotification })
class PaymentMethods extends Component {
  constructor(props) {
    super(props);

    this.state = {
      data: null,
      levels: [],
      currentLevel: null,
      csvData: null,
      isLoading: false,
      error: '',
      hierarchy: {values: []}
    };

    this.requestId = 0;
    this.onLevelChange = ::this.onLevelChange;
    this.onCSVData = ::this.onCSVData;
    this.openReportModal = ::this.openReportModal;
  }

  fetchData(startDate, endDate) {
    this.setState({
      isLoading: true,
      error: '',
    });

    const requestId = ++this.requestId;

    fetch(
      getQuery({
        startTime: startDate.unix(),
        endTime: endDate.unix(),
      })
    )
      .then(resp => {
        // dealyed response
        if (requestId !== this.requestId) {
          return null;
        }

        if (!resp.success) {
          return API_ERROR;
        }

        if (!resp.data || !resp.data.agg) {
          return API_INVALID_RESP;
        }

        const agg = resp.data.agg;

        this.setState({
          data: agg.result,
          lastUpdatedAt: agg.last_updated_at,
        });

        return resp;
      })
      .catch(err => {
        console.error(err);

        if (requestId !== this.requestId) {
          return null;
        }

        return API_ERROR;
      })
      .then(data => {
        if (!data) {
          return;
        }

        this.state.isLoading = false;

        if (data.error) {
          this.state.error = data.error;
          this.props.showNotification({
            type: 'error',
            message: data.error,
          });
        }

        this.setState({ ...this.state });
      });
  }

  onCSVData(csvUrl) {

    const {startDate, endDate} = this.props;

    this.setState({
      csvData: {
        name: `Payment Insights, ${
              moment(startDate).format(csvDateFormat)} to ${
              moment(endDate).format(csvDateFormat)
              }(Razorpay).csv`,
        url: csvUrl,
      },
    });
  }

  onLevelChange(hierarchy) {
    this.setState({
      levels: getLevels(hierarchy),
      currentLevel: hierarchy,
      hierarchy
    });
  }

  componentWillMount() {
    const { startDate, endDate } = this.props;

    this.fetchData(startDate, endDate);
  }

  componentWillReceiveProps(nextProps) {
    const { startDate, endDate } = nextProps;

    if (
      startDate.unix() !== this.props.startDate.unix() ||
      endDate.unix() !== this.props.endDate.unix()
    ) {
      this.fetchData(startDate, endDate);
    }
  }

  openReportModal(e) {
    e.preventDefault();

    this.props.openModal({
      component: null,
      size: 'large',
    });
  }

  render() {
    const { levels, csvData, isLoading, data, error } = this.state,
      { startDate, endDate } = this.props,
      levelsLength = levels.length,
      hasNoData = !data || data.length === 0;

    return (
      <GenericPanel
        className="payment-methods-container"
        isLoading={isLoading}
        hasNoData={hasNoData}
        error={error}
      >
        <PanelTopbar className="clearfix">
          <div className="pull-left">
            <span>Showing:</span>
            {levelsLength > 0 && (
              <Breadcrumb>
                {levels.map((level, index) => (
                  <BreadcrumbItem
                    key={index}
                    onClick={() =>
                      index + 1 !== levelsLength &&
                      this.onLevelChange(level.data)}
                  >
                    {level.name}
                  </BreadcrumbItem>
                ))}
              </Breadcrumb>
            )}
          </div>
          <div className="panel-actions pull-right">
            <div className="panel-action-item">
              <MoreOptionsButton csvData={csvData} />
            </div>
          </div>
        </PanelTopbar>
        <PanelBody>
          <Treemap
            data={this.state.data}
            onLevelChange={this.onLevelChange}
            currentLevel={this.state.currentLevel}
            onCSVData={this.onCSVData}
          />
        </PanelBody>
        <PanelFooter className="clearfix">
          <div className="pull-left">
            <LastUpdated at={this.state.lastUpdatedAt} />
          </div>
          <div className="pull-right">
            <Link
              target="_blank"
              to={`/payments?from=${startDate.unix()}&to=${endDate.unix()}`}
            >
              View all Payments
            </Link>
          </div>
        </PanelFooter>
      </GenericPanel>
    );
  }
}

export default PaymentMethods;
