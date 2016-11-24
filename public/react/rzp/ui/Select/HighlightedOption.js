const createHighlighedOption = (label, searchTerm) => {
  if (searchTerm) {
    label = label.replace(new RegExp(searchTerm, 'i'), '<b>$&</b>')
  }

  return {
    __html: label
  }
}

export default ({ option, select, optionLabelPath }) => {
  let highlightedLabel = option[optionLabelPath]
  return (
    <span dangerouslySetInnerHTML={createHighlighedOption(highlightedLabel, select.searchTerm)} />
  )
}
