import { Component } from 'react';

import { snakeToTitleCase, classList } from 'common/util';
import { openModal, closeModal, notifyError } from 'common/modal';
import { ModalContent } from 'component/Modal';
import { isPresent } from 'rzp/utils/rzp-utils';

import AddCustomNote from './AddCustomNote';
import AddConstantField from './AddConstantField';
import { dotToString } from './index';

export default class ConfigDetails extends Component {
  constructor(props) {
    super();
    this.state = {
      selectedColumns: Object.keys(props.fieldsMap).reduce(
        (allColumns, column) => ({
          ...allColumns,
          [column]: true,
        }),
        {}
      ),
    };
    this.fieldsWithNotes = haveNotesField(props.fields);
  }

  handleColumCheckChange = ({ target }) => {
    const { name, checked } = target;
    this.setState({
      selectedColumns: {
        ...this.state.selectedColumns,
        [name]: checked,
      },
    });
  };

  componentWillReceiveProps(nextProps) {
    if (
      nextProps.loadingConfigComponents &&
      !this.props.loadingConfigComponents
    ) {
      this.setState({ selectedColumns: {} });
    }

    this.fieldsWithNotes = haveNotesField(nextProps.fields);
  }

  getValue = () => {
    return this.reportColumns ? this.reportColumns.getValues() : {};
  };

  toggleField = (field, status) => {
    this.setState({
      selectedColumns: {
        ...this.state.selectedColumns,
        [field]: status,
      },
    });
  };

  render() {
    const props = this.props;
    const { selectedColumns } = this.state;

    return (
      <div class="row-item">
        {!props.loading && props.fields ? (
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

            {isPresent(props.fields) && (
              <ReportColumns
                selectedColumns={Object.keys(selectedColumns).filter(
                  column => selectedColumns[column]
                )}
                fieldsMap={this.props.fieldsMap}
                fieldsWithNotes={this.fieldsWithNotes}
                toggleField={this.toggleField}
                availableFilters={props.availableFilters}
                filters={props.filters}
                ref={ref => (this.reportColumns = ref)}
              />
            )}
          </>
        ) : (
          <div class="text-center">
            {props.loadingConfigComponents
              ? 'Fetching Columns...'
              : 'Select Type Report Type to see columns'}
          </div>
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
    super(props);
    this.state = {
      ...this.state,
      ...this.getInitialState(props.selectedColumns, 'props'),
      filters: props.filters || {},
    };
  }

  componentWillReceiveProps(nextProps) {
    this.setState({
      ...this.getInitialState(nextProps.selectedColumns, 'state'),
    });
  }

  handleAddNotesClick = () => {
    openModal(
      <ModalContent header="Add Custom Note Field">
        <AddCustomNote
          onSave={this.handleCustomNoteSave}
          fields={this.props.fieldsWithNotes}
        />
      </ModalContent>
    );
  };

  handleAddConstantClick = () => {
    openModal(
      <ModalContent header="Add Constant Field">
        <AddConstantField onSave={this.handleConstantSave} />
      </ModalContent>
    );
  };

  handleCustomNoteSave = values => {
    const fieldName = `${values.field}.notes.${values.subColumn}`;
    if (this.state.outputFields.indexOf(fieldName) > -1) {
      notifyError('Field already exist in config');
    } else {
      this.props.toggleField(fieldName, true);
      closeModal();
    }
  };

  handleConstantSave = values => {
    const fieldName = `constant:${values.field}`;
    this.props.toggleField(fieldName, true);
    closeModal();
  };

  handleRemoveClick = fieldName => () => {
    this.props.toggleField(fieldName, false);
  };

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
      (otherFields, field) => ({
        ...otherFields,
        ...(Object.keys(this.state.filters[field]).reduce(
          (otherColumns, column) => {
            if (!this.state.fieldsMap[filterField]) {
              return { ...otherColumns };
            }

            return {
              ...otherColumns,
              op: this.state.filters[field][column].op,
              values: this.references[`${field}.${column}`].getValue(),
            };
          }
        ),
        {}),
      }),
      {}
    );

    return { output_fields: outputFields, fields_map: fieldsMap, filters };
  };

  handleFilterOpChange = ({ target }) => {
    let { name, value } = target;
    const filters = { ...this.state.filters };
    name = name.replace('filters.', '');
    dotToString(name, value, filters);
    if (!value) delete filters[name];
    this.setState({ filters });
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
        <div class="m-t m-b">
          <strong>
            {!!outputFields.length
              ? 'Customized your selected Columns'
              : 'Select Columns to be customized'}
          </strong>&nbsp; (<em>
            <small>Mention all amount fields in paise</small>
          </em>)
        </div>

        {/* column headers */}
        <div class="ReportConfig--report-column">
          <div class="label">
            <strong>Entity.Column</strong>
          </div>
          <div class="input">
            <strong>Column Name</strong>
          </div>
          <div class="filter-op">
            <strong>Filter</strong>
          </div>
          <div class="filter-value">
            <strong>Filter Values</strong>
          </div>
        </div>

        {outputFields.map((column, index) => {
          const [fieldName, columnName] = column.split('.');
          return (
            <div key={column} class="ReportConfig--report-column">
              <div class="label">
                <span onClick={this.handleRemoveClick(column)}>
                  <i class="i-close" />
                </span>
                <span class="m-l">{column}</span>
                <span class="reorder-icons pull-right">
                  <i
                    class={classList(
                      'i-arrow-up',
                      index === 0 && 'visibility-hidden'
                    )}
                    onClick={this.moveUp(column)}
                  />
                  <i
                    class={classList(
                      'i-arrow-down',
                      lastIndex === index && 'visibility-hidden'
                    )}
                    onClick={this.moveDown(column)}
                  />
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
                {!!this.props.availableFilters[fieldName] &&
                  !!this.props.availableFilters[fieldName][columnName] && (
                    <select
                      name={`filters.${column}.op`}
                      onChange={this.handleFilterOpChange}
                      defaultValue={
                        (
                          (this.state.filters[fieldName] || {})[columnName] ||
                          {}
                        ).op
                      }
                    >
                      <option value="">Select Filter...</option>
                      {this.props.availableFilters[fieldName][
                        columnName
                      ].op.map(operation => (
                        <option key={operation} value={operation}>
                          {operation}
                        </option>
                      ))}
                    </select>
                  )}
              </div>
              <div className="filter-value">
                {!!((this.state.filters[fieldName] || {})[columnName] || {})
                  .op && (
                  <FilterValue
                    filterOp={this.state.filters[fieldName][columnName].op}
                    avlblValues={
                      this.props.availableFilters[fieldName][columnName].values
                    }
                    column={column}
                    values={this.state.filters[fieldName][columnName].values}
                    ref={ref => (this.references[column] = ref)}
                  />
                )}
              </div>
            </div>
          );
        })}
        {!!this.props.fieldsWithNotes.length && (
          <button class="btn" onClick={this.handleAddNotesClick}>
            Add Custom Notes Field
          </button>
        )}
        <button class="btn" onClick={this.handleAddConstantClick}>
          Add Constant Field
        </button>
      </div>
    );
  }

