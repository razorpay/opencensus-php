import ListFilter from '../ListFilter'
import { Field } from 'redux-form'

export default (props) => {
  return (
    <ListFilter {...props}>
      <div class='form-group list-filter-item'>
        <label>Invoice ID</label>
        <Field
          name='invoice_id'
          component='input'
          class='form-control input-sm'
        />
      </div>

      <div class='form-group list-filter-item'>
        <label>Customer Contact</label>
        <Field
          name='customer_contact'
          component='input'
          class='form-control input-sm'
        />
      </div>

      <div class='form-group list-filter-item'>
        <label>Customer Email</label>
        <Field
          name='customer_email'
          component='input'
          class='form-control input-sm'
        />
      </div>

      <div class='form-group list-filter-item count'>
        <label>Count</label>
        <Field
          name='count'
          component='input'
          min={1}
          max={100}
          type='number'
          class='form-control input-sm'
        />
      </div>
    </ListFilter>
  )
}
