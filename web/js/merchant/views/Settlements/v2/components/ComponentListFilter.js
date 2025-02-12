import React from 'react';
import { sanitizeTabName } from 'merchant/views/Settlements/v2/util';
import { titleCase } from 'common/utils/rzp-utils';
import Input from 'common/new-ui/Input';

const ComponentListFilter = (props, ref) => {
  const activeTabLabel = `${titleCase(sanitizeTabName(props.activeTab))} Id`;
  return (
    <form ref={ref} onSubmit={props.submit}>
      <div className="settlement-components-filter">
        <div>
          <Input label={activeTabLabel} name="id" type="text" aria-label={activeTabLabel} />
        </div>
        <div>
          <Input
            label="Count"
            name="count"
            type="number"
            defaultValue={props.count}
            aria-label="Count"
          />
        </div>
        <div>
          <button className="btn btn-primary" type="submit">
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
