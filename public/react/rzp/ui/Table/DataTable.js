import Spinner from 'rzp/ui/Spinner';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import rowClass from 'merchant/utils/activeRow';
import Table from 'rzp/ui/Table/Index';

export default function DataTable(props) {
  let { error, loading, items, columns, count, skip, paginate, title } = props;

  return (
    <div>
      {error && <Alert type="error" message={error} />}

      <Table rows={items} columns={columns} rowClass={rowClass} />
      {loading && <div style={{ padding: 77 }}><Spinner /></div>}
      {!loading &&
        !items.length &&
        <h4 class="empty-table-message">{`No ${title} Found!`}</h4>}

      <Pager
        count={count}
        skip={skip}
        length={items.length}
        onClick={paginate}
      />

    </div>
  );
}
