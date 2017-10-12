import React, { Component } from 'react';
import Form from 'ui/Form';
import Table from 'ui/Table';
import Field, { SelectField, SwitchField, DateTimeField } from 'ui/Field';
import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';

export default class EntityList extends Component {
  collection = new Collection({
    fetchRoute: 'pricing_get_merchant_plans',
    fetchFn: adminFetch,
  });

  state = {
    entityList: [],
  };

  constructor(props) {
    super(props);

    this.changeFilters = this.changeFilters.bind(this);
    this.onSubmit = this.onSubmit.bind(this);
  }

  onSubmit = filters => {
    const { entityType, mode, count, from, to, entityId, ...rest } = filters;
    return this.props.onSearch(rest);
  };

  changeFilters({ target }) {
    this.props.onEntityChange(target.value);
  }

  componentWillReceiveProps(nextProps) {
    if (!nextProps.entities) {
      return false;
    }

    this.setState({
      entityList: Object.keys(nextProps.entities).sort(),
    });
  }

  render() {
    const { entities, fields, selectedEntity } = this.props,
      filters = entities && entities[selectedEntity];

    return (
      <div class="list-container">
        <div class="box">
          <header>Entities</header>
          {!entities ? (
            <span>Please Wait...</span>
          ) : (
            <Form onSubmit={this.onSubmit} class="filters">
              <SelectField
                name="entityType"
                label="Entity"
                onChange={this.changeFilters}
                defaultValue={this.props.selectedEntity}
              >
                {this.state.entityList.map((entity, index) => (
                  <option key={index} value={entity}>
                    {entity}
                  </option>
                ))}
              </SelectField>
              <SwitchField
                disabledValue="test"
                value={this.props.selectedMode}
                onChange={this.props.onModeChange}
                mode="mode"
                label="Live Mode"
              />
              <Field
                label="Count"
                class="small"
                name="count"
                type="number"
                defaultValue="20"
                min="10"
                max="1000"
                step="10"
              />
              <Field
                label="From"
                type="datetime-local"
                name="from"
                value={this.props.selectedFrom}
                onChange={this.props.onSetSelectedFrom}
              />
              <Field
                label="To"
                type="datetime-local"
                name="to"
                value={this.props.selectedTo}
                onChange={this.props.onSetSelectedTo}
              />
              <Field
                label="ID"
                name="entityId"
                value={this.props.searchEntity}
                onChange={this.props.onSearchEntityChange}
              />

              <div class="more-filters-following" />
              {filters &&
                Object.keys(filters).map((filterName, index) => {
                  let filterValue = filters[filterName];
                  if (typeof filterValue === 'string')
                    return (
                      <Field
                        key={index}
                        label={filterValue}
                        name={filterName}
                      />
                    );
                  return (
                    <SelectField
                      label={filterName}
                      name={filterName}
                      key={index}
                    >
                      {filterValue instanceof Array &&
                        filterValue.map((o, i) => (
                          <option value={o} key={i}>
                            {o}
                          </option>
                        ))}
                    </SelectField>
                  );
                })}
              <button>Go</button>
            </Form>
          )}
        </div>
        {this.props.searchErrors.length > 0 && (
          <div className="text-danger">
            {this.props.searchErrors.map((error, index) => {
              return <span key={index}>{error}</span>;
            })}
          </div>
        )}
      </div>
    );
  }
}
