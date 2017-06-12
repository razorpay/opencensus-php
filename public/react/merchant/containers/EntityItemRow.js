import { Component } from 'react';
import { connect } from 'react-redux';

@connect(state => state.app)
export default class EnityItemRow extends Component {
  render() {
    let { id, luminateRowId, activeEntityId } = this.props;

    return (
      <tr
        class={`${luminateRowId === id ? 'luminate' : null} ${activeEntityId === id ? 'active' : null}`}
      >
        {this.props.children}
      </tr>
    );
  }
}
