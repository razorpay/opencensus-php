import { Component } from 'react';

import { snakeToTitleCase } from 'common/util';

import Field, { CheckField, SelectField } from 'ui/Field';

export default function ConfigDetails(props) {
  return (
    <div>
      <SelectField
        name="type"
        label={'Report to customized'}
        onChange={props.onReportTypeChange}
      >
        <option value="">Select...</option>
        {types.map(type => (
          <option value={type} key={type}>
            {snakeToTitleCase(type)}
          </option>
        ))}
      </SelectField>

      {props.fields && (
        <>
          <strong>Select Report Fields</strong>
          {Object.keys(props.fields).map(fieldName => (
            <ReportField
              key={fieldName}
              fieldName={fieldName}
              columns={props.fields[fieldName]}
              filters={props.filters[fieldName] || {}}
              selectedFilters={
                !!props.selectedFilters
                  ? props.selectedFilters[fieldName]
                  : undefined
              }
            />
          ))}
        </>
      )}
    </div>
  );
}

function ReportField(props) {
  return (
    <CollapsiblePanel header={snakeToTitleCase(props.fieldName)}>
      {props.columns.map(column => {
        const namePrefix = `fieldsMap.${props.fieldName}.${column}`;
        return (
          <div key={column} class="ReportConfig--inline-fields">
            <div>
              <input
                id={namePrefix + '.present'}
                type="checkbox"
                name={namePrefix + '.present'}
              />
              <label htmlFor={namePrefix + '.present'}>{column}</label>
            </div>
            <div>
              <input type="text" name={namePrefix + '.outputField'} />
            </div>
            <div class="field-filter">
              {props.filters[column] && (
                <FieldFilter
                  filters={props.filters[column]}
                  fixedName={`${props.fieldName}.${column}`}
                  selectedFilter={
                    !!props.selectedFilters
                      ? props.selectedFilters[column]
                      : undefined
                  }
                />
              )}
            </div>
          </div>
        );
      })}
    </CollapsiblePanel>
  );
}

function FieldFilter({ filters, fixedName, ...props }) {
  return (
    <>
      <select name={`filters.${fixedName}.op`}>
        <option value="">Select Filter...</option>
        {filters.op.map(operation => (
          <option value={operation} key={operation}>
            {operation}
          </option>
        ))}
      </select>

      <FieldFilterValues
        fixedName={fixedName}
        ansh={console.log({ fixedName }, props.selectedFilter)}
        filterOp={props.selectedFilter && props.selectedFilter.op}
        values={filters.values}
      />
    </>
  );
}

function FieldFilterValues(props) {
  switch (props.filterOp) {
    case 'IN':
    case 'NOT IN':
      return (
        <div class="field-filter--type-in">
          {props.values.map(val => {
            const name = `filters.${props.fixedName}.values`;
            return (
              <div key={val}>
                <input type="checkbox" name={name} id={name} />
                <label htmlFor={name}>{String(val)}</label>
              </div>
            );
          })}
        </div>
      );
  }
  return <div />;
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
        {this.state.collapsed && this.props.children}
      </div>
    );
  }
}

const types = [
  'transactions',
  'cards',
  'settlements',
  'refunds',
  'orders',
  'disputes',
  'customers',
  'payments',
  'bank_accounts',
  'virtual_accounts',
  'bank_transfers',
  'transfers',
  'invoices',
  'reversals',
  'merchants',
  'tokens',
  'hdfc',
  'terminals',
  'offers',
  'upi',
  'emi_plans',
  'discounts',
  'subscriptions',
  'plans',
  'items',
];
