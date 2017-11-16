import React, { Component } from 'react';

import Definition from 'rzp/ui/Definition';

import { tabsMeta } from './data';

class Panel extends Component {
  constructor(props) {
    super(props);

    this.meta = tabsMeta[props.tabName];
    this.handleGroupingChange = this.handleGroupingChange.bind(this);
  }

  handleGroupingChange(e) {
    const { tabName, onGroupingChange } = this.props;

    return onGroupingChange && onGroupingChange(tabName, e.target.value);
  }

  render() {
    const { selectedGrouping } = this.props,
      { grouping, options } = this.meta;

    return (
      <div className="clearfix panel">
        <div className="pull-left">
          <Definition>
            <h3>+ 1234</h3>
            <span className="text-fade">As compared to:</span>
          </Definition>
        </div>
        <div className="pull-right">
          {grouping.length > 0 && (
            <select
              value={selectedGrouping}
              onChange={this.handleGroupingChange}
            >
              {grouping.map((item, index) => {
                return (
                  <option value={item.value} key={index}>
                    {item.text}
                  </option>
                );
              })}
            </select>
          )}
          {options.length > 0 && <button>...</button>}
        </div>
      </div>
    );
  }
}

export default Panel;
