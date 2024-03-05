import React from 'react';
const List = ({ item, rows }) => {
  return (
    <div className="details-row-container">
      {rows.map((row, index) => (
        <div className={`details-row ${row.rowClass || ''}`} key={index}>
          <span className="details-cell">{row.title}</span>
          {row.value(item)}
        </div>
      ))}
    </div>
  );
};
export default List;
