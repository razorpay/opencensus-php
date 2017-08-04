import Spinner from 'rzp/ui/Spinner';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import Table from 'rzp/ui/Table/Index';
import { NavLink } from 'react-router-dom';

export default function DataTable(props) {
  let {
    error,
    loading,
    items,
    columns,
    showHeaders,
    count,
    skip,
    paginate,
    title,
    limit,
    limitUrl,
  } = props;

  let showMoreBtn;

  if (items.length && limit && limitUrl) {
    showMoreBtn = (
      <NavLink to={limitUrl} target="_blank">
        <b>Show More</b>
      </NavLink>
    );
  }

  return (
    <div>
      {error && <Alert type="error" message={error} />}

      <Table
        rows={items}
        columns={columns}
        showHeaders={showHeaders}
        limit={limit}
        class="table-striped"
      />
      {loading && <div style={{ padding: 77 }}><Spinner /></div>}
      {!loading &&
        !items.length &&
        <h4 class="empty-table-message">{`No ${title} Found!`}</h4>}

      {paginate &&
        <Pager
          count={count}
          skip={skip}
          length={items.length}
          onClick={paginate}
        />}

      {showMoreBtn}
    </div>
  );
}
