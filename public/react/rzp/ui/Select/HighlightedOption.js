const createHighlighedOption = (label, searchTerm) => {
  if (searchTerm) {
    let escapedSearchTerm = searchTerm.replace(
      /([.?*+^$[\]\\(){}|-])/g,
      '\\$1'
    );
    label = label.replace(new RegExp(escapedSearchTerm, 'i'), '<b>$&</b>');
  }

  return {
    __html: label,
  };
};

export default ({ option, select, optionLabelPath }) => {
  let highlightedLabel = option[optionLabelPath];
  return (
    <span
      dangerouslySetInnerHTML={createHighlighedOption(
        highlightedLabel,
        select.searchTerm
      )}
    />
  );
};
