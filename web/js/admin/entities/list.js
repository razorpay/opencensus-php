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
    selectedEntity: 'payment',
  };

  constructor(props) {
    super(props);

    this.changeFilters = this.changeFilters.bind(this);
  }

  onSubmit = filters => this.collection.setFilters(filters);

  changeFilters({ target }) {
    this.setState({
      selectedEntity: target.value,
    });
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
    const entities = this.props.entities,
      fields = this.props.fields,
      selectedEntity = this.state.selectedEntity,
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
                name="entity_type"
                label="Entity"
                onChange={this.changeFilters}
                defaultValue={this.state.selectedEntity}
              >
                {this.state.entityList.map((entity, index) => (
                  <option key={index} value={entity}>
                    {entity}
                  </option>
                ))}
              </SelectField>
              <SwitchField
                disabledValue="test"
                value="live"
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
              <Field label="From" type="datetime-local" name="from" />
              <Field label="To" type="datetime-local" name="to" />
              <Field label="ID" name="entity.id" />

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
      </div>
    );
  }
}
