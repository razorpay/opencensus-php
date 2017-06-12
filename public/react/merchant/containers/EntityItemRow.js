import { Component } from 'react';
import { connect } from 'react-redux';

@connect(state => state.app)
export default class EnityItemRow extends Component {
  render() {
    let { id, luminateRowId } = this.props;

    return (
      <tr class={luminateRowId === id ? 'luminate' : null}>
        {this.props.children}
      </tr>
    );
  }
}
