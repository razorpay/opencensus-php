import React, { Component } from 'react';
import { observable, extendObservable, action } from 'mobx';
import { observer } from 'mobx-react';
import { adminFetch, adminPost, adminPut } from 'util/fetch';
import { notifyDone } from 'common/modal';
import UserForm from './UserForm';

@observer
export default class EditUser extends Component {
  // all available roles
  allRoles = observable.map();
  allGroups = observable.map();

  // selected actions
  groups = observable.map();
  roles = observable.map();

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
        let self = this;

        //create map of all roles {role_id: role_name}
        allRoles.items.forEach(r => self.allRoles.set(r.id, r.name));

        //create map of all groups {group_id: group_obj}
        allGroups.items.forEach(g => self.allGroups.set(g.id, g));

        if (user) {
          //create map of selected groups {group_id: group_obj}
          user.groups.forEach(g => self.groups.set(g.id, g));

          //If user, than remove selected roles from all roles map
          user.roles.forEach(r => {
            self.allRoles.delete(r.id);
            self.roles.set(r.id, r.name);
          });
          self.user = user;
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

  updateRole = (roleId, shouldRemove = false) => {
    let { allRoles, roles } = this;

    if (shouldRemove) {
      allRoles.set(roleId, roles.get(roleId));
      roles.delete(roleId);
    } else {
      roles.set(roleId, allRoles.get(roleId));
      allRoles.delete(roleId);
    }
  };

  save = body => {
    let { id } = this.props.match.params;
    let { groups, roles } = this;
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
    data.body.roles = roles.keys();

    return request(data).then(response => {
      if (response) {
        notifyDone();
      }
    });
  };

  render() {
    let {
      fields,
      user,
      roles,
      groups,
      allGroups,
      allRoles,
      updateRole,
      toggleGroup,
      selectAllGroups,
      save,
      pending,
    } = this;

    if (pending) {
      return <div class="spinner" />;
    }

    return (
      <UserForm
        fields={fields}
        user={user}
        groups={groups}
        roles={roles}
        allGroups={allGroups}
        allRoles={allRoles}
        updateRole={updateRole}
        toggleGroup={toggleGroup}
        selectAllGroups={selectAllGroups}
        onSubmit={save}
      />
    );
  }
}
