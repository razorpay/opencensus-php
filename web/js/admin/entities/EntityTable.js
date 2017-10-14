import React from 'react';

import { snakeToTitleCase } from 'util/index';

const EntityTable = ({ columnMeta = {}, records = [], limit = 10 }) => {
  if (records.length === 0) {
    return null;
  }

  const _columnMeta = [];

  Object.keys(records[0])
    .slice(0, limit)
    .forEach(columnKey => {
      const value = records[0][columnKey];

      const existingColumnMeta = columnMeta[columnKey] || {};

      existingColumnMeta.label =
        existingColumnMeta.label || snakeToTitleCase(columnKey);

      existingColumnMeta.key = columnKey;

      if (typeof existingColumnMeta.value !== 'function') {
        existingColumnMeta.value = value => value;
      }

      _columnMeta.push(existingColumnMeta);
    });

  return (
    <table className="table">
      <thead>
        <tr>
          {_columnMeta.map((columnMeta, index) => {
            return <th key={index}>{columnMeta.label}</th>;
          })}
        </tr>
      </thead>
      <tbody>
        {records.map((record, index) => {
          return (
            <tr key={index}>
              {_columnMeta.map((columnMeta, index) => {
                return (
                  <td key={index}>
                    {columnMeta.value(record[columnMeta.key])}
                  </td>
                );
              })}
            </tr>
          );
        })}
      </tbody>
    </table>
  );
};

export default EntityTable;
