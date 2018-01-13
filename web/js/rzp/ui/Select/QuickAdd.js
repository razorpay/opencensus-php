import PropTypes from 'prop-types';

const QuickAdd = ({ select, label, appendSearchTerm, onClick }) => {
  return (
    <div
      class="quick-create"
      onClick={() => {
        onClick(select);
        select.close && select.close();
      }}
    >
      <i class="i i-plus" />
      <span>
        {label}
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
