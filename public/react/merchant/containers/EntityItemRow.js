import { Component } from 'react';
import { connect } from 'react-redux';
import { setActiveRow } from 'merchant/modules/app';

@connect(state => state.app, { setActiveRow })
export default class EnityItemRow extends Component {
  // Highlight the row only on anchor clicks
  handleClick = event => {
    if (event.target.closest('a[href]')) {
      this.props.setActiveRow(this.props.id);
    }
  };

  render() {
    let { id, activeRowId, luminateRowId } = this.props;

    return (
      <tr
        class={`${activeRowId === id ? 'active' : ''} ${luminateRowId === id ? 'luminate' : ''}`}
        onClick={this.handleClick}
      >
        {this.props.children}
      </tr>
    );
  }
}
