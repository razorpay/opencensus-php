import Promise from 'promise';
import React, { Component } from 'react';

import { adminFetch } from 'util/fetch';
import EntityList from './index';

const initialState = {
  entities: null,
  fields: null,
  errors: [],
  collection: { pending: false, items: [] },
  selectedEntity: 'payment',
  selectedMode: 'live',
  selectedFrom: 0,
  selectedTo: 0,
  searchEntity: '',
  searchErrors: [],
};

export default class EntityListContainer extends Component {
  constructor(props) {
    super(props);

    this.state = initialState;

    this.onSearch = this.onSearch.bind(this);
    this.onModeChange = this.onModeChange.bind(this);
    this.onEntityChange = this.onEntityChange.bind(this);
    this.onSetSelectedFrom = this.onSetSelectedFrom.bind(this);
    this.onSetSelectedTo = this.onSetSelectedTo.bind(this);
    this.onSearchEntityChange = this.onSearchEntityChange.bind(this);
  }

  onModeChange(selectedMode) {
    this.setState({ selectedMode });
  }

  onEntityChange(selectedEntity) {
    this.setState({ selectedEntity });
  }

  onSetSelectedFrom(e) {
    this.setState({ selectedFrom: e.target.value });
  }

  onSetSelectedTo(e) {
    this.setState({ selectedTo: e.target.value });
  }

  onSearchEntityChange(e) {
    this.setState({ searchEntity: e.target.value.trim() });
  }

  componentWillMount() {
    const route = 'admin_fetch_all_entities';

    adminFetch({ route })
      .then(resp => {
        resp = resp.data;

        if (resp.errors) {
          return Promise.reject(resp.errors);
        }

        const { entities, fields } = resp.data;

        this.setState({ entities, fields });
      })
      .catch(errors => {
        this.setState({ errors });
      });
  }

  onSearch(filters = {}) {
    const urlParams = {
      type: this.state.selectedEntity,
      mode: this.state.selectedMode,
    };

    let route = 'admin_fetch_entity_multiple';

    if (this.state.searchEntity) {
      urlParams.id = this.state.searchEntity;
      filters = null;
      route = 'admin_fetch_entity_by_id';
    } else {
      if (this.state.selectedFrom) {
        filters.from = this.state.selectedFrom;
      }

      if (this.state.selectedTo) {
        filters.to = this.state.selectedTo;
      }
    }

    this.setState({
      collection: { pending: true },
    });

    return adminFetch({
      route,
      urlParams,
      queryParams: filters,
    })
      .then(resp => {
        resp = resp.data;

        if (resp.errors) {
          return Promise.reject(resp.errors);
        }

        const { items } = resp.data;

        this.setState({
          collection: { items, filters },
          searchErrors: [],
        });
      })
      .catch(searchErrors => {
        this.setState({ searchErrors });
      })
      .then(() => {
        this.setState({ collection: { pending: false } });
      });
  }

  render() {
    return (
      <div>
        {this.state.errors.length === 0 ? (
          <EntityList
            entities={this.state.entities}
            fields={this.state.fields}
            collection={this.state.collection}
            selectedEntity={this.state.selectedEntity}
            selectedMode={this.state.selectedMode}
            selectedFrom={this.state.selectedFrom}
            selectedTo={this.state.selectedTo}
            searchEntity={this.state.searchEntity}
            searchErrors={this.state.searchErrors}
            onSearch={this.onSearch}
            onModeChange={this.onModeChange}
            onEntityChange={this.onEntityChange}
            onSetSelectedFrom={this.onSetSelectedFrom}
            onSetSelectedTo={this.onSetSelectedTo}
            onSearchEntityChange={this.onSearchEntityChange}
          />
        ) : (
          <div className="text-danger">
            {this.state.errors.map((error, index) => {
              return <span key={index}>{error}</span>;
            })}
          </div>
        )}
      </div>
    );
  }
}
