import React, { Component } from 'react';
import { openModal, notifyDone } from 'common/modal';
import { observable, action, extendObservable, toJS } from 'mobx';
import { observer } from 'mobx-react';
import { adminFetch, adminPost, adminPut } from 'util/fetch';

import Form from 'ui/Form';
import Field, { SelectField, Switch } from 'ui/Field';
import Table from 'ui/Table';
import AsyncButton from 'ui/AsyncButton';

import user from 'admin/user';
import Levels from './Level';

@observer
export default class EditWorkflow extends Component {
  // all available roles
  allRoles = observable.shallowArray();
  allPerms = observable.shallowArray();

  // selected actions
  permissions = observable.shallowArray();
  @observable levels = [];

  componentWillMount() {
    extendObservable(this, { pending: true });
    let { id } = this.props.match.params;

    let requests = [
      adminFetch({ route_name: 'role_get_multiple' }),
      adminFetch({
        count: 1000,
        query_params: { type: 'workflow' },
        route_name: 'permission_get_multiple',
      }),
    ];

    if (id !== 'new') {
      requests.push(
        adminFetch({
          route_name: 'workflow_get',
          url_params: { id },
        })
      );
    }

    Promise.all(requests).then(
      action(([allRoles, allPerms, workflow]) => {
        this.pending = false;
        this.allRoles.replace(allRoles.items);
        this.allPerms.replace(allPerms.items);
        if (workflow) {
          this.permissions.replace(
            workflow.permissions.map(p =>
              this.allPerms.find(q => q.id === p.id)
            )
          );
          this.levels.replace(workflow.levels);
          this.workflow = workflow;
        }
      })
    );
  }

  selectPerm = e =>
    this.allPerms.some(
      p => p.id === e.target.value && this.permissions.push(p)
    );

  deletePerm = e => {
    this.allPerms.some(
      p =>
        p.id === e.target.getAttribute('data-id') && this.permissions.remove(p)
    );
  };

  actionFields = [
    ['Actions', p => p.name],
    [
      '',
      p => (
        <div class="link danger" data-id={p.id} onClick={this.deletePerm}>
          Remove
        </div>
      ),
    ],
  ];

  save = body => {
    let { id } = this.props.match.params;
    let data = { body };

    data.body.permissions = this.permissions.map(p => p.id);
    data.body.levels = toJS(this.levels);
    data.body.levels.forEach((l, index) => {
      l.steps = l.steps.map(s => ({
        role_id: s.role_id,
        reviewer_count: s.reviewer_count,
      }));
      l.level = index + 1;
    });

    let requestFn;

    if (id === 'new') {
      requestFn = adminPost;
      data.route_name = 'workflow_create';
      data.body.org_id = user.org_id;
    } else {
      requestFn = adminPut;
      data.route_name = 'workflow_update';
      data.url_params = {
        id,
      };
    }

    return requestFn(data).then(r => r && notifyDone());
  };

  render() {
    let {
      pending,
      workflow,
      allPerms,
      allRoles,
      roleMap,
      permissions,
      levels,
    } = this;

    if (pending) {
      return <div class="spinner" />;
    }

    return (
      <div class="columns box-container">
        <div class="column box">
          <header>
            {workflow ? `Edit - ${workflow.id}` : 'Create Workflow'}
          </header>
          <Form onSubmit={this.save}>
            <Field
              label="Workflow Name"
              name="name"
              defaultValue={workflow && workflow.name}
            />
            <SelectField label="Add Action" onChange={this.selectPerm} value="">
              <option value="" />
              {allPerms.map(
                p =>
                  permissions.indexOf(p) < 0 && (
                    <option value={p.id} key={p.id}>
                      {p.name}
                    </option>
                  )
              )}
            </SelectField>
            <button>Save</button>
          </Form>
          <Table
            animateRow={false}
            fields={this.actionFields}
            items={permissions}
          />
        </div>
        <Levels levels={levels} roles={allRoles} />
      </div>
    );
  }
}

export function showEntity() {
  return <EditWorkflow model={this} />;
}
