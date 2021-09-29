/**
 * merge all override config data specific
 * @param {Object} selectedData data config based on status
 * @param {Object} allComponents all available components consume by RTB page
 * @return {Object} updated Config merge of selected status component with all component it can includes override of component via selectedData
 */
export function mergeComponent(selectedData, allComponents) {
  const selectedDataComponents = selectedData.sections;
  if (!selectedData.sections) {
    return allComponents;
  }
  const updatedComponents = { ...allComponents };
  Object.keys(selectedDataComponents).forEach((compKey) => {
    if (allComponents[compKey]) {
      updatedComponents[compKey] = {
        ...updatedComponents[compKey],
        ...selectedDataComponents[compKey],
      };
    } else {
      // add component    x
      updatedComponents[compKey] = selectedDataComponents[compKey];
    }
  });
  return updatedComponents;
}

/***
 * sort all components based on order in which we wanna display in UI
 * @param {Object} allComponents all available components consumed by RTB
 * @param {Array} order array of ids of component in which order we want to render
 * @return {Array} array of component config in order
 */
export function sortBy(allComponents, order = []) {
  if (!order || !order.length) {
    return [];
  }
  return order.map((key) => allComponents[key]).filter(Boolean);
}
