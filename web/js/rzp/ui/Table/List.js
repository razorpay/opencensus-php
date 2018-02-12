export default ({ item, rows }) => {
  return (
    <div class="details-row-container">
      {rows.map((row, index) => (
        <div class={`details-row ${row.rowClass || ''}`} key={index}>
          <span class="details-cell">{row.title}</span>
          {row.value(item)}
        </div>
      ))}
    </div>
  );
};
