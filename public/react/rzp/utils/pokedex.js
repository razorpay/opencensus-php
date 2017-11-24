export const groupBy = (records, colName) => {
  const result = {};

  records.forEach((record, index) => {
    if (!record.hasOwnProperty(colName)) {
      return;
    }

    const colValue = record[colName],
      colRecords = (result[colName] = result[colName] || []);

    delete record[colName];

    colRecords.push(record);
  });

  return result;
};
