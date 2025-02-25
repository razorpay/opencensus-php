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
    const paymentMethod = item?.method || '';
    const sourceChannel = item?.source_channel || '';
    return (
      <tr
        onClick={() => {
          onRowClick?.({
            id,
            rowData: { paymentMethod, sourceChannel },
          });
        }}
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
