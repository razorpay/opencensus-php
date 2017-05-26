import { PropTypes } from 'react';

const QuickAdd = ({ select, label, appendSearchTerm, onClick }) => {
  return (
    <div
      class="quick-create"
      onClick={() => {
        onClick(select);
        select.close();
      }}
    >
      <i class="icon icon-plus" />
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
