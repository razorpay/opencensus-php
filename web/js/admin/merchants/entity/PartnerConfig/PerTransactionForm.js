import { SelectField, SearchableSelectField } from 'ui/Field';

export default function PerTransactionForm({ plans, values, internals }) {
  const { comission, pricing } = plans;
  return (
    <>
      <SelectField data-name="_comission_mode" label="Comission Type">
        <option value="variable">Variable</option>
        <option value="fixed">Fixed</option>
      </SelectField>

      <Searchable
        name="default_plan_id"
        trackBy="plan_id"
        label="Default sub-merchant pricing"
        options={pricing.data}
        defaultValue={values.default_plan_id}
        pending={pricing.pending}
      />

      <Searchable
        name="implicit_plan_id"
        trackBy="plan_id"
        label="Partner Pricing"
        options={internals === 'fixed' ? comission.data : pricing.data}
        defaultValue={values.implicit_plan_id}
        pending={pricing.pending || comission.pending}
      />

      <Searchable
        name="explicit_plan_id"
        trackBy="plan_id"
        label="Add-on Comission"
        options={comission.data}
        helpMsg="This will be exposed to sub-merchants"
        defaultValue={values.explicit_plan_id}
        pending={comission.pending}
      />
    </>
  );
}

function Searchable({ pending, ...props }) {
  return pending ? (
    <Field label={props.label} value="Loading..." disabled />
  ) : (
    <SearchableSelectField {...props} />
  );
}
