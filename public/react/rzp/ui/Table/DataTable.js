import Spinner from 'rzp/ui/Spinner';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import Table from 'rzp/ui/Table/Index';
import { NavLink } from 'react-router-dom';

/*
  // Usage: Check slider/details view of payments, plans, etc.
  // Constraint:
     1. Pass props 'progressLoader' to <Table> only when progress loaders is shown instead of <Spinner>.
     2. Passing props 'customClass' is advised so as to have more control on `progress loader` length
     3. Passing props 'panelHeading = {title:, subTitle}' is kind of header but it's not table th (Check subscriptions dual view)
*/

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
    progressLoader,
    customClass,
    noStripe,
    panelHeading,
  } = props;

  const classes = `${noStripe ? '' : 'table-striped'} ${columns
    ? customClass
    : ''}`;

  return (
    <div class="data-table">
      {error && <Alert type="error" message={error} />}
      {panelHeading &&
        <div class="list-heading">
          <span class="label--primary">
            {panelHeading.title}
          </span>
          <span class="label--secondary" style={{ float: 'right' }}>
            {panelHeading.subTitle}
          </span>
        </div>}

      <Table
        rows={items}
        columns={columns}
        showHeaders={showHeaders}
        limit={limit}
        progressLoader={progressLoader}
        loading={loading}
        className={classes}
      />
      {!progressLoader &&
        loading &&
        <div style={{ padding: 77 }}>
          <Spinner />
        </div>}
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
    </div>
  );
}
