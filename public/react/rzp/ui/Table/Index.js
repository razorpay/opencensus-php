export default ({ rows, columns, rowClass }) => {
  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            {columns.map((column, index) => <th key={index}>{column[0]}</th>)}
          </tr>
        </thead>
        <tbody>
          {rows.map(item => (
            <tr key={item.id} className={rowClass && rowClass(item)}>
              {columns.map((column, index) => (
                <td key={index}>{column[1](item)}</td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};
