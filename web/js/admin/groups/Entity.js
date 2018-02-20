import React, { Component } from 'react';
import { SelectField, SelectMethod } from 'ui/Field';
import { openModal, closeModal } from 'common/modal';
import { adminPost, adminFetch, adminPut, adminDelete } from 'common/fetch';
import { notifyError, notifySuccess, notifyDone } from 'common/modal';
import { prevent } from 'common/util';

import GroupForm from './GroupForm';

class EditGroup extends Component {
  state = {
    parents: [],
    potentialParents: [],
    group: null,
    pending: true,
  };

  addItem = item => {
    this.props.collection.items.push(item);
  };

  save = data => {
    let self = this;
    let { model } = self.props;
    let request = null;

    data.parents = self.state.parents.map(p => p.id);

    let url;
    if (model) {
      url = `live/groups/${self.props.model.id}`;
      request = adminPut;
    } else {
      url = 'live/groups';
      request = adminPost;
    }

    return request({
      url,
      data,
    }).then(response => {
      if (response) {
        if (!model) {
          self.addItem(response);
        }
        notifySuccess('Group is created successfully!');
      }
      closeModal();
    });
  };

  componentWillMount() {
    let { model } = this.props;
    let potentialParents = [];

    if (model) {
      let requests = [
        this._fetchFn('groups/{groupId}/allowed_groups'),
        this._fetchFn('groups/{groupId}'),
      ];

      Promise.all(requests).then(([allowedGroups, group]) => {
        this.setState({
          potentialParents: allowedGroups,
          group: group,
          parents: group.parents,
          pending: false,
        });
      });
    } else {
      adminFetch('live/groups').then(response => {
        if (response) {
          this.setState({ potentialParents: response.items, pending: false });
        }
      });
    }
  }

  _fetchFn = url => adminFetch(url.replace('{groupId}', this.props.model.id));

  selectParent = e => {
    this.state.potentialParents.some(p => {
      if (p.id === e.target.value) {
        this.setState({
          parents: this.state.parents.concat(p),
          potentialParents: this.state.potentialParents.filter(
            q => q.id !== p.id
          ),
        });
        return 1;
      }
    });
  };

  deleteParent = p => {
    this.setState({
      potentialParents: this.state.potentialParents.concat(p),
      parents: this.state.parents.filter(q => q.id !== p.id),
    });
  };

  render() {
    return (
      <GroupForm
        {...this.state}
        onSelectParent={this.selectParent}
        onDeleteParent={this.deleteParent}
        onSubmit={this.save}
      />
    );
  }
}

export function showEntity(collection) {
  openModal(<EditGroup collection={collection} model={this} />);
}

export function removeEntity(e) {
  prevent(e);

  return adminDelete(`live/groups/${this.id}`).then(response => {
    notifyDone();
    this.collection.items.remove(this);
  });
}

const fields = [
  ['Group Id', item => item.id],
  ['Name', item => item.name],
  ['Description', item => item.description],
];
