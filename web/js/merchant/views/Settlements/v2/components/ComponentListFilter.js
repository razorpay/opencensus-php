import React from 'react';
import { sanitizeTabName } from 'merchant/views/Settlements/v2/util';
import { titleCase } from 'common/utils/rzp-utils';
import Input from 'common/new-ui/Input';

const ComponentListFilter = (props, ref) => {
  return (
    <form ref={ref} onSubmit={props.submit}>
      <div class="settlement-components-filter">
        <div>
          <Input
            label={`${titleCase(sanitizeTabName(props.activeTab))} Id`}
            name="id"
            type="text"
          />
        </div>
        <div>
          <Input label="Count" name="count" type="number" defaultValue={props.count} />
        </div>
        <div>
          <button class="btn btn-primary" type="submit">
            Search
          </button>
        </div>
        <div>
          <span onClick={props.clear}>Clear</span>
        </div>
      </div>
    </form>
  );
};

export default React.forwardRef(ComponentListFilter);
