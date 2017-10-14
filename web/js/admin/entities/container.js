import Promise from 'promise';
import React, { Component } from 'react';

import { adminFetch } from 'util/fetch';
import EntityList from './index';

const initialState = {
  entities: null,
  errors: [],
  collection: { pending: false, items: [] },
  selectedEntity: 'payment',
  selectedMode: 'live',
  selectedFrom: 0,
  selectedTo: 0,
  selectedCount: 20,
  searchEntity: '',
  searchErrors: [],
  skip: 0,
};

function mergeEntitiesWithFields(entities, fields) {
  const modifiedEntities = {};

  Object.keys(entities).map(entityType => {
    const entity = entities[entityType];

    const modifiedEntity = {};

    // entity can be null
    if (entity) {
      Object.keys(entity).map(fieldType => {
        let field = entity[fieldType];

        field = typeof field === 'string' ? fields[fieldType] : field;

        modifiedEntity[fieldType] = field;
      });
    }

    modifiedEntities[entityType] = modifiedEntity;
  });

  return modifiedEntities;
}

export default class EntityListContainer extends Component {
  constructor(props) {
    super(props);

    this.state = initialState;

    this.onSearch = this.onSearch.bind(this);
    this.onModeChange = this.onModeChange.bind(this);
    this.onEntityChange = this.onEntityChange.bind(this);
    this.onSetSelectedFrom = this.onSetSelectedFrom.bind(this);
    this.onSetSelectedTo = this.onSetSelectedTo.bind(this);
    this.onSetCount = this.onSetCount.bind(this);
    this.onSearchEntityChange = this.onSearchEntityChange.bind(this);
  }

  onModeChange(selectedMode) {
    this.setState({ selectedMode });
  }

  onEntityChange(selectedEntity) {
    this.setState({ selectedEntity }, this.onSearch);
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

  onSetCount(e) {
    this.setState({ selectedCount: e.target.value.trim() });
  }

  componentWillMount() {
    const route = 'admin_fetch_all_entities';

    adminFetch({ route })
      .then(resp => {
        resp = resp.data;

        if (resp.errors) {
          return Promise.reject(resp.errors);
        }

        let { entities, fields } = resp.data;

        entities = mergeEntitiesWithFields(entities, fields);

        this.setState({ entities }, this.onSearch);
      })
      .catch(errors => {
        this.setState({ errors });
      });
  }

  onSearch(filters = {}) {
    const urlParams = {
      type: this.state.selectedEntity,
    };

    let queryParams = {};

    const mode = this.state.selectedMode;

    let route = 'admin_fetch_entity_multiple';

    if (this.state.searchEntity) {
      urlParams.id = this.state.searchEntity;
      route = 'admin_fetch_entity_by_id';
    } else {
      queryParams = {
        ...filters,
        count: this.state.selectedCount,
        skip: this.state.skip,
      };

      if (this.state.selectedFrom) {
        queryParams.from = this.state.selectedFrom;
      }

      if (this.state.selectedTo) {
        queryParams.to = this.state.selectedTo;
      }
    }

    this.setState({
      collection: { ...this.state.collection, pending: true },
    });

    return adminFetch({
      mode,
      route,
      urlParams,
      queryParams,
    })
      .then(resp => {
        resp = resp.data;

        if (resp.errors) {
          return Promise.reject(resp.errors);
        }

        const { items, filters } = resp.data;

        this.setState({
          collection: { items, pending: false },
          searchErrors: [],
        });
      })
      .catch(searchErrors => {
        this.setState({
          searchErrors,
          collection: { ...this.state.collection, pending: false },
        });
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
            selectedCount={this.state.selectedCount}
            searchEntity={this.state.searchEntity}
            searchErrors={this.state.searchErrors}
            onSearch={this.onSearch}
            onModeChange={this.onModeChange}
            onEntityChange={this.onEntityChange}
            onSetSelectedFrom={this.onSetSelectedFrom}
            onSetSelectedTo={this.onSetSelectedTo}
            onSearchEntityChange={this.onSearchEntityChange}
            onSetCount={this.onSetCount}
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
