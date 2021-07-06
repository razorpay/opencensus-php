import { Component } from 'react';
import { connect } from 'react-redux';

class EnityItemRow extends Component {
  render() {
    const { id, luminateRowId, activeEntityId, activeSecEntityId } = this.props;
    return (
      <tr
        className={`${luminateRowId === id ? 'luminate' : null} ${
          activeEntityId === id || activeSecEntityId === id ? 'active' : null
        }`}
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
