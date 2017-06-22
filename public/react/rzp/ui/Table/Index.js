import EntityItemRow from 'merchant/containers/EntityItemRow';

export default ({ rows, columns, showHeaders = true }) => {
  return (
    <div class="table-responsive">
      <table class="table table-hover">
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
            {rows.map(item => (
              <EntityItemRow key={item.id} id={item.id}>
                {columns.map((column, index) => (
                  <td class={column.columnClass} key={index}>
                    {column.value(item)}
                  </td>
                ))}
              </EntityItemRow>
            ))}
          </tbody>}
      </table>
    </div>
  );
};
