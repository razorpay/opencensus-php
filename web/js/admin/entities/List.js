import React, { Component } from 'react';
import Form, { serialize } from 'ui/Form';
import Field, { FromField, ToField, SelectField, SwitchField } from 'ui/Field';
import { PageTable } from 'ui/Table';
import { adminFetch } from 'util/fetch';
import Collection from 'model/collection';
import { extendObservable } from 'mobx';
import { observer } from 'mobx-react';
import { Link, withRouter } from 'react-router-dom';

// fetch entity columns
var sharedData;

@withRouter
@observer
export default class EntityList extends Component {
  collection = new Collection({
    data: {
      route_name: 'admin_fetch_entity_multiple',
      mode: this.props.match.params.mode || 'live',
      url_params: {
        type: this.props.match.params.selectedEntity || 'payment',
      },
    },
    fetchFn: adminFetch,
  });

  submit = filters => {
    filters = parseFilters(filters);
    this.updateUrl();
    return this.collection.applyFilters(filters);
  };

  updateUrl = _ =>
    this.props.history.replace(
      `/entities/${this.collection.data.mode}/${this.selectedEntity}`
    );

  componentWillMount() {
    extendObservable(this, {
      pending: !sharedData,
      selectedEntity: this.collection.data.url_params.type,
    });

    this.updateUrl();
    if (!sharedData) {
      adminFetch('admin_fetch_all_entities').then(data => {
        if (data) {
          data.entitiesArray = Object.keys(data.entities).sort();
          sharedData = data;
          this.pending = false;
        }
        return data;
      });
    }
  }

  selectEntity = e => {
    let value = e.target.value;
    this.collection.data.url_params.type = value;
    this.selectedEntity = value;
    this.submit();
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
    this.submit();
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

        if (
          key === 'merchant_id' ||
          (this.selectedEntity === 'merchant' && key === 'id')
        ) {
          return (
            <Link to={`/merchants/${value}`} class="link">
              {value}
            </Link>
          );
        } else if (key === 'id') {
          return (
            <Link
              class="link"
              to={`/entity/${this.selectedEntity}/${
                this.collection.data.mode
              }/${value}`}
            >
              {value}
            </Link>
          );
        } else if (this.selectedEntity === 'payment' && key === 'status') {
          return getStatusPill(value);
        } else if (value && typeof value === 'object') {
          return <pre>{JSON.stringify(value)}</pre>;
        }
        return value;
      },
    ]);
  }

  render() {
    if (this.pending) {
      return <div class="table-pending" />;
    }

    let selectedFilters = sharedData.entities[this.selectedEntity];
    let selectedFiltersArray = [];
    if (selectedFilters) {
      selectedFiltersArray = Object.keys(selectedFilters);
    }

    return (
      <div class="list-container">
        <div class="box entity-container">
          <header>Entities</header>
          <Form onSubmit={this.submit} class="filters">
            <SelectField
              label="Entity"
              value={this.selectedEntity}
              onChange={this.selectEntity}
            >
              {sharedData.entitiesArray.map(e => (
                <option value={e} key={e}>
                  {e}
                </option>
              ))}
            </SelectField>
            <SwitchField
              label="Mode"
              defaultChecked={this.collection.data.mode === 'live'}
              onChange={this.selectMode}
              disabledLabel="Test"
              enabledLabel="Live"
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
            <FromField format="X" />
            <ToField format="X" />
            <Field label="Search Entity Id" onChange={this.selectId} />

            {selectedFiltersArray.map(f => {
              let filterValue = selectedFilters[f];
              if (typeof filterValue === 'string') {
                filterValue = sharedData.fields[filterValue];
              }
              return fieldTypes[filterValue.type](f, filterValue);
            })}

            <button>Go</button>
            <div
              style={{ margin: 'auto 5px 18px 5px' }}
              class="link"
              onClick={this.downloadEntityCsv}
            >
              Download
            </div>
          </Form>
        </div>
        <PageTable model={this.collection} fields={this.fields()} />
      </div>
    );
  }

  downloadEntityCsv = e => {
    let filters = parseFilters(serialize(e.currentTarget.closest('form')));
    window.open(
      `/admin/${this.collection.data.mode}/fetchentity/${
        this.selectedEntity
      }/csv?${Object.keys(filters)
        .map(
          filterName =>
            `${encodeURIComponent(filterName)}=${encodeURIComponent(
              filters[filterName]
            )}`
        )
        .join('&')}`
    );
  };
}

const getStatusPill = value => {
  let className = 'pills ';

  switch (value) {
    case 'open':
    case 'captured':
      className += 'label-success';
      break;
    case 'authorized':
      className += 'label-info';
      break;

    case 'closed':
    case 'failed':
      className += 'label-danger';
      break;
    case 'refunded':
      className += 'label-prime';
      break;
  }

  return <span class={className}>{value}</span>;
};

const parseFilters = filters =>
  filters &&
  Object.keys(filters).reduce((prev, next) => {
    let dotSplit = next.split('.');
    if (dotSplit.length > 1) {
      let nestedFilter =
        (filters[dotSplit[0]] && JSON.parse(filters[dotSplit[0]])) || {};
      nestedFilter[dotSplit[1]] = filters[next];
      prev[dotSplit[0]] = JSON.stringify(nestedFilter);
    } else {
      prev[next] = filters[next];
    }
    return prev;
  }, {});

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
    <SelectField key={name} name={name} label={label}>
      <option value="">All</option>
      <option value="1">Yes</option>
      <option value="0">No</option>
    </SelectField>
  ),
};
