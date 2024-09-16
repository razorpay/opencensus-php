// Todo: delete this file, it's available in @dashboard/shared-ui
import Alert from 'common/ui/Forms/Alert';
import Pager from 'common/ui/Pager';
import Spinner from 'common/ui/Spinner';
import Table from 'common/ui/Table/Index';

/*
  // Usage: Check slider/details view of payments, plans, etc.
  // Constraint:
    1. Pass props 'progressLoader' to <Table> only when progress loaders is shown instead of <Spinner>.
    2. Passing props 'customClass' is advised so as to have more control on `progress loader` length
    3. Passing props 'panelHeading = {title:, subTitle}' is kind of header but it's not table th (Check subscriptions dual view)
*/

export default function DataTable(props) {
  const {
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
    empty_placeholder,
    progressLoader,
    customClass,
    noStripe,
    panelHeading,
    EmptyComponent, //-render empty component when the items are 0. see batch list
    onErrorCloseClick,
    isMobileResolution,
    customMobileRow,
    mobileColumns,
    onCellClick,
    onRowClick,
    isDisabled,
    hasMoreData = true,
  } = props;

  const classes = `${noStripe ? '' : 'table-striped'} ${columns ? customClass : ''}`;
  return (
    <div class={`data-table ${panelHeading ? 'has-panel' : ''}`}>
      {error && <Alert type="error" message={error} onCloseClick={onErrorCloseClick} />}
      {panelHeading && (
        <div class="list-heading">
          <span class="label--primary">{panelHeading.title}</span>
          <span class="label--secondary" style={{ float: 'right' }}>
            {panelHeading.subTitle}
          </span>
        </div>
      )}

      <Table
        rows={items}
        columns={columns}
        showHeaders={showHeaders}
        limit={limit}
        progressLoader={progressLoader}
        loading={loading}
        className={classes}
        mobileColumns={mobileColumns}
        customMobileRow={customMobileRow}
        isMobileResolution={isMobileResolution}
        onCellClick={onCellClick}
        onRowClick={onRowClick}
        isDisabled={isDisabled}
      />
      {!progressLoader && loading && (
        <div style={{ padding: 77 }} class="text-center">
          <Spinner />
        </div>
      )}
      {!loading &&
        !items.length &&
        (EmptyComponent ? (
          <EmptyComponent />
        ) : empty_placeholder ? (
          empty_placeholder
        ) : (
          <h4 class="empty-table-message">{`No ${title} Found!`}</h4>
        ))}

      {paginate && (
        <Pager
          count={count}
          skip={skip}
          length={items.length}
          onClick={paginate}
          hasMoreData={hasMoreData}
        />
      )}
    </div>
  );
}
