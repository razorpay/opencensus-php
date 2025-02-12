import PropTypes from 'prop-types';

/**
 * Component for the quick-add option in react-power-select
 * @prop {Object} select - Object containing items like searchTerm
 * @prop {String} label - Label to display after the '+' sign
 * @prop {Boolean} appendSearchTerm - Flag to toggle appending to the search term after the label
 * @prop {Function} onClick - Click-handler for the quick-add item
 * @prop {String} labelWhenSearchTermBlank - Label to display when there is no input in the search bar
 * @prop {String} labelWhenSearchTermValid - Label to display when there is some input in the search bar
 */
const QuickAdd = ({
  select,
  label,
  appendSearchTerm,
  onClick,
  labelWhenSearchTermBlank,
  labelWhenSearchTermValid,
  maxSearchTermLength,
}) => {
  // Initialize a label to show for the quick-add option
  let labelToShow = label;
  if (
    (!select.searchTerm || select.searchTerm.length === 0) &&
    labelWhenSearchTermBlank
  ) {
    // If `searchTerm` is empty, use `labelWhenSearchTermBlank`
    labelToShow = labelWhenSearchTermBlank;
  } else if (
    select.searchTerm &&
    select.searchTerm.length > 0 &&
    labelWhenSearchTermValid
  ) {
    // If `searchTerm` is not empty, use `labelWhenSearchTermValid` as the label,
    // but replace placeholders in the template first

    /**
     * Object which contains keys as placeholders in templates
     * and values as actual-values to fill the placeholder with.
     */
    let { searchTerm } = select;
    if (
      searchTerm &&
      maxSearchTermLength &&
      searchTerm.length > maxSearchTermLength
    ) {
      // If searchTerm exceeds max length, add three dots to show continuity.
      searchTerm = `${searchTerm.substring(0, maxSearchTermLength)}...`;
    }
    const templatePlaceholders = {
      ':_searchTerm_:': searchTerm,
    };

    labelToShow = labelWhenSearchTermValid; // Init label to the template-string

    // Replace each placeholder in the template (if one exists)
    for (let template in templatePlaceholders) {
      labelToShow = labelWhenSearchTermValid.replace(
        template,
        templatePlaceholders[template]
      );
    }
  }

  return (
    <div
      className="quick-create"
      onClick={() => {
        onClick(select);
        select.actions.close && select.actions.close();
      }}
    >
      <i className="i i-plus" />
      <span>
        {labelToShow}
        {appendSearchTerm && select.searchTerm && ` ${select.searchTerm}...`}
      </span>
    </div>
  );
};

QuickAdd.defaultProps = {
  label: 'Add New',
  appendSearchTerm: false,
  onClick: () => {},
};

QuickAdd.propTypes = {
  label: PropTypes.string,
  appendSearchTerm: PropTypes.bool,
  onClick: PropTypes.func,
};

export default QuickAdd;
