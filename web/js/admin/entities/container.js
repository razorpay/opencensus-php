import Promise from 'promise';
import React, { Component } from 'react';

import { adminFetch } from 'util/fetch';
import EntityFilters from './index';
import EntityListContainer from '../entitylist/container.js';

const initialState = {
  entities: null,
  errors: [],
  selectedEntity: 'payment',
  selectedMode: 'live',
  selectedFrom: 0,
  selectedTo: 0,
  selectedCount: 20,
  searchEntity: '',
  searchErrors: [],
  skip: 0,
  entityProps: {},
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

export default class Entities extends Component {
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
    const routeName = 'admin_fetch_all_entities';

    adminFetch({
      data: {
        route_name: routeName,
        mode: this.state.selectedMode,
      },
    })
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
    const urlParams = {};

    let queryParams = {};

    const mode = this.state.selectedMode;

    let routeName = 'admin_fetch_entity_multiple';

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

    return new Promise(res => {
      this.setState(
        {
          entityProps: {
            type: this.state.selectedEntity,
            mode,
            urlParams: urlParams,
            queryParams,
          },
        },
        res
      );
    });
  }

  render() {
    return (
      <div>
        {this.state.errors.length === 0 ? (
          <div className="entity-view">
            <EntityFilters
              entities={this.state.entities}
              fields={this.state.fields}
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
            {!!this.state.entities && (
              <EntityListContainer
                type={this.state.entityProps.type}
                mode={this.state.entityProps.mode}
                urlParams={this.state.entityProps.urlParams}
                queryParams={this.state.entityProps.queryParams}
              />
            )}
          </div>
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
