import { email, contact } from 'common/ui/item/pair';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import { action, customerId, customerName } from 'merchant/views/Customers/item';

const CustomersListItem = ({ customer, onEdit, columns }) => {
  return (
    <EntityItemRow id={customer.id}>
      {columns.map(({ title, value }) => (
        <td key={title}>{value(customer, onEdit)}</td>
      ))}
    </EntityItemRow>
  );
};

export default ({ customers, isLoading, onEdit, onDelete }) => {
  const columns = [customerId, customerName, email, contact, action];

  return (
    <div className="table-responsive">
      <table className="table table-hover">
        <thead>
          <tr>
            {columns.map(({ title }) => (
              <th key={title}>{title}</th>
            ))}
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={5}
          rows={customers}
          emptyTableMsg="No Customers found!"
        >
          {customers.map((customer) => (
            <CustomersListItem
              key={customer.id}
              customer={customer}
              columns={columns}
              onEdit={() => onEdit(customer)}
              onDelete={() => onDelete(customer)}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
