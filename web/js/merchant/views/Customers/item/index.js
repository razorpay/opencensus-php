export const customerId = {
  title: 'Customer Id',
  value: (item, onClick) => <a onClick={onClick}>{item.id}</a>,
};

export const customerName = {
  title: 'Customer Name',
  value: (item, onClick) => <a onClick={onClick}>{item.name}</a>,
};

export const action = {
  title: 'Action',
  value: (item, onClick) => (
    <div className="btn-group">
      <button type="button" className="btn btn-xs btn-default" onClick={onClick}>
        <i className="i i-edit" />
        <span>edit</span>
      </button>
    </div>
  ),
};
