import EntityItemRow from 'merchant/containers/EntityItemRow';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';

export default ({
  rows,
  columns,
  className,
  showHeaders = true,
  limit,
  loading,
  progressLoader = false,
  tableStyle = null,
  isMobileResolution,
  mobileColumns,
  customMobileRow,
}) => {
  const rowItems = [];
  const cols = isMobileResolution && mobileColumns ? mobileColumns : columns;
  if (progressLoader && loading) {
    limit = limit || 5;
    for (let cur = 0; cur < limit; cur++) {
      rowItems.push(
        <EntityItemRow key={cur}>
          {cols.map((column, index) => (
            <td class={column.columnClass ? column.columnClass : ''} key={index}>
              <PlaceholderLoader />
            </td>
          ))}
        </EntityItemRow>,
      );
    }
  } else if (rows.length) {
    let curRow = 0;
    rows.forEach((item, index) => {
      curRow++;
      if (curRow > limit) {
        return false;
      }
      /* 
        In case you have to create a custom view for mobile:
        then you can create a custom function `mobileRows` & pass as props
        from your parent component & take each item as a param. 
        Note: You can take reference from QR Code payment's tab
      */
      const row =
        isMobileResolution && customMobileRow ? (
          customMobileRow(item)
        ) : (
          <EntityItemRow key={`${item.id}_${index}`} id={item.id}>
            {cols.map((column, index) => (
              <td class={column.columnClass ? column.columnClass : ''} key={index}>
                {column.value(item)}
              </td>
            ))}
          </EntityItemRow>
        );
      return rowItems.push(row);
    });
  }

  return (
    <div class="table-responsive">
      <table class={`table table-hover ${className}`} style={tableStyle}>
        {showHeaders ? (
          <thead>
            <tr>
              {cols.map((column, index) => (
                <th class={column.columnClass} key={index}>
                  {column.title}
                </th>
              ))}
            </tr>
          </thead>
        ) : null}
        {rows && <tbody>{rowItems}</tbody>}
      </table>
    </div>
  );
};
