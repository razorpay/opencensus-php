import { SortableContainer, SortableElement, SortableHandle } from 'react-sortable-hoc'
import ReduxTypeAhead from 'rzp/ui/Forms/ReduxTypeAhead'
import { Field } from 'redux-form'


const LineItem = (props) => {
  debugger
  let { field, item, index, items } = props
  return (
    <tr>
      <td>
        <Field
          name={`${field}.name`}
          component={ReduxTypeAhead}
          options={items}
          selected={item}
          selectedLabel='name'
          optionComponent={(option) => <span>{option.name}</span>}
          searchIndices={['name']}
          placeholder='Select a plan'
        />
      </td>
      <td>
        <Field
          name={`${field}.quantity`}
          component='input'
          class='form-control text-right'
          type='number'
        />
      </td>
      <td>
        <Field
          name={`${field}.rate`}
          component='input'
          class='form-control text-right'
        />
      </td>
      <td class='text-right'>
        {item.quantity * item.rate}

        <span class='remove-row-action' onClick={() => props.onRemove(index)}>
          <i class='fa fa-times-circle text-danger'></i>
        </span>
      </td>
    </tr>
  )
}

export default ({ fields, items }) => {
  debugger
  return (
    <div class='invoice-lineitem'>
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
                key={`line_item_${idx}`}
                index={idx}
                field={fieldName}
                item={item}
                items={items}
                onRemove={(index) => {
                  fields.remove(index)
                  if (fields.length === 1) {
                    fields.push({
                      name: '',
                      quantity: 1,
                      rate: 0.00
                    })
                  }
                }}
              />
            )
          }
        </tbody>
      </table>

      <button
        class='btn btn-default'
        style={{
          marginLeft: '20px'
        }}
        type='button'
        onClick={() => fields.push({
          quantity: 1,
          rate: 0.00
        })}
      >
        Add Another Item
      </button>
    </div>
  )
}
