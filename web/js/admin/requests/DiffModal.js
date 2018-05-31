import React, { Component } from 'react';
import Table from 'ui/Table';
import { adminFetch } from 'common/fetch';
import { openModal } from 'common/modal';
import { ModalContent } from 'component/Modal';

export default class DiffModal extends Component {
  state = {
    items: [],
    pending: true,
  };

  componentWillMount() {
    const { id } = this.props;

    adminFetch(`live/w-actions/${id}/diff`).then(response => {
      let items = [...this.state.items];

      Object.keys(response.new).forEach(key => {
        const isUrl = key.indexOf('url') > -1;
        items.push({
          property: key,
          old: isUrl ? (
            <a href={response.old[key]} target="_blank" class="diff-link">
              {response.old[key]}
            </a>
          ) : (
            JSON.stringify(response.old[key])
          ),
          new: isUrl ? (
            <a href={response.new[key]} target="_blank" class="diff-link">
              {response.new[key]}
            </a>
          ) : (
            JSON.stringify(response.new[key])
          ),
        });
      });

      this.setState({ items, pending: false });
    });
  }

  render() {
    return (
      <ModalContent header="Changes">
        {this.state.pending ? (
          <div class="spinner center" />
        ) : (
          <Table items={this.state.items} fields={fields} />
        )}
      </ModalContent>
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
