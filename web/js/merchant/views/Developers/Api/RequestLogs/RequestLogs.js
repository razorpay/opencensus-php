import React from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { NavLink, withRouter } from 'react-router-dom';
import Alert from 'common/ui/Forms/Alert';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Time from 'common/ui/Time';
import Pager from 'common/ui/Pager';
import * as ApiLogsActions from 'merchant/reducers/developers/apiLogs';
import ListContainer from 'merchant/containers/ListContainer';
import {
  trackApiLogsSearchHttpStatusChanged,
  trackApiLogsSearched,
  trackApiLogsSearchKeywordChanged,
} from '../events';
import StatusLabel from '../../components/StatusLabel';

@withRouter
@connect((state) => ({ ...state.apiLogs }), {
  ...ApiLogsActions,
})
export default class RequestLogs extends ListContainer {
  constructor(props) {
    super(props);
    this.state = {
      ...super.state,
      status: {},
      shouldCtasBeDisabled: false,
    };
  }

  componentDidUpdate(prevProps) {
    const { from: prevPropsFrom, to: prevPropsTo } = prevProps.selectedFilters.duration;
    const { from, to } = this.props.selectedFilters.duration;

    if (prevPropsFrom !== from || prevPropsTo !== to) {
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
          }).then((data) => {
            // CTAs should only be disabled be dates have changed and there's no data in that date range
            this.setState({ shouldCtasBeDisabled: !data.data?.body?.result?.length });
          });
        },
      );
    }
  }

  fetchEntityList(params) {
    const requestData = {
      ...params,
      httpStatus: this.state.httpStatus,
      searchField: this.state.searchField,
      from: params.from || this.props.selectedFilters.duration.from,
      to: params.to || this.props.selectedFilters.duration.to,
    };

    return this.props.fetchApiLogs(requestData);
  }

  handleSearchClick = () => {
    this.search();
    trackApiLogsSearched();
  };

  render() {
    const { loading, items: apiLogs, selectedFilters } = this.props;
    const { duration, dateRange } = selectedFilters;
    const fromDate = moment(duration.from).format('DD MMM');
    const toDate = moment(duration.to).format('DD MMM');
    const { status, shouldCtasBeDisabled } = this.state;

    return (
      <div className="api-logs-container content-wrapper" style={{ marginTop: 20 }}>
        <h4 className="title mb-20">
          API logs {dateRange?.name ? `in ${dateRange.name}` : ''} ({fromDate} - {toDate})
        </h4>
        <div className="list-filter-container">
          <div className="form-group list-filter-item">
            <label>Search</label>
            <input
              type="text"
              name="searchField"
              placeholder="Search for any keyword from request, response or headers"
              class="form-control input-sm"
              style={{ width: 345 }}
              value={this.state.searchField}
              onChange={(e) => this.setState({ searchField: e.target.value })}
              onBlur={() => trackApiLogsSearchKeywordChanged()}
              disabled={!apiLogs?.length}
            />
          </div>
          <div className="form-group list-filter-item">
            <label>Response Code</label>
            <select
              class="form-control input-sm"
              value={this.state.httpStatus}
              onChange={(e) => this.setState({ httpStatus: e.target.value })}
              onBlur={() => trackApiLogsSearchHttpStatusChanged()}
              disabled={shouldCtasBeDisabled}
            >
              <option value="all">All</option>
              <option value="2xx">2xx</option>
              <option value="3xx">3xx</option>
              <option value="4xx">4xx</option>
              <option value="5xx">5xx</option>
            </select>
          </div>
          <div class="form-group list-filter-item btn-toolbar">
            <button
              class="btn btn-primary btn-sm"
              type="button"
              onClick={this.handleSearchClick}
              disabled={shouldCtasBeDisabled}
            >
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
        <Alert type={status.type} message={status.message} />
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Log ID</th>
                <th>Endpoint</th>
                <th>Date and Time</th>
                <th>Response Code</th>
              </tr>
            </thead>
            <TableBody
              isLoading={loading}
              colSpan={8}
              rows={apiLogs}
              emptyTableRow={() => (
                <tr>
                  <td class="text-center empty-table" colSpan={4}>
                    <p>No request logs found for selected time range</p>
                  </td>
                </tr>
              )}
            >
              {apiLogs.map((log) => (
                <EntityItemRow key={log.request_id} id={log.request_id}>
                  <td>
                    <NavLink to={`/developers/apis/${log.request_id}`}>
                      <code>{log.request_id}</code>
                    </NavLink>
                  </td>
                  <td>
                    <strong>{log.request.method.toUpperCase()}</strong>{' '}
                    <span>{log.request.url.split('/v1')[1]}</span>
                  </td>
                  <td>
                    <Time value={log.timestamp} format="DD MMM YYYY, hh:mm:ss a" />
                  </td>
                  <td>
                    <StatusLabel statusCode={log.response.http_status_code} />
                  </td>
                </EntityItemRow>
              ))}
            </TableBody>
          </table>
        </div>
        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={apiLogs.length}
          onClick={this.paginate}
        />
      </div>
    );
  }
}
