import React, { Component } from 'react';
import { default as deepEqual } from 'deep-equal';

import { adminFetch } from 'util/fetch';
import EntityList from './index';

class EntityListContainer extends Component {
  constructor(props) {
    super(props);

    this.perPage = 10;
    this.skip = 0;
    this.reqData = {};

    this.state = {
      noMoreResults: false,
      loading: false,
      errors: [],
      records: [],
    };

    this.handlePaginateClick = this.handlePaginateClick.bind(this);
  }

  handlePaginateClick(e) {
    this.fetchData(
      {
        ...this.reqData,
        queryParams: {
          ...this.reqData.queryParams,
          skip: this.skip,
        },
      },
      true
    );
  }

  fetchData(reqData, paginate = false) {
    this.setState({
      loading: true,
      records: paginate ? this.state.records : [],
    });

    adminFetch(reqData)
      .then(resp => {
        resp = resp.data;

        if (resp.errors) {
          return Promise.reject(resp.errors);
        }

        const records = this.state.records,
          newRecords = resp.data.items,
          noMoreResults = newRecords.length < this.perPage;

        if (paginate) {
          records.splice(records.length, 0, ...newRecords);
        } else {
          records.splice(0, records.length, ...newRecords);
        }

        this.skip += this.perPage | 0;

        this.setState({
          records,
          loading: false,
          noMoreResults,
        });
      })
      .catch(errors => {
        this.setState({
          errors: errors,
          loading: false,
        });
      });
  }

  componentWillReceiveProps(nextProps) {
    const { type, mode, urlParams, queryParams, routeName } = nextProps;

    if (
      type !== this.props.type ||
      mode !== this.props.mode ||
      routeName !== this.props.routeName ||
      !deepEqual(urlParams, this.props.urlParams) ||
      !deepEqual(queryParams, this.props.queryParams)
    ) {
      this.skip = 0;
      this.perPage = queryParams.count || 20;

      this.reqData = {
        data: {
          mode,
          url_params: {
            ...urlParams,
            type: type,
          },
          route_name:
            routeName ||
            (urlParams.id
              ? 'admin_fetch_entity_by_id'
              : 'admin_fetch_entity_multiple'),
        },
        queryParams,
      };

      this.fetchData(this.reqData);
    }
  }

  render() {
    return (
      <div className="entity-results box">
        <div>
          <EntityList
            records={this.state.records}
            limit={this.props.columnLimit || 10}
          />
        </div>
        <div>
          <center>
            {this.state.loading ? (
              'Loading...'
            ) : this.state.noMoreResults ? (
              'No more Results'
            ) : (
              <div className="panel-footer">
                <button className="btn" onClick={this.handlePaginateClick}>
                  Load More
                </button>
              </div>
            )}
          </center>
        </div>
      </div>
    );
  }
}

export default EntityListContainer;
