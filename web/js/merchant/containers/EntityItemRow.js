// Todo: delete this file, it's available in @dashboard/shared-ui
import { Component } from 'react';
import { connect } from 'react-redux';

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
    const receiverType = item?.receiver_type || '';
    const sourceChannel = item?.source_channel || '';
    return (
      <tr
        onClick={() => {
          onRowClick?.({
            id,
            rowData: { receiverType, sourceChannel },
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

const mapStateToProps = (state) => {
  return state.app;
};

export default connect(mapStateToProps, null)(EntityItemRow);
