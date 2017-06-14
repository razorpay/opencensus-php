export default ({ item, rows }) => {
  return (
    <div class="list-group details-row-container">
      {rows.map(row => (
        <div class="list-group-item">
          <span>{row[0]}</span>
          <span>{row[1](item)}</span>
        </div>
      ))}
    </div>
  );
};
