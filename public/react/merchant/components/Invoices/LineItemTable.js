import { SortableContainer, SortableElement, SortableHandle } from 'react-sortable-hoc'
import ReduxTypeAhead from 'rzp/ui/Forms/ReduxTypeAhead'
import { Field } from 'redux-form'

const LineItem = ({ item, index, plans }) => {
  debugger
  return (
    <tr>
      <td>
        <Field
          name='line_item[name]'
          component={ReduxTypeAhead}
          options={plans}
          selected={item}
          selectedLabel='name'
          optionComponent={(option) => <span>{option.name}</span>}
          searchIndices={['name']}
          placeholder='Select a plan'
        />
        <textarea
          class='form-control'
          value='avles'
        />
      </td>
      <td className='text-right'>
        <Field
          name={`line_item[quantity]`}
          component='input'
          className='form-control'
        />
      </td>
      <td className='text-right'>
        <Field
          name={`line_item[rate]`}
          component='input'
          className='form-control'
        />
      </td>
      <td className='text-right'>
        {item.quantity * item.rate}
      </td>
    </tr>
  )
}

export default ({ fields, plans }) => {
  return (
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
            fields.map((fieldName, idx, item) =>
              <LineItem
                key={fieldName}
                index={idx}
                item={item}
                plans={plans}
              />
            )
          }
        </tbody>
      </table>
    </div>
  )
}
