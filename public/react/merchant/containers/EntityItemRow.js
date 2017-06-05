import { Component } from 'react';
import { connect } from 'react-redux';

@connect(state => state.app)
export default class EnityItemRow extends Component {
  render() {
    let { id, activeRowId, luminateRowId } = this.props;

    return (
      <tr
        class={`${activeRowId === id ? 'active' : ''} ${luminateRowId === id ? 'luminate' : ''}`}
      >
        {this.props.children}
      </tr>
    );
  }
}
