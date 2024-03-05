import React, { Component } from 'react';

class EntityItemRow extends Component {
  render() {
    const {
      id,
      luminateRowId,
      activeEntityId,
      activeSecEntityId,
      rowClasses = '',
      onRowClick,
      item,
      isDisabled,
    } = this.props;

    return (
      <tr
        onClick={() => onRowClick?.(id)}
        className={`${luminateRowId === id ? 'luminate' : ''}${
          activeEntityId === id || activeSecEntityId === id ? ' active' : ''
        }${rowClasses ?? ''}${isDisabled?.(item) ? ' disabled' : ''}`}
        data-testid={`entity-item-row-${id}`}
      >
        {this.props.children}
      </tr>
    );
  }
}

export default EntityItemRow;
