import React from 'react';

import { snakeToTitleCase } from 'util/index';
import Table from 'ui/Table';

const EntityList = ({ columnMeta = {}, records = [], limit = 10 }) => {
  if (records.length === 0) {
    return null;
  }

  // Processed column meta
  const _columnMeta = [];

  Object.keys(records[0])
    .slice(0, limit)
    .forEach(columnKey => {
      const value = records[0][columnKey];

      const existingColumnMeta = columnMeta[columnKey] || [];

      existingColumnMeta[0] =
        existingColumnMeta[0] || snakeToTitleCase(columnKey);

      if (typeof existingColumnMeta[1] !== 'function') {
        existingColumnMeta[1] = value => value[columnKey];
      }

      _columnMeta.push(existingColumnMeta);
    });

  return <Table fields={_columnMeta} items={records} />;
};

export default EntityList;
