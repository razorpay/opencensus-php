import EntityItemRow from 'merchant/containers/EntityItemRow';

export default ({ rows, columns, className, showHeaders = true, limit }) => {
  let rowItems = rows.map(item => (
    <EntityItemRow key={item.id} id={item.id}>
      {columns.map((column, index) => (
        <td class={column.columnClass} key={index}>
          {column.value(item)}
        </td>
      ))}
    </EntityItemRow>
  ));

  if (limit) {
    rowItems = rowItems.filter((items, indx) => indx < limit);
  }

  return (
    <div class="table-responsive">
      <table class={`table table-hover ${className}`}>
        {showHeaders
          ? <thead>
              <tr>
                {columns.map((column, index) => (
                  <th class={column.columnClass} key={index}>{column.title}</th>
                ))}
              </tr>
            </thead>
          : null}
        {rows &&
          <tbody>
            {rowItems}
          </tbody>}
      </table>
    </div>
  );
};
