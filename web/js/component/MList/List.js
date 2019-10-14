import React, { Component } from 'react';
import PropTypes from 'prop-types';

export class List extends Component {
  static propTypes = {
    multiSelect: PropTypes.bool,
    theme: PropTypes.oneOf['primary'],
    children: PropTypes.element.isRequired,
  };
  state = {
    selection: [],
  };
  toggleSelection = key => {};
  render() {
    const children = this.props.children;
    return (
      <div>
        {children.map(e => {
          return <div className="mlist">{e}</div>;
        })}
      </div>
    );
  }
}

export default List;
