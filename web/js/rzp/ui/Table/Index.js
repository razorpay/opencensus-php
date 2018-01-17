import EntityItemRow from 'merchant/containers/EntityItemRow';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';

export default ({
  rows,
  columns,
  className,
  showHeaders = true,
  limit,
  loading,
  progressLoader = false,
}) => {
  let rowItems = [];

  if (progressLoader && loading) {
    limit = limit || 5;
    for (let cur = 0; cur < limit; cur++) {
      rowItems.push(
        <EntityItemRow key={cur}>
          {columns.map((column, index) => (
            <td
              class={column.columnClass ? column.columnClass : ''}
              key={index}
            >
              <PlaceholderLoader />
            </td>
          ))}
        </EntityItemRow>
      );
    }
  } else if (rows.length) {
    let curRow = 0;

    rows.forEach(item => {
      curRow++;
      if (curRow > limit) {
        return false;
      }
      rowItems.push(
        <EntityItemRow key={item.id} id={item.id}>
          {columns.map((column, index) => (
            <td
              class={column.columnClass ? column.columnClass : ''}
              key={index}
            >
              {column.value(item)}
            </td>
          ))}
        </EntityItemRow>
      );
    });
  }

  return (
    <div class="table-responsive">
      <table class={`table table-hover ${className}`}>
        {showHeaders ? (
          <thead>
            <tr>
              {columns.map((column, index) => (
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
