import React from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import Alert from 'common/ui/Forms/Alert';
import Pager from 'common/ui/Pager';
import TableBody from 'common/ui/TableBody';
import Time from 'common/ui/Time';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import ListContainer from 'merchant/containers/ListContainer';
import * as ApiLogsActions from 'merchant/reducers/developers/apiLogs';
import StatusLabel from 'merchant/views/Developers/components/StatusLabel';
import { HTTP_STATUS_CODE_LIST } from 'merchant/views/Developers/constants';
import {
  trackApiLogsSearchHttpStatusChanged,
  trackApiLogsSearched,
  trackApiLogsSearchKeywordChanged,
} from 'merchant/views/Developers/events';

class RequestLogs extends ListContainer {
  constructor(props) {
    super(props);
    this.state = {
      ...super.state,
      status: {},
      httpStatus: '',
      searchField: '',
    };
  }

  componentDidUpdate(prevProps) {
    const { from: prevPropsFrom, to: prevPropsTo } = prevProps.selectedFilters.duration;
    const { from, to } = this.props.selectedFilters.duration;

    if (prevPropsFrom !== from || prevPropsTo !== to) {
      /* eslint-disable-next-line react/no-did-update-set-state */
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
      httpStatus: this.state.httpStatus,
      searchField: this.state.searchField,
      from: params.from || this.props.selectedFilters.duration.from,
      to: params.to || this.props.selectedFilters.duration.to,
    };

    return this.props.fetchApiLogs(requestData);
  }

  handleSearchClick = (e) => {
    if (e && e.code && e?.code !== 'Enter') return;

    this.search();
    trackApiLogsSearched();
  };

  render() {
    const { loading, items: apiLogs, selectedFilters } = this.props;
    const { duration, dateRange } = selectedFilters;
    const fromDate = moment(duration.from).format('DD MMM');
    const toDate = moment(duration.to).format('DD MMM');
    const { status, httpStatus } = this.state;

    return (
      <div className="api-logs-container content-wrapper">
        <h5 className="mb-20">
          <strong>API Request Logs {dateRange ? `in ${dateRange}` : ''}</strong> ({fromDate} -{' '}
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
                onBlur={() => trackApiLogsSearchKeywordChanged()}
                onKeyDown={this.handleSearchClick}
              />
            </div>
          </div>
          <div className="form-group list-filter-item">
            <label>Response Code</label>
            <select
              className="form-control input-sm"
              value={httpStatus === '' ? 'all' : httpStatus}
              onChange={(e) =>
                this.setState({ httpStatus: e.target.value === 'all' ? '' : e.target.value })
              }
              onBlur={() => trackApiLogsSearchHttpStatusChanged()}
              data-testid="select"
            >
              {HTTP_STATUS_CODE_LIST.map((code) => (
                <option key={code.value} value={code.value} data-testid="select-option">
                  {code.label}
                </option>
              ))}
            </select>
          </div>
          <div className="form-group list-filter-item btn-toolbar">
            <button
              className="btn btn-primary btn-sm"
              type="button"
              onClick={this.handleSearchClick}
            >
              Search
            </button>
            <button
              type="button"
              className="btn btn-sm btn-text"
              onClick={() => {
                this.setState({ searchField: '', httpStatus: '' }, () => this.search());
              }}
            >
              Clear
            </button>
          </div>
        </div>
        <Alert type={status.type} message={status.message} />
        <div className="table-responsive">
          <table className="table table-hover">
            <thead>
              <tr>
                <th>Log ID</th>
                <th>Method &amp; Endpoint</th>
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
                  <td className="text-center empty-table" colSpan={4}>
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

export default compose(
  withRouter,
  connect((state) => ({ ...state.apiLogs }), {
    ...ApiLogsActions,
  }),
)(RequestLogs);
