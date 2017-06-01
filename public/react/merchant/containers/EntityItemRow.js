import { Component } from 'react';
import { connect } from 'react-redux';
import { setActiveRow, removeActiveRow } from 'merchant/modules/app';

@connect(state => state.app, { setActiveRow, removeActiveRow })
export default class EnityItemRow extends Component {
  // Highlight the row only on anchor clicks
  handleClick = event => {
    if (event.target.closest('a[href]')) {
      this.props.setActiveRow(this.props.id);
    }
  };

  componentWillUnmount() {
    let { id, activeRowId } = this.props;
    if (id === activeRowId) {
      this.props.removeActiveRow();
    }
  }

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
