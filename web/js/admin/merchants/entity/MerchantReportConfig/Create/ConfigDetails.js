import { Component } from 'react';

import { snakeToTitleCase, classList } from 'common/util';

export default class ConfigDetails extends Component {
  state = {
    selectedColumns: {},
  };

  handleColumCheckChange = ({ target }) => {
    const { name, checked } = target;
    this.setState({
      selectedColumns: {
        ...this.state.selectedColumns,
        [name]: checked,
      },
    });
  };

  getValue = () => {
    return this.reportColumns.getValues();
  };

  render() {
    const props = this.props;
    const { selectedColumns } = this.state;
    return (
      <div>
        {props.fields && (
          <>
            <strong>Select Report Fields</strong>
            {Object.keys(props.fields).map(fieldName => (
              <ReportField
                key={`${props.selectedType}.${fieldName}`}
                fieldName={fieldName}
                columns={props.fields[fieldName]}
                onColumnCheckChange={this.handleColumCheckChange}
              />
            ))}

            <ReportColumns
              selectedColumns={Object.keys(selectedColumns).filter(
                column => selectedColumns[column]
              )}
              filters={props.filters}
              ref={ref => (this.reportColumns = ref)}
            />
          </>
        )}
      </div>
    );
  }
}

class ReportColumns extends Component {
  state = {
    outputFields: [],
    fieldsMap: {},
    filters: {},
  };
  references = {};

  constructor(props) {
    super();
    this.state = {
      ...this.state,
      ...this.getInitialState(props.selectedColumns),
    };
  }

  componentWillReceiveProps(nextProps) {
    this.setState({
      ...this.getInitialState(nextProps.selectedColumns),
    });
  }

  handleOutPutFieldChange = ({ target }) => {
    const { name, value } = target;
    this.setState({
      fieldsMap: {
        ...this.state.fieldsMap,
        [name]: value,
      },
    });
  };

  getValues = () => {
    const outputFields = this.state.outputFields.map(
      field => this.state.fieldsMap[field]
    );

    const fieldsMap = Object.keys(this.state.fieldsMap).reduce(
      (newFieldMap, field) => ({
        ...newFieldMap,
        [this.state.fieldsMap[field]]: [field],
      }),
      {}
    );

    const filters = Object.keys(this.state.filters).reduce(
      (newFilter, filterField) => {
        const [field, column] = filterField.split('.');
        if (!this.state.fieldsMap[filterField]) {
          return { ...newFilter };
        }
        return {
          ...newFilter,
          [field]: {
            ...newFilter[field],
            [column]: {
              ...(newFilter[field] && newFilter[field][column]),
              op: this.state.filters[filterField],
              values: this.references[filterField].getValue(),
            },
          },
        };
      },
      {}
    );

    return { output_fields: outputFields, fields_map: fieldsMap, filters };
  };

  handleFilterOpChange = ({ target }) => {
    let { name, value } = target;
    name = name.replace('filters.', '');
    this.setState({
      filters: {
        ...this.state.filters,
        [name]: value,
      },
    });
  };

  moveUp = column => () => {
    this.moveColumn(column, -1);
  };

  moveDown = column => () => {
    this.moveColumn(column, 1);
  };

  moveColumn(column, direction) {
    const outputFields = [...this.state.outputFields];
    const oldIndex = outputFields.indexOf(column);
    const newIndex = oldIndex + direction;
    [outputFields[newIndex], outputFields[oldIndex]] = [
      outputFields[oldIndex],
      outputFields[newIndex],
    ];
    this.setState({ outputFields });
  }

