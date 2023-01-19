export const customerId = {
  title: 'Customer Id',
  value: (item, onClick, userActionAllowed) =>
    userActionAllowed ? <a onClick={onClick}>{item.id}</a> : <span>{item.id}</span>,
};

export const customerName = {
  title: 'Customer Name',
  value: (item, onClick, userActionAllowed) =>
    userActionAllowed ? <a onClick={onClick}>{item.name}</a> : <span>{item.name}</span>,
};

export const action = {
  title: 'Action',
  value: (_, onClick) => (
    <div className="btn-group">
      <button type="button" className="btn btn-xs btn-default" onClick={onClick}>
        <i className="i i-edit" />
        <span>Edit</span>
      </button>
    </div>
  ),
};
