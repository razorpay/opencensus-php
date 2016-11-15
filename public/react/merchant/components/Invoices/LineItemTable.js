import { SortableContainer, SortableElement, SortableHandle } from 'react-sortable-hoc'
import ReduxTypeAhead from 'rzp/ui/Forms/ReduxTypeAhead'
import { Field } from 'redux-form'

const LineItem = ({ item, index, plans }) => {
  return (
    <tr>
      <td>
        <Field
          name={`line_item[${index}][name]`}
          component={ReduxTypeAhead}
          options={plans}
          selected={item}
          selectedLabel='name'
          optionComponent={(option) => <span>{option.name}</span>}
          searchIndices={['name']}
          placeholder='Select a plan'
        />
      </td>
      <td class='text-right'>
        <Field
          name={`line_item[${index}][quantity]`}
          component='input'
          class='form-control'
        />
      </td>
      <td class='text-right'>
        <Field
          name={`line_item[${index}][rate]`}
          component='input'
          class='form-control'
        />
      </td>
      <td class='text-right'>
        {item.quantity * item.rate}
      </td>
    </tr>
  )
}

export default ({ fields, plans }) => {
  return (
    <div class='table-responsive invoice-lineitem'>
      <table class='table'>
        <thead>
          <tr>
            <th>Item Details</th>
            <th style={{width: '12%'}} class='text-right'>Quantity</th>
            <th style={{width: '15%'}} class='text-right'>Rate</th>
            <th class='text-right'>Amount</th>
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
