import React, { Component } from 'react';
import Form from 'ui/Form';
import EntityTable from './EntityTable';
import Field, {
  SelectField,
  ControlledSwitchField,
  DateTimeField,
} from 'ui/Field';
import { adminFetch } from 'util/fetch';

export default class EntityList extends Component {
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
    const { entities, selectedEntity } = this.props,
      filters = entities && entities[selectedEntity];

    return (
      <div class="list-container">
        <div class="box">
          <header>Entities</header>
          {!entities ? (
            <center>loading...</center>
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
              <ControlledSwitchField
                disabledValue="test"
                enabledValue="live"
                onChange={this.props.onModeChange}
                enabled={this.props.selectedMode === 'live'}
                name="mode"
                label="Live Mode"
              />
              <Field
                label="Count"
                className="small"
                name="count"
                type="number"
                value={this.props.selectedCount}
                onChange={this.props.onSetCount}
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
        <div className="entity-results">
          {this.props.collection.pending ? (
            <center>Loading...</center>
          ) : (
            this.props.collection.items.length > 0 && (
              <EntityTable records={this.props.collection.items} />
            )
          )}
        </div>
      </div>
    );
  }
}
