import TableBody from '../TableBody';

const CustomersListItem = props => {
  let { customer, canHighlightRow } = props;
  return (
    <tr class={canHighlightRow ? 'luminate' : ''}>
      <td>{customer.name}</td>
      <td>{customer.email}</td>
      <td>{customer.contact}</td>
      <td class="row-action">
        <div class="btn-group">
          <button class="btn btn-xs btn-default" onClick={props.onEdit}>
            <i class="fa fa-edit" />
            <span>edit</span>
          </button>
          {/*
          <button
            class='btn btn-xs btn-default'
            onClick={props.onDelete}
          >
            <i class='fa fa-trash text-danger'></i>
            <span>delete</span>
          </button>
*/}
        </div>
      </td>
    </tr>
  );
};

const CustomersList = props => {
  let { customers, isLoading, onEdit, onDelete, highlightRow } = props;

  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Customer Name</th>
            <th>Email</th>
            <th>Contact</th>
            <th>Actions</th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={4}
          rows={customers}
          emptyTableMsg="No Customers found!"
        >
          {customers.map(customer => (
            <CustomersListItem
              key={customer.id}
              customer={customer}
              canHighlightRow={highlightRow(customer)}
              onEdit={() => onEdit(customer)}
              onDelete={() => onDelete(customer)}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};

CustomersList.defaultProps = {
  highlightRow: () => {},
};

export default CustomersList;
