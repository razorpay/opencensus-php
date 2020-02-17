import { Component } from 'react';
import { connect } from 'react-redux';

@connect(state => state.app)
export default class EnityItemRow extends Component {
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
