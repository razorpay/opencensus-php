import React, { Component } from 'react';
import Form from 'ui/Form';
import Field, { SelectField, SelectMethod } from 'ui/Field';
import { replaceSlider } from 'common/modal';
import Table from 'ui/Table';
import { adminPost } from 'util/fetch';

class EditGroup extends Component {
  save = data => {
    let params = {
      route_name: 'edit_group',
    };
    if (this.props.model) {
      params.url_params = {
        groupId: this.props.model.id,
      };
    }

    return adminPost({
      body: {
        name: data.name,
        description: data.description,
        parents: this.state.parents.map(p => p.id),
      },
      params,
    });
  };

  componentWillReceiveProps(props) {
    this.setState(props);
  }

  state = {
    parents: this.props.model ? this.props.model.parents : [],
    potentialParents: this.props.potentialParents || [],
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

  deleteParentField = [
    'Action',
    item => (
      <div class="link danger" onClick={e => this.deleteParent(item)}>
        Remove
      </div>
    ),
  ];

  render() {
    let { name, description, sub_groups } = this.props.model || {};

    let { parents, potentialParents } = this.state;

    return (
      <div>
        <header>Edit Group</header>
        <Form onSubmit={this.save}>
          <Field name="name" label="Name" required />
          <Field name="description" label="Description" required />
          <br />
          <SelectField label="Parents" onChange={this.selectParent} value="">
            <option value="" />
            {potentialParents.map(p => (
              <option value={p.id} key={p.id}>
                {p.name}
              </option>
            ))}
          </SelectField>
          <button>Save</button>
          <label>Parents</label>
          <Table
            fields={fields.concat([this.deleteParentField])}
            items={parents}
            bordered={true}
          />
          <label>Subgroups</label>
          <Table fields={fields} items={parents} bordered={true} />
        </Form>
      </div>
    );
  }
}

export function showEntity(collection) {
  replaceSlider(<EditGroup collection={collection} model={this} />);
}

const fields = [
  ['Group Id', item => item.id],
  ['Name', item => item.name],
  ['Description', item => item.description],
];
