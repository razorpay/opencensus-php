import React, { Component } from 'react';
import Table from 'ui/Table';
import { adminFetch } from 'util/fetch';
import { openModal } from 'common/modal';

export default class DiffModal extends Component {
  state = {
    items: [],
    pending: true,
  };

  componentWillMount() {
    const { id } = this.props;

    adminFetch({
      route_name: 'action_diff_get',
      url_params: {
        id,
      },
    }).then(response => {
      let items = [...this.state.items];

      Object.keys(response.new).forEach(key => {
        items.push({
          property: key,
          old: String(response.old[key]),
          new: String(response.new[key]),
        });
      });

      this.setState({ items, pending: false });
    });
  }

  render() {
    if (this.state.pending) {
      return <div class="spinner" />;
    }

    return (
      <div>
        <header>Changes</header>
        <Table items={this.state.items} fields={fields} />
      </div>
    );
  }
}

// Resources
const fields = [
  ['Property', item => item.property],
  ['Old Value', item => item.old],
  ['New Value', item => item.new],
];
