import React, { Component } from 'react';
import Table from 'ui/Table';
import { adminFetch } from 'util/fetch';
import { openModal } from 'common/modal';
import BaseModal from 'ui/BaseModal';

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
          old: JSON.stringify(response.old[key]),
          new: JSON.stringify(response.new[key]),
        });
      });

      this.setState({ items, pending: false });
    });
  }

  render() {
    return (
      <BaseModal header="Changes">
        {this.state.pending ? (
          <div class="spinner center" />
        ) : (
          <Table items={this.state.items} fields={fields} />
        )}
      </BaseModal>
    );
  }
}

// Resources
const fields = [
  ['Property', item => item.property],
  ['Old Value', renderDiffRow('old')],
  ['New Value', renderDiffRow('new')],
];

function renderDiffRow(type) {
  return function(item, index) {
    if (item[type] instanceof Array) {
      if (!item[type].length) {
        return '--';
      }
      return (
        <ul>
          {item[type].map(value => {
            return Object.keys(value).map(key => (
              <li key={type + '-' + key + '-' + index + '-' + key}>
                <b>{key}:</b> {value[key]}
              </li>
            ));
          })}
        </ul>
      );
    } else {
      return item[type];
    }
  };
}
