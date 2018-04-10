import React, { Component } from 'react';
import { observable, extendObservable, action } from 'mobx';
import { observer } from 'mobx-react';
import { adminFetch, adminPost, adminPut, adminDelete } from 'common/fetch';
import { notifySuccess, notifyError } from 'common/modal';
import UserForm from './UserForm';
import { isWorkflow } from 'common/util';
import { prevent } from 'common/util';

@observer
export default class EditUser extends Component {
  // all available groups
  allGroups = observable.map();

  // selected actions
  groups = observable.map();

  // TODO: TEST Check what's orgId. 'org_fieldmap_get_by_entity' in api-route-map
  fetchFieldMapsParams = {
    url: `live/field-map/entity/admin`,
  };

  fetchUserParams = {
    url: `live/admin/${this.props.match.params.id}/fetch`,
  };

  componentWillMount() {
    extendObservable(this, { pending: true });
    let { id } = this.props.match.params;
    let requests = ['live/groups', 'live/roles', this.fetchFieldMapsParams];

    if (id !== 'new') {
      requests.push(this.fetchUserParams);
    }

    Promise.all(requests.map(r => adminFetch(r))).then(
      action(([allGroups, allRoles, fieldMaps, user = null]) => {
        this.allRoles = allRoles.items;
        //create map of all groups {group_id: group_obj}
        allGroups.items.forEach(g => this.allGroups.set(g.id, g));

        if (user) {
          //create map of selected groups {group_id: group_obj}
          user.groups.forEach(g => this.groups.set(g.id, g));
          this.user = user;
        }

        this.fields = fieldMaps.fields;
        this.pending = false;
      })
    );
  }

  selectAllGroups = e => {
    let { groups, allGroups } = this;
    groups.clear();
    if (e.target.checked) {
      allGroups.entries().forEach(g => groups.set(g[0], g[1]));
    }
  };

  toggleGroup = groupId => {
    let { groups, allGroups } = this;
    let isThere = groups.has(groupId);

    if (isThere) {
      groups.delete(groupId);
    } else {
      groups.set(groupId, allGroups.get(groupId));
    }
  };

  save = body => {
    let { id } = this.props.match.params;
    let { groups } = this;
    let request = null;

    let successMsg, url;

    if (id !== 'new') {
      url = `live/admin/${id}`;
      request = adminPut;
      successMsg = 'User is created successfully';
    } else {
      url = 'live/admins';
      request = adminPost;
      successMsg = 'User is updated successfully';
    }

    body.groups = groups.keys();
    if (body.roles) {
      body.roles = body.roles.split(',');
    }

    return request({
      url,
      data: body,
    }).then(response => {
      if (response) {
        if (isWorkflow(response)) {
          notifySuccess('Workflow created successfully');
          return;
        }

        notifySuccess(successMsg);
      }
    });
  };

  render() {
    if (this.pending) {
      return <div class="spinner center" />;
    }

    return (
      <UserForm
        fields={this.fields}
        user={this.user}
        groups={this.groups}
        roles={this.user && this.user.roles}
        allGroups={this.allGroups}
        allRoles={this.allRoles}
        toggleGroup={this.toggleGroup}
        selectAllGroups={this.selectAllGroups}
        onSubmit={this.save}
      />
    );
  }
}

export function removeEntity(e) {
  prevent(e);

  return adminDelete(`live/admin/${this.id}`).then(response => {
    if (response) {
      if (isWorkflow(response)) {
        notifySuccess('Workflow created successfully');
        return;
      }

      notifySuccess('User delete successfully');
      this.collection.remove(this);
    }
  });
}
