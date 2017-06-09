import EntityItemRow from 'merchant/containers/EntityItemRow';

export default ({ rows, columns }) => {
  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            {columns.map((column, index) => <th key={index}>{column[0]}</th>)}
          </tr>
        </thead>
        {rows &&
          <tbody>
            {rows.map(item => (
              <EntityItemRow key={item.id}>
                {columns.map((column, index) => (
                  <td key={index}>{column[1](item)}</td>
                ))}
              </EntityItemRow>
            ))}
          </tbody>}
      </table>
    </div>
  );
};