  getInitialState = (selectedColumns = [], checkAgainstField) => {
    const fieldsMap = {};
    const outputFields = [];

    const original = this[checkAgainstField];

    selectedColumns.forEach(selectedCol => {
      outputFields.push(selectedCol);
      if (
        checkAgainstField === 'state' &&
        this.state.outputFields.indexOf(selectedCol) < 0
      ) {
        fieldsMap[selectedCol] = selectedCol;
      } else {
        // retaining state for existing items
        fieldsMap[selectedCol] = original.fieldsMap[selectedCol];
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
      case 'NOT IN':
        return this.props.avlblValues.filter(
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
    const { column, values } = this.props;
    let name;
    switch (this.props.filterOp) {
      case 'IN':
      case 'NOT IN':
        return (
          <div class="filter-value--type-in">
            {this.props.avlblValues.map(val => {
              name = `${column}.filter-value-${val}`;
              return (
                <div key={name}>
                  <input
                    defaultChecked={values.indexOf(val) >= 0}
                    type="checkbox"
                    name={name}
                    id={name}
                  />
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
            <input defaultValue={values[0]} id={name} type="text" name={name} />
          </div>
        );
      case 'BETWEEN':
        name = `${column}.filter-value-between-`;
        return (
          <div className="filter-value--type-between">
            <input
              defaultValue={values[0]}
              type="text"
              name={`${name}0`}
              id={`${name}0`}
            />
            <input
              defaultValue={values[1]}
              type="text"
              name={`${name}1`}
              id={`${name}1`}
            />
          </div>
        );
    }
    return null;
  }
}

// need to shift this to some common place
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
    const collapsed = this.state.collapsed;
    return (
      <div class="collapsible">
        <div onClick={this.toggleCollapsed} class="heading clearfix">
          <span class="pull-left">{this.props.header}</span>
          <i class={`i-arrow-${collapsed ? 'up' : 'down'} pull-right`} />
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

// helper methods
function haveNotesField(fields = {}) {
  return Object.keys(fields).filter(
    field => fields[field].indexOf('notes') > -1
  );
}
