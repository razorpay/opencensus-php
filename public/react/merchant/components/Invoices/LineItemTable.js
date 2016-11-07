import { SortableContainer, SortableElement, SortableHandle } from 'react-sortable-hoc'
import { Field } from 'redux-form'

const DragHandle = SortableHandle(() => <span>::</span>)

const SortableLineItem = SortableElement(({ item, index }) => (
  <tr>
    <td>
      <DragHandle />
      {item.name}
      <Field
        name={`line_item[${index}]`}
        component='textarea'
        className='form-control'
      />
    </td>
    <td className='text-right'>{item.quantity}</td>
    <td className='text-right'>{item.rate}</td>
    <td className='text-right'>{item.quantity * item.rate}</td>
  </tr>
))

export default SortableContainer(({ items }) => (
  <div className='table-responsive'>
    <table className='table'>
      <thead>
        <tr>
          <th>Item Details</th>
          <th className='text-right'>Quantity</th>
          <th className='text-right'>Rate</th>
          <th className='text-right'>Amount</th>
        </tr>
      </thead>
      <tbody>
        {
          items.map((item, idx) =>
            <SortableLineItem
              key={item.id}
              index={idx}
              item={item}
              displayName='tr'
            />
          )
        }
      </tbody>
    </table>
  </div>
))
