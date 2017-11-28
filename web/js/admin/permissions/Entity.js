import React, { Component } from 'react';
import { openModal, closeModal, notifyDone } from 'common/modal';
import Form from 'ui/Form';
import Field, { CheckField } from 'ui/Field';
import OrgTable from './OrgTable';
import BaseModal from 'ui/BaseModal';
import Table from 'ui/Table';
import { adminFetch, adminPut, adminPost, adminDelete } from 'util/fetch';
import { prevent } from 'util/index';

export default class EditPerm extends Component {
  state = {
    pending: true,
    roles: null,
    orgs: null,
    permission: null,
    orgTableData: null,
  };

  fetchFn(route_name) {
    return adminFetch({
      route_name,
      url_params: {
        id: this.props.model.id,
      },
    });
  }

  componentWillMount() {
    let requests = [adminFetch('org_get_multiple')];
    if (this.props.model) {
      requests.push(
        this.fetchFn('permission_get'),
        this.fetchFn('permission_get_roles')
      );
    }
    Promise.all(requests).then(([orgs, permission, roles]) => {
      if (permission) {
        permission.orgs = permission.orgs.map(o => o.id);
        permission.workflow_orgs = permission.workflow_orgs.map(o => o.id);
      } else {
        permission = {
          orgs: [],
          workflow_orgs: [],
        };
      }
      this.setState({
        permission,
        roles: (roles && roles.items) || [],
        orgs: orgs.items,
        pending: false,
        orgTableData: {
          orgs: permission.orgs,
          workflow_orgs: permission.workflow_orgs,
        },
      });
    });
  }

  onChange = orgTableData => this.setState({ orgTableData });

  onSubmit = body => {
    body = { ...body, ...this.state.orgTableData };
    body.assignable = body.assignable === '1';
    let data = { body };

    let promise;
    if (this.props.model) {
      data.url_params = {
        id: this.props.model.id,
      };
      data.route_name = 'permission_edit';
      data.content_type = 'application/json';
      promise = adminPut(data);
    } else {
      data.route_name = 'permission_create';
      promise = adminPost(data).then(data => {
        if (data) {
          this.props.collection.items.push(data);
          return data;
        }
      });
    }

    return promise.then(data => {
      if (data) {
        closeModal();
        notifyDone();
        return data;
      }
    });
  };

  render() {
    let { collection, model } = this.props;

    let { id, name, category, description } = model || {};

    let { roles, orgs, permission } = this.state;

    return (
      <BaseModal
        header={id ? `Edit Permission – ${name}` : 'Add a new Permission'}
      >
        <Form onSubmit={this.onSubmit}>
          {this.state.pending ? (
            <div class="spinner center" />
          ) : (
            <div>
              <Field
                required
                label="Permission Name"
                name="name"
                defaultValue={name}
              />
              <Field
                required
                label="Category"
                name="category"
                defaultValue={category}
              />
              <Field
                required
                label="Description"
                name="description"
                defaultValue={description}
              />
              <CheckField
                label="Assignable"
                name="assignable"
                defaultChecked={false}
              />
              <header>Organizations:</header>
              <OrgTable
                onChange={this.onChange}
                items={orgs}
                orgs={permission.orgs}
                workflowOrgs={permission.workflow_orgs}
              />
              {id && (
                <div>
                  <header>Assigned Roles (In this Org)</header>
                  <Table items={roles} fields={roleFields} />
                </div>
              )}
              <div class="sticky-save-btn">
                <button>Save</button>
              </div>
            </div>
          )}
        </Form>
      </BaseModal>
    );
  }
}

export function showEntity(collection) {
  openModal(<EditPerm collection={collection} model={this} />);
}
export function removeEntity(e) {
  let params = {
    route_name: 'permission_delete',
    url_params: {
      id: this.id,
    },
  };

  return adminDelete(params).then(response => {
    notifyDone();
    this.collection.items.remove(this);

    return response;
  });
}

const roleFields = [
  ['Name', item => item.name],
  ['Description', item => item.description],
];