  render() {
    const { outputFields } = this.state;
    const lastIndex = outputFields.length - 1;
    return (
      <div>
        {outputFields.map((column, index) => {
          const [fieldName, columnName] = column.split('.');
          return (
            <div key={column} class="ReportConfig--report-column">
              <div class="label">
                <span>{column}</span>
                <span class="reorder-icons pull-right">
                  {index !== 0 && (
                    <i className="i-arrow-up" onClick={this.moveUp(column)} />
                  )}
                  {lastIndex !== index && (
                    <i
                      className="i-arrow-down"
                      onClick={this.moveDown(column)}
                    />
                  )}
                </span>
              </div>
              <div class="input">
                <input
                  id={column}
                  name={column}
                  value={this.state.fieldsMap[column]}
                  onChange={this.handleOutPutFieldChange}
                />
              </div>
              <div class="filter-op">
                {!!this.props.filters[fieldName] &&
                  !!this.props.filters[fieldName][columnName] && (
                    <select
                      name={`filters.${column}`}
                      onChange={this.handleFilterOpChange}
                    >
                      <option value="">Select Filter...</option>
                      {this.props.filters[fieldName][columnName].op.map(
                        operation => (
                          <option key={operation} value={operation}>
                            {operation}
                          </option>
                        )
                      )}
                    </select>
                  )}
              </div>
              <div className="filter-value">
                {!!this.state.filters[column] && (
                  <FilterValue
                    filterOp={this.state.filters[column]}
                    values={this.props.filters[fieldName][columnName].values}
                    column={column}
                    ref={ref => (this.references[column] = ref)}
                  />
                )}
              </div>
            </div>
          );
        })}
      </div>
    );
  }

  getInitialState = (selectedColumns = []) => {
    const fieldsMap = {};
    const outputFields = [];
    selectedColumns.forEach(selectedCol => {
      outputFields.push(selectedCol);
      if (this.state.outputFields.indexOf(selectedCol) < 0) {
        fieldsMap[selectedCol] = selectedCol;
      } else {
        // retaining state for existing items
        fieldsMap[selectedCol] = this.state.fieldsMap[selectedCol];
      }
    });

    return { outputFields, fieldsMap };
  };
}

function ReportField(props) {
  return (
    <CollapsiblePanel header={snakeToTitleCase(props.fieldName)}>
      <div className="ReportConfig--inline-fields">
        {props.columns.map(column => {
          const name = `${props.fieldName}.${column}`;
          return (
            <div key={column}>
              <input
                id={name}
                type="checkbox"
                name={name}
                onChange={props.onColumnCheckChange}
              />
              <label htmlFor={name}>{column}</label>
            </div>
          );
        })}
      </div>
    </CollapsiblePanel>
  );
}

class FilterValue extends Component {
  getValue() {
    const name = `${this.props.column}.filter-value-`;
    switch (this.props.filterOp) {
      case 'IN':
        return this.props.values.filter(
          val => document.getElementById(`${name}${val}`).checked
        );
      case '<':
      case '>':
        return [Number(document.getElementById(`${name}compare`).value) || 0];
      case 'BETWEEN':
        const value0 =
          Number(document.getElementById(`${name}between-0`).value) || 0;
        const value1 =
          Number(document.getElementById(`${name}between-1`).value) || 0;
        return [value0, value1];
    }
  }

  render() {
    const column = this.props.column;
    let name;
    switch (this.props.filterOp) {
      case 'IN':
      case 'NOT IN':
        return (
          <div class="filter-value--type-in">
            {this.props.values.map(val => {
              name = `${column}.filter-value-${val}`;
              return (
                <div key={name}>
                  <input type="checkbox" name={name} id={name} />
                  <label htmlFor={name}>{String(val)}</label>
                </div>
              );
            })}
          </div>
        );
      case '<':
      case '>':
        name = `${column}.filter-value-compare`;
        return (
          <div class="filter-value--type-compare">
            <input id={name} type="text" name={name} />
          </div>
        );
      case 'BETWEEN':
        name = `${column}.filter-value-between-`;
        return (
          <div className="filter-value--type-between">
            <input type="text" name={`${name}0`} id={`${name}0`} />
            <input type="text" name={`${name}1`} id={`${name}1`} />
          </div>
        );
    }
    return null;
  }
}

class CollapsiblePanel extends Component {
  constructor(props) {
    super();
    this.state = {
      collapsed: !!props.initiallyNotCollapsed,
    };
  }

  toggleCollapsed = () => {
    this.setState({
      collapsed: !this.state.collapsed,
    });
  };

  render() {
    return (
      <div class="collapsible">
        <div onClick={this.toggleCollapsed} class="heading clearfix">
          <span class="pull-left">{this.props.header}</span>
          <i class="i-arrow-down pull-right" />
        </div>
        <div
          class={classList(
            'collapsible--body',
            !!this.state.collapsed ? 'open' : 'close'
          )}
        >
          {this.props.children}
        </div>
      </div>
    );
  }
}
