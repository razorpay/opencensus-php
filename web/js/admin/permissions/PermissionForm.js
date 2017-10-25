import React, { Component } from 'react';
import { observer } from 'mobx-react';
import Field, { SelectField, CheckField } from 'ui/Field';
import Table from 'ui/Table';
import Form from 'ui/Form';

@observer
export default class PermForm extends Component {
  render() {
    let {
      id,
      name,
      description,
      category,
      perms,
      roles,
      orgs,
      selected_orgs,
      workflow_orgs,
      onSelectAll,
      onSelect,
    } = this.props;

    return (
      <div>
        <header>
          {id ? `Edit Permission &ndash; ${id}` : 'Add a new Permission'}
        </header>
        <Form>
          <Field label="Permission Name" name="name" defaultValue={name} />
          <Field label={'Category'} name="category" defaultValue={category} />
          <br />
          <Field
            label={'Description'}
            name="description"
            defaultValue={description}
          />
          <CheckField
            label="Assignable"
            name="Assignable"
            defaultChecked={false}
            name=""
          />
          <header>Organizations:</header>
          <OrgTable
            items={orgs}
            selectAllOrg={onSelectAll}
            selectOrg={onSelect}
            selectedOrgs={selected_orgs}
            workflowOrgs={workflow_orgs}
          />
          <header>Assigned Roles(In this Org)</header>
          <Table items={roles} fields={roleFields} />
          <button>Save</button>
        </Form>
      </div>
    );
  }
}

// export default observer(function PermForm({
//   id,
//   name,
//   description,
//   category,
//   perms,
//   roles,
//   orgs,
//   selected_orgs,
//   workflow_orgs,
//   onSelectAll,
//   onSelect
// }) {
//   return (
//     <div>
//       <header>
//         {id ? `Edit Permission &ndash; ${id}` : "Add a new Permission"}
//       </header>
//       <Form>
//         <Field label="Permission Name" name="name" defaultValue={name} />
//         <Field label={"Category"} name="category" defaultValue={category} />
//         <br />
//         <Field
//           label={"Description"}
//           name="description"
//           defaultValue={description}
//         />
//         <CheckField
//           label="Assignable"
//           name="Assignable"
//           defaultChecked={false}
//           name=""
//         />
//         <header>Organizations:</header>
//         <OrgTable
//           items={orgs}
//           selectAllOrg={onSelectAll}
//           selectOrg={onSelect}
//           selectedOrgs={selected_orgs}
//           workflowOrgs={workflow_orgs}
//         />
//         <header>Assigned Roles(In this Org)</header>
//         <Table items={roles} fields={roleFields} />
//         <button>Save</button>
//       </Form>
//     </div>
//   );
// });

@observer
class OrgTable extends Component {
  componentWillReceiveProps(nextProps) {
    console.log(nextProps);
  }

  render() {
    let {
      items,
      selectAllOrg,
      selectOrg,
      selectedOrgs,
      workflowOrgs,
    } = this.props;

    return (
      <div class="table table-striped">
        <div class="tr thead">
          <div class="th">
            <input type="checkbox" onClick={selectAllOrg} />
          </div>
          <div class="th">Business Name</div>
          <div class="th">Display Name</div>
          <div class="th">Workflow Enable</div>
        </div>
        {items &&
          items.map((item, idx) => (
            <div class="tr" key={idx}>
              <div class="td">
                <input
                  type="checkbox"
                  value="on"
                  onChange={e => selectOrg(e, item.id)}
                  checked={!!selectedOrgs[item.id]}
                />
              </div>
              <div class="td">{item.business_name}</div>
              <div class="td">{item.display_name}</div>
              <div class="td">
                <input
                  type="checkbox"
                  disabled=""
                  value="on"
                  checked={!!workflowOrgs[item.id]}
                  disabled={!selectedOrgs[item.id]}
                />
              </div>
            </div>
          ))}
      </div>
    );
  }
}

// const OrgTable = observer(() => {
//   return (
//     <div class="table table-striped">
//       <div class="tr thead">
//         <div class="th">
//           <input type="checkbox" onClick={selectAllOrg} />
//         </div>
//         <div class="th">Business Name</div>
//         <div class="th">Display Name</div>
//         <div class="th">Workflow Enable</div>
//       </div>
//       {items &&
//         items.map((item, idx) => (
//           <div class="tr" key={idx}>
//             <div class="td">
//               <input
//                 type="checkbox"
//                 value="on"
//                 onChange={e => selectOrg(e, item.id)}
//                 checked={selectedOrgs[item.id]}
//               />
//             </div>
//             <div class="td">{item.business_name}</div>
//             <div class="td">{item.display_name}</div>
//             <div class="td">
//               <input
//                 type="checkbox"
//                 disabled=""
//                 value="on"
//                 checked={workflowOrgs[item.id]}
//                 disabled={!selectedOrgs[item.id]}
//               />
//             </div>
//           </div>
//         ))}
//     </div>
//   );
// });

// const orgFields = [
//   ["", item => <input type="checkbox" />],
//   ["Business Name", item => item.business_name],
//   ["Display Name", item => item.display_name],
//   ["Workflow Enable", item => <input type="checkbox" disabled />]
// ];

const roleFields = [
  ['Name', item => item.name],
  ['Description', item => item.description],
];
