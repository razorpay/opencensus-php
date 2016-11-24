import { PropTypes } from 'react'

const QuickAdd = ({ select, label, appendSearchTerm, onClick }) => {
  return (
    <div
      class='quick-create'
      onClick={() => {
        onClick(...arguments)
        select.close()
      }}
    >
      <i class='fa fa-plus'></i>
      <span>
        { label }
        { appendSearchTerm && select.searchTerm && ` ${select.searchTerm}...` }
      </span>
    </div>
  )
}

QuickAdd.defaultProps = {
  label: 'Add New',
  appendSearchTerm: true,
  onClick: () => {}
}

QuickAdd.propTypes = {
  label: PropTypes.string,
  appendSearchTerm: PropTypes.bool,
  onClick: PropTypes.func
}

export default QuickAdd
