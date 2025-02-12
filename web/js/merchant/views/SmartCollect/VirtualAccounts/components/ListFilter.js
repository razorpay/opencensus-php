import { Field } from 'redux-form';
import ListFilter from 'merchant/components/ListFilter';

export default (props) => {
  return (
    <ListFilter {...props}>
      <div className="form-group list-filter-item">
        <label>Customer Identifier Id</label>
        <Field
          name="id"
          component="input"
          className="form-control input-sm"
          onBlur={props.onEleBlur('va_id')}
        />
      </div>
      <>
        <div className="form-group list-filter-item account-number-filter">
          <label>Customer Identifier Number/UPI Address</label>
          <Field
            name="payee_account"
            component="input"
            className="form-control input-sm"
            onBlur={props.onEleBlur('payee_vpa')}
          />
        </div>

        <div className="form-group list-filter-item">
          <label>Customer Name</label>
          <Field
            name="name"
            component="input"
            className="form-control input-sm"
            onBlur={props.onEleBlur('name')}
          />
        </div>

        <div className="form-group list-filter-item">
          <label>Customer Contact</label>
          <Field
            name="contact"
            component="input"
            className="form-control input-sm"
            onBlur={props.onEleBlur('contact')}
          />
        </div>

        <div className="form-group list-filter-item">
          <label>Customer Email</label>
          <Field
            name="email"
            component="input"
            type="email"
            className="form-control input-sm"
            onBlur={props.onEleBlur('email')}
          />
        </div>

        <div className="form-group list-filter-item description-filter">
          <label>Customer Identifier Description</label>
          <Field
            name="description"
            component="input"
            type="description"
            className="form-control input-sm"
            onBlur={props.onEleBlur('description')}
          />
        </div>
      </>

      <div className="form-group list-filter-item">
        <label>Notes</label>
        <Field
          name="notes"
          className="form-control input-sm"
          component="input"
          onBlur={props.onEleBlur('notes')}
        />
      </div>
      <div className="form-group list-filter-item count">
        <label>Count</label>
        <Field
          name="count"
          component="input"
          min={1}
          max={100}
          type="number"
          className="form-control input-sm"
          onBlur={props.onEleBlur('count')}
        />
      </div>
    </ListFilter>
  );
};
