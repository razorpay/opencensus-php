import TableBody from 'rzp/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';

const CustomersListItem = ({ customer, onEdit }) => {
  return (
    <EntityItemRow id={customer.id}>
      <td>
        <a onClick={onEdit}>{customer.id}</a>
      </td>
      <td>
        <a onClick={onEdit}>{customer.name}</a>
      </td>
      <td>{customer.email}</td>
      <td>{customer.contact}</td>
      <td class="row-action">
        <div class="btn-group">
          <button class="btn btn-xs btn-default" onClick={onEdit}>
            <i class="i i-edit" />
            <span>edit</span>
          </button>
        </div>
      </td>
    </EntityItemRow>
  );
};

export default ({ customers, isLoading, onEdit, onDelete }) => {
  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Customer Id</th>
            <th>Customer Name</th>
            <th>Email</th>
            <th>Contact</th>
            <th>Actions</th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={5}
          rows={customers}
          emptyTableMsg="No Customers found!"
        >
          {customers.map(customer => (
            <CustomersListItem
              key={customer.id}
              customer={customer}
              onEdit={() => onEdit(customer)}
              onDelete={() => onDelete(customer)}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
