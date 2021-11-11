import { Field } from 'redux-form';
import ListFilter from 'merchant/components/ListFilter';

export default (props) => {
  return (
    <ListFilter {...props}>
      <div class="form-group list-filter-item">
        <label>Virtual Account Id</label>
        <Field
          name="id"
          component="input"
          class="form-control input-sm"
          onBlur={props.onEleBlur('va_id')}
        />
      </div>
      <>
        <div class="form-group list-filter-item account-number-filter">
          <label>Account Number/UPI Address</label>
          <Field
            name="payee_account"
            component="input"
            class="form-control input-sm"
            onBlur={props.onEleBlur('payee_vpa')}
          />
        </div>

        <div class="form-group list-filter-item">
          <label>Customer Name</label>
          <Field
            name="name"
            component="input"
            class="form-control input-sm"
            onBlur={props.onEleBlur('name')}
          />
        </div>

        <div class="form-group list-filter-item">
          <label>Customer Contact</label>
          <Field
            name="contact"
            component="input"
            class="form-control input-sm"
            onBlur={props.onEleBlur('contact')}
          />
        </div>

        <div class="form-group list-filter-item">
          <label>Email</label>
          <Field
            name="email"
            component="input"
            type="email"
            class="form-control input-sm"
            onBlur={props.onEleBlur('email')}
          />
        </div>

        <div class="form-group list-filter-item">
          <label>Account Description</label>
          <Field
            name="description"
            component="input"
            type="description"
            class="form-control input-sm"
            onBlur={props.onEleBlur('description')}
          />
        </div>
      </>

      <div class="form-group list-filter-item">
        <label>Notes</label>
        <Field
          name="notes"
          class="form-control input-sm"
          component="input"
          onBlur={props.onEleBlur('notes')}
        />
      </div>
      <div class="form-group list-filter-item count">
        <label>Count</label>
        <Field
          name="count"
          component="input"
          min={1}
          max={100}
          type="number"
          class="form-control input-sm"
          onBlur={props.onEleBlur('count')}
        />
      </div>
    </ListFilter>
  );
};
