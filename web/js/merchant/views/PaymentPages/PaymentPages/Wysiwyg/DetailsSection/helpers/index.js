export function parseGoalTrackerAmountValues(meta_data) {
  const newMetaData = { ...meta_data };
  if (newMetaData.hasOwnProperty('goal_amount')) {
    newMetaData.goal_amount = String(Number(newMetaData.goal_amount) / 100);
  }
  if (newMetaData.hasOwnProperty('collected_amount')) {
    newMetaData.collected_amount = String(Number(newMetaData.collected_amount) / 100);
  }
  return newMetaData;
}
