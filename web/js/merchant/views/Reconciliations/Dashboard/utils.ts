const createColumnSourceObj = (sourceCols) => {
  return sourceCols?.reduce((acc, curr) => {
    const { merchant_process_id, merchant_source_id, merchant_process_name, merchant_source_name } =
      curr;
    if (!acc[merchant_process_id]) {
      acc[merchant_process_id] = {
        id: merchant_process_id,
        name: merchant_process_name,
        sources: {},
      };
    }
    if (!acc[merchant_process_id].sources[merchant_source_id]) {
      acc[merchant_process_id].sources[merchant_source_id] = {
        id: merchant_source_id,
        name: merchant_source_name,
        columns: [],
      };
    }
    acc[merchant_process_id].sources[merchant_source_id].columns.push({ ...curr });
    return acc;
  }, {});
};

export { createColumnSourceObj };
