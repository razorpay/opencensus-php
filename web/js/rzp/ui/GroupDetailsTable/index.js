import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';
import { classList } from 'common/util';

/*
* GroupedDetailsTable is similar to DataTable but it supports 2 column layout, where each row can have upto 2 sub rows, shown in primary-secondary fashion
* Example: Invoices list in subscription details view and Payments list on Payment-Pages details view
* @props
*   - (Optional, String), className: Custom class name
*   - (String), title: Shown on primary title on left side
*   - (String), subtitle: Subtitle shown as secondary heading on right side
*   - (Boolean), loading: Tells whether data is fetched. Used to show progress loader.
*   - (Array), items: All the items to be displayed
*   - (Array of Arrays), rowConfig: Each of the row(sub-rows) to have resolution to their respective values.
*   - (Array of Arrays), loaderConfig: All the rows above, can have their respective progress-loader size, which is shown while data is loading
* */
export default function GroupDetailsTable({
  title,
  subTitle,
  className,
  loading,
  items,
  footer,
  rowConfig,
  loaderConfig,
}) {
  if (loading) {
    items = [1, 2]; // Dummy entries to show 2 loaders
  }

  return (
    <div class={classList('entity-detail-list', className)}>
      <div class="list-heading">
        <span class="label--primary">
          <b>{title}</b>
        </span>
        <span class="label--secondary">{subTitle}</span>
      </div>
      {items.map((rowData, idx) => (
        <GroupDetailRow
          key={idx}
          rowData={rowData}
          rowConfig={rowConfig}
          loaderConfig={loaderConfig}
          loading={loading}
        />
      ))}
      {!loading &&
        !items.length && (
          <h4 class="empty-table-message">{`No ${title} Found!`}</h4>
        )}

      {!loading && footer && <div class="entity-detail-footer">{footer}</div>}
    </div>
  );
}

/*
* GroupDetailRow is used by GroupedDetailsTable, to render each row
* @props
*   - (Boolean), loading: Propagated as it is
*   - (Object), rowData: Has data object for the row
*   - (Array), rowConfig: Propagated as it is
*   - (Array), loaderConfig: Propagated as it is
* */
function GroupDetailRow({ loading, rowData, rowConfig, loaderConfig }) {
  return (
    <div class="entity-detail-row">
      <div class="row-item content">
        {rowConfig.map((subrow, idx) => (
          <div class="detail-row" key={idx}>
            <div class="row-element left">
              {rowConfig[idx][0] &&
                (loading ? (
                  <PlaceholderLoader
                    style={loaderConfig[idx][0] || { width: '50%' }}
                  />
                ) : (
                  rowConfig[idx][0](rowData)
                ))}
            </div>
            <div class="row-element right">
              {rowConfig[idx][1] &&
                (loading ? (
                  <PlaceholderLoader
                    style={loaderConfig[idx][1] || { width: '30%' }}
                  />
                ) : (
                  rowConfig[idx][1](rowData)
                ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
