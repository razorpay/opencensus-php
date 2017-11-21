import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { observable, extendObservable, action } from 'mobx';
import { observer } from 'mobx-react';
import { adminFetch, adminPost, adminPut, adminDelete } from 'util/fetch';
import { notifyDone } from 'common/modal';
import UserForm from './UserForm';
import { isWorkflow } from 'util/index';
import { prevent } from 'util/index';

@withRouter
@observer
export default class EditUser extends Component {
  // all available groups
  allGroups = observable.map();

  // selected actions
  groups = observable.map();

  fetchFieldMapsParams = {
    route_name: 'org_fieldmap_get_by_entity',
    url_params: {
      entity: 'admin',
    },
  };

  fetchUserParams = {
    route_name: 'admin_get',
    url_params: {
      adminId: this.props.match.params.id,
    },
  };

  componentWillMount() {
    extendObservable(this, { pending: true });
    let { id } = this.props.match.params;
    let requests = [
      'group_get_multiple',
      'role_get_multiple',
      this.fetchFieldMapsParams,
    ];

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
    let data = { body };
    let request = null;

    if (id !== 'new') {
      data.route_name = 'admin_edit';
      data.url_params = { adminId: id };
      request = adminPut;
    } else {
      data.route_name = 'admin_create';
      request = adminPost;
    }

    data.body.groups = groups.keys();
    data.body.roles = data.body.roles.split(',');

    return request(data).then(response => {
      if (response) {
        if (isWorkflow(response, this.props.history)) {
          return;
        }
        notifyDone();
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
  let params = {
    route_name: 'admin_delete',
    url_params: {
      adminId: this.id,
    },
  };

  return adminDelete(params).then(response => {
    if (response) {
      this.collection.items.remove(this);
      notifyDone();
    }
  });
}
