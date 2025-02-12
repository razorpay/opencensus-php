// Todo: delete this file, it's available in @dashboard/shared-ui
import Alert from 'common/ui/Forms/Alert';
import Pager from 'common/ui/Pager';
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
    gridTemplateColumns,
  } = props;

  const classes = `${noStripe ? '' : 'table-striped'} ${columns ? customClass : ''}`;
  return (
    <div className={`data-table ${panelHeading ? 'has-panel' : ''}`}>
      {error && <Alert type="error" message={error} onCloseClick={onErrorCloseClick} />}
      {panelHeading && (
        <div className="list-heading">
          <span className="label--primary">{panelHeading.title}</span>
          <span className="label--secondary" style={{ float: 'right' }}>
            {panelHeading.subTitle}
          </span>
        </div>
      )}

      <Table
        rows={items}
        columns={columns}
        showHeaders={showHeaders}
        progressLoader={progressLoader}
        loading={loading}
        className={classes}
        mobileColumns={mobileColumns}
        customMobileRow={customMobileRow}
        isMobileResolution={isMobileResolution}
        onCellClick={onCellClick}
        onRowClick={onRowClick}
        isDisabled={isDisabled}
        gridTemplateColumns={gridTemplateColumns}
      />
      {!loading &&
        !items.length &&
        (EmptyComponent ? (
          <EmptyComponent />
        ) : empty_placeholder ? (
          empty_placeholder
        ) : (
          <h4 className="empty-table-message">{`No ${title} Found!`}</h4>
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
