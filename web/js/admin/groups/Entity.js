import React, { Component } from 'react';
import Field, { SelectField, SelectMethod } from 'ui/Field';
import { replaceSlider, closeSlider } from 'common/modal';
import { adminPost, adminFetch, adminPut, adminDelete } from 'util/fetch';
import { notifyError, notifySuccess, notifyDone } from 'common/modal';
import Table from 'ui/Table';
import Form from 'ui/Form';
import { prevent } from 'util/index';

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

  save = body => {
    let self = this;
    let { model } = self.props;
    let data = { body };
    let request = null;

    data.body.parents = self.state.parents.map(p => p.id);

    if (model) {
      data.url_params = {
        groupId: self.props.model.id,
      };
      data.route_name = 'edit_group';
      request = adminPut;
    } else {
      data.route_name = 'group_create';
      request = adminPost;
    }

    return request({
      ...data,
    }).then(response => {
      if (response) {
        if (!model) {
          self.addItem(response);
        }
        notifySuccess('Success!');
      }
      closeSlider();
    });
  };

  componentWillMount() {
    let { model } = this.props;
    let potentialParents = [];

    if (model) {
      let requests = [
        this._fetchFn('group_get_allowed_groups'),
        this._fetchFn('group_get'),
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
      adminFetch({ route_name: 'group_get_multiple' }).then(response => {
        if (response) {
          this.setState({ potentialParents: response.items, pending: false });
        }
      });
    }
  }

  _fetchFn = route_name => {
    return adminFetch({
      route_name,
      url_params: {
        groupId: this.props.model.id,
      },
    });
  };

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
    if (this.state.pending) {
      return <div class="spinner" />;
    }

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
  replaceSlider(<EditGroup collection={collection} model={this} />);
}

export function removeEntity(e) {
  prevent(e);
  let params = {
    route_name: 'group_delete',
    url_params: {
      groupId: this.id,
    },
  };

  adminDelete(params).then(response => {
    notifyDone();
    this.collection.items.remove(this);
  });
}

const fields = [
  ['Group Id', item => item.id],
  ['Name', item => item.name],
  ['Description', item => item.description],
];
