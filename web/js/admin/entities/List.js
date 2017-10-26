import React, { Component } from 'react';
import Form from 'ui/Form';
import Field, { FromField, ToField, SelectField, SwitchField } from 'ui/Field';
import { PageTable } from 'ui/Table';
import { adminFetch } from 'util/fetch';
import Collection from 'model/collection';
import { observer } from 'mobx-react';
import { Link } from 'react-router-dom';

// fetch entity columns
var sharedData;

@observer
export default class EntityList extends Component {
  state = {
    pending: !sharedData,
    selectedEntity: 'payment',
  };

  collection = new Collection({
    data: {
      route_name: 'admin_fetch_entity_multiple',
      mode: 'live',
      url_params: {
        type: 'payment',
      },
    },
    fetchFn: adminFetch,
  });

  onSubmit = filters => {
    return this.collection.setFilters(filters);
  };

  componentWillMount() {
    if (!sharedData) {
      adminFetch('admin_fetch_all_entities').then(data => {
        if (data) {
          data.entitiesArray = Object.keys(data.entities).sort();
          sharedData = data;
          this.setState({
            pending: false,
          });
        }
        return data;
      });
    }
  }

  selectEntity = e => {
    let value = e.target.value;
    this.collection.data.url_params.type = value;
    this.setState({ selectedEntity: value });
  };

  selectId = e => {
    let urlParams = this.collection.data.url_params;
    let value = e.target.value;
    if (value) {
      urlParams.id = value;
    } else {
      delete urlParams.id;
    }
  };

  selectMode = e => {
    this.collection.data.mode = e.target.value;
  };

  fields() {
    var items = this.collection.items;
    if (!items.length) {
      return [];
    }
    return Object.keys(items[0]).map(key => [
      key,
      item => {
        let value = item[key];
        if (key === 'merchant_id') {
          value = (
            <Link class="link" to={'/merchants/' + value}>
              {value}
            </Link>
          );
        } else if (value && typeof value === 'object') {
          value = <pre>{JSON.stringify(value)}</pre>;
        }
        return value;
      },
    ]);
  }

  render() {
    if (this.state.pending) {
      return <div class="table-pending" />;
    }

    let selectedFilters = sharedData.entities[this.state.selectedEntity];
    let selectedFiltersArray = [];
    if (selectedFilters) {
      selectedFiltersArray = Object.keys(selectedFilters);
    }

    return (
      <div class="list-container">
        <div class="box">
          <header>Entities</header>
          <Form onSubmit={this.onSubmit} class="filters">
            <SelectField
              label="Entity"
              value={this.state.selectedEntity}
              onChange={this.selectEntity}
            >
              {sharedData.entitiesArray.map(e => (
                <option value={e} key={e}>
                  {e}
                </option>
              ))}
            </SelectField>
            <SwitchField
              label="Live Mode"
              defaultChecked
              onChange={this.selectMode}
              disabledValue="test"
              enabledValue="live"
            />
            <Field
              class="small"
              label="Count"
              type="number"
              name="count"
              min="10"
              max="1000"
              step="10"
              defaultValue="20"
            />
            <FromField />
            <ToField />
            <Field label="Search" onChange={this.selectId} />

            {selectedFiltersArray.map(f => {
              let filterValue = selectedFilters[f];
              if (typeof filterValue === 'string') {
                filterValue = sharedData.fields[filterValue];
              }
              return fieldTypes[filterValue.type](f, filterValue);
            })}

            <button>Go</button>
          </Form>
        </div>
        <PageTable model={this.collection} fields={this.fields()} />
      </div>
    );
  }
}

const fieldTypes = {
  object: (name, { label, values }) => (
    <SelectField key={name} name={name} label={label}>
      <option value="">All</option>
      {Object.keys(values).map(value => (
        <option value={value} key={value}>
          {values[value]}
        </option>
      ))}
    </SelectField>
  ),

  array: (name, { label, values }) => (
    <SelectField key={name} name={name} label={label}>
      <option value="">All</option>
      {values.map(v => (
        <option value={v} key={v}>
          {v}
        </option>
      ))}
    </SelectField>
  ),

  string: (name, { label }) => <Field key={name} name={name} label={label} />,

  boolean: (name, { label }) => (
    <SwitchField knob key={name} name={name} label={label} />
  ),
};
