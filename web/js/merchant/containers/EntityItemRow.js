import { Component } from 'react';
import { connect } from 'react-redux';

class EnityItemRow extends Component {
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

const mapStateToProps = (state) => {
  return state.app;
};

export default connect(mapStateToProps, null)(EnityItemRow);
