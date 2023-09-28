import React from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import Alert from 'common/ui/Forms/Alert';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Time from 'common/ui/Time';
import Pager from 'common/ui/Pager';
import * as WebHooksLogsActions from 'merchant/reducers/developers/webhookLogs';
import ListContainer from 'merchant/containers/ListContainer';
import StatusLabel from 'merchant/views/Developers/components/StatusLabel';
import {
  trackWebhookLogsSearchHttpStatusChanged,
  trackWebhookLogsSearchKeywordChanged,
  trackWebhookLogsSearched,
} from 'merchant/views/Developers/events';
import { HTTP_STATUS_CODE_LIST } from 'merchant/views/Developers/constants';

@connect((state) => ({ ...state.webhookLogs }), {
  ...WebHooksLogsActions,
})
class RequestLogs extends ListContainer {
  constructor(props) {
    super(props);
    this.state = {
      ...super.state,
      shouldCtasBeDisabled: false,
      httpStatus: '',
      searchField: '',
    };
  }

  componentDidUpdate(prevProps) {
    const { from: prevPropsFrom, to: prevPropsTo } = prevProps.selectedFilters.duration;
    const { from, to } = this.props.selectedFilters.duration;

    if (
      prevPropsFrom !== from ||
      prevPropsTo !== to ||
      prevProps.selectedFilters.eventType !== this.props.selectedFilters.eventType
    ) {
      // eslint-disable-next-line react/no-did-update-set-state
      this.setState(
        {
          httpStatus: '',
          searchField: '',
        },
        () => {
          this.search({
            from,
            to,
          });
        },
      );
    }
  }

  fetchEntityList(params) {
    const requestData = {
      ...params,
      ...this.props.selectedFilters,
      httpStatus: this.state.httpStatus,
      searchField: this.state.searchField,
      webhookId: this.props.webhookId,
    };

    return this.props.fetchWebhookLogs(requestData);
  }

  handleSearchClick = (e) => {
    if (e && e.code && e?.code !== 'Enter') return;

    this.search();
    trackWebhookLogsSearched();
  };

  render() {
    const { loading, items: webhookLogs, selectedFilters, match } = this.props;
    const { duration, dateRange } = selectedFilters;
    const fromDate = moment(duration.from).format('DD MMM');
    const toDate = moment(duration.to).format('DD MMM');
    const { status, shouldCtasBeDisabled, httpStatus } = this.state;

    return (
      <div className="webhook-logs-container content-wrapper">
        <h5 className="title mb-20">
          <strong>Webhook Request Logs {dateRange ? `in ${dateRange}` : ''}</strong> ({fromDate} -{' '}
          {toDate})
        </h5>
        <div className="list-filter-container">
          <div className="form-group list-filter-item">
            <label>Search</label>
            <div className="search-input">
              <i className="i i-search input-icon" />
              <input
                type="text"
                name="searchField"
                placeholder="Search for any keyword from request, response or headers"
                className="form-control input-sm input-field"
                value={this.state.searchField}
                onChange={(e) => this.setState({ searchField: e.target.value })}
                onBlur={() => trackWebhookLogsSearchKeywordChanged()}
                onKeyDown={this.handleSearchClick}
              />
            </div>
          </div>
          <div className="form-group list-filter-item">
            <label>Response Code</label>
            <select
              class="form-control input-sm"
              value={httpStatus === '' ? 'all' : httpStatus}
              onChange={(e) =>
                this.setState({ httpStatus: e.target.value === 'all' ? '' : e.target.value })
              }
              onBlur={() => trackWebhookLogsSearchHttpStatusChanged()}
              data-testid="select"
            >
              {HTTP_STATUS_CODE_LIST.map((code) => (
                <option key={code.value} value={code.value} data-testid="select-option">
                  {code.label}
                </option>
              ))}
            </select>
          </div>
          <div class="form-group list-filter-item btn-toolbar">
            <button class="btn btn-primary btn-sm" type="button" onClick={this.handleSearchClick}>
              Search
            </button>
            <button
              type="button"
              className="btn btn-sm btn-text"
              onClick={() => {
                this.setState({ searchField: '', httpStatus: '' }, () => this.search());
              }}
              disabled={shouldCtasBeDisabled}
            >
              Clear
            </button>
          </div>
        </div>
        {status?.type && status?.message ? (
          <Alert type={status.type} message={status.message} />
        ) : null}
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Event ID</th>
                <th>Event Type</th>
                <th>Date and Time</th>
                <th>Status</th>
              </tr>
            </thead>
            <TableBody
              isLoading={loading}
              colSpan={8}
              rows={webhookLogs}
              emptyTableRow={() => (
                <tr>
                  <td class="text-center empty-table" colSpan={4}>
                    <p>No webhook logs found for selected time range</p>
                  </td>
                </tr>
              )}
            >
              {webhookLogs.map((webhookLog) => (
                <EntityItemRow key={webhookLog.request_id} id={webhookLog.request_id}>
                  <td>
                    <NavLink
                      to={`/developers/webhooks/${match.params.id}/event/${webhookLog.request_id}`}
                    >
                      <code>{webhookLog.request_id}</code>
                    </NavLink>
                  </td>
                  <td>{webhookLog.request.route_name}</td>
                  <td>
                    <Time value={webhookLog.timestamp} format="DD MMM YYYY, hh:mm:ss a" />
                  </td>
                  <td>
                    <StatusLabel statusCode={webhookLog.response.http_status_code} />
                  </td>
                </EntityItemRow>
              ))}
            </TableBody>
          </table>
        </div>
        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={webhookLogs.length}
          onClick={this.paginate}
        />
      </div>
    );
  }
}

export default withRouter(RequestLogs);
