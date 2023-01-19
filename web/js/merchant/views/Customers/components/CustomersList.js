import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import { email, contact } from 'common/ui/item/pair';
import { action, customerId, customerName } from 'merchant/views/Customers/item';

const TableHead = ({ columns }) => {
  return (
    <thead>
      <tr>
        {columns.map(({ title }, idx) => (
          <th key={idx}>{title}</th>
        ))}
      </tr>
    </thead>
  );
};

const CustomersListItem = ({ customer, onEdit, columns, userActionAllowed }) => {
  return (
    <EntityItemRow id={customer.id}>
      {columns.map(({ title, value }) => (
        <td key={title}>{value(customer, onEdit, userActionAllowed)}</td>
      ))}
    </EntityItemRow>
  );
};

const CustomersList = (props) => {
  const { userActionAllowed, customers, isLoading, onEdit, onDelete } = props;

  const columns = [customerId, customerName, email, contact].concat(
    userActionAllowed ? [action] : [],
  );

  return (
    <div className="table-responsive">
      <table className="table table-hover">
        <TableHead columns={columns} />
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
              userActionAllowed={userActionAllowed}
              onEdit={() => onEdit(customer)}
              onDelete={() => onDelete(customer)}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};

export default CustomersList;
