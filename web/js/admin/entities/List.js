import React, { Component } from 'react';
import { Link, withRouter } from 'react-router-dom';
import { extendObservable } from 'mobx';
import { observer } from 'mobx-react';

import { adminFetch } from 'util/fetch';
import { formatDate } from 'util/index';

import Form, { serialize } from 'ui/Form';
import Field, {
  FromField,
  ToField,
  SelectField,
  SwitchField,
  SelectMode,
} from 'ui/Field';
import { PageTable } from 'ui/Table';
import Collection, { defaultFilters } from 'model/collection';
import { statusPill } from 'util/data';

// fetch entity columns
var sharedData;

@withRouter
@observer
export default class EntityList extends Component {
  initialQueryParams = location.search
    .slice(1)
    .split(/&|=/)
    .reduce((p, n, i, a) => {
      if (n && !(i % 2)) {
        p[decodeURIComponent(n)] = decodeURIComponent(a[i + 1]);
      }
      return p;
    }, {});

  collection = new Collection({
    data: {
      route_name: 'admin_fetch_entity_multiple',
      mode: this.props.match.params.mode || 'live',
      url_params: {
        type: this.props.match.params.selectedEntity || 'payment',
      },
    },
    filters: this.initialQueryParams,
    fetchFn: adminFetch,
  });

  submit = filters => {
    filters = parseFilters(filters);

    if (filters) {
      if (filters['entity-id']) {
        const id = filters['entity-id'].trim();
        if (this.selectedEntity === 'merchant') {
          window.open(`/admin/merchants/${id}`);
        } else {
          window.open(`/admin/entity/${this.selectedEntity}/${id}`);
        }

        return;
      }

      this.collection.data.route_name = 'admin_fetch_entity_multiple';

      if (filters['from']) {
        filters['from'] = filters['from'];
      }

      if (filters['to']) {
        filters['to'] = filters['to'];
      }
    }

    let returnPromise = this.collection.applyFilters(filters);

    // depends on updated value of filters
    this.updateUrl();
    return returnPromise;
  };

  updateUrl = _ => {
    let filters = this.collection.filters;
    let query = Object.keys(filters)
      .filter(filter => !(filter in defaultFilters))
      .map(
        filter =>
          `${encodeURIComponent(filter)}=${encodeURIComponent(filters[filter])}`
      )
      .join('&');

    let newLocation = `/entities/${this.collection.data.mode}/${
      this.selectedEntity
    }`;
    if (query) {
      newLocation += '?' + query;
    }
    this.props.history.replace(newLocation);
  };

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

    this.collection.setFilters({});
    this.clearForm(value);

    this.submit();
  };

  clearForm(currentEntity) {
    document.getElementById('entity-form').reset(); // Clear the previous values (It doesn't clear)
    // document.getElementsByName("from")[0].value = ''; // TODO: Clear from and to values explicitly
    // document.getElementsByName("to")[0].value = '';
    document.getElementById('selected-entity').value = currentEntity; // Keep the current selected entity selected
    document.getElementById('entity-mode').value = this.collection.data.mode; // Keep the current mode selected
  }

  selectId = e => {
    let urlParams = this.collection.data.url_params;
    let value = e.target.value;
    if (value) {
      urlParams.id = value;
    } else {
      delete urlParams.id;
    }
  };

  onSelectChange = e => {
    const form = e.currentTarget.closest('form');
    let formData;

    if (form) {
      formData = serialize(form);
    }

    this.submit(formData);
    this.updateUrl();
  };

  onModeChange = e => {
    this.collection.data.mode = e.target.value;
    this.onSelectChange(e);
  };

  morphKey(key) {
    if (
      key.indexOf('amount') > -1 ||
      key.indexOf('fee') > -1 ||
      key.indexOf('tax') > -1 ||
      key.indexOf('charge') > -1
    ) {
      return (key += ' (Paisa)');
    }
    return key;
  }

  fields() {
    var items = this.collection.items;
    if (!items.length) {
      return [];
    }
    return Object.keys(items[0]).map(key => [
      this.morphKey(key),
      item => {
        let value = item[key];

        if (
          key === 'merchant_id' ||
          (this.selectedEntity === 'merchant' && key === 'id')
        ) {
          return (
            <Link to={`/merchants/${value}`} class="link" target="_blank">
              {value}
            </Link>
          );
        } else if (key === 'payment_id' && value) {
          if (value.indexOf('pay_') === -1) {
            value = 'pay_' + value;
          }
          return (
            <Link to={`/entity/payment/${value}`} class="link" target="_blank">
              {value}
            </Link>
          );
        } else if (
          key === 'id' ||
          (key === 'iin' && this.selectedEntity === 'iin')
        ) {
          return (
            <Link
              class="link"
              target="_blank"
              to={`/entity/${this.selectedEntity}/${
                this.collection.data.mode
              }/${value}`}
            >
              {value}
            </Link>
          );
        } else if (key === 'status') {
          return statusPill(value);
        } else if (key.indexOf('_at') > -1 || key.indexOf('_until') > -1) {
          // Value is time
          value = formatDate(value);
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
      selectedFiltersArray = Object.keys(selectedFilters).sort(
        (a, b) => (b.endsWith('_id') ? 1 : -1)
      );
    }

    return (
      <div class="list-container">
        <div class="box entity-container">
          <header>Entities</header>
          <Form
            name="entity-search"
            id="entity-form"
            onSubmit={this.submit}
            class="filters"
          >
            <SelectField
              label="Entity"
              id="selected-entity"
              value={this.selectedEntity}
              onChange={this.selectEntity}
            >
              {sharedData.entitiesArray.map(e => (
                <option value={e} key={e}>
                  {e}
                </option>
              ))}
            </SelectField>
            <SelectMode
              name={null}
              onChange={this.onModeChange}
              id="entity-mode"
              defaultValue={this.collection.data.mode}
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
            <Field
              label="Search Entity Id"
              onChange={this.selectId}
              name="entity-id"
            />

            {selectedFiltersArray.map(f => {
              let filterValue = selectedFilters[f];
              if (typeof filterValue === 'string') {
                filterValue = sharedData.fields[filterValue];
              }
              return fieldTypes[filterValue.type](
                f,
                filterValue,
                this.collection.filters[f],
                this
              );
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
  object: (name, { label, values }, defaultValue, entityList) => (
    <SelectField
      key={name}
      name={name}
      label={label}
      defaultValue={defaultValue}
      onChange={entityList.onSelectChange}
    >
      <option value="">All</option>
      {Object.keys(values).map(value => (
        <option value={value} key={value}>
          {values[value]}
        </option>
      ))}
    </SelectField>
  ),

  array: (name, { label, values }, defaultValue, entityList) => (
    <SelectField
      key={name}
      name={name}
      label={label}
      defaultValue={defaultValue}
      onChange={entityList.onSelectChange}
    >
      <option value="">All</option>
      {values.map(v => (
        <option value={v} key={v}>
          {v}
        </option>
      ))}
    </SelectField>
  ),

  string: (name, { label }, defaultValue, entityList) => (
    <Field key={name} name={name} label={label} defaultValue={defaultValue} />
  ),

  boolean: (name, { label }, defaultValue, entityList) => (
    <SelectField
      key={name}
      name={name}
      label={label}
      defaultValue={defaultValue}
      onChange={entityList.onSelectChange}
    >
      <option value="">All</option>
      <option value="1">Yes</option>
      <option value="0">No</option>
    </SelectField>
  ),
};
