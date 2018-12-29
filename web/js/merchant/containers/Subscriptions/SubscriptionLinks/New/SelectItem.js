import { TypeAhead } from 'react-power-select';

import { Label } from 'component/Input';

export default function SelectItem({ selectedItemId, ...props }) {
  return (
    <>
      <div className="Input Input--large Input--required">
        <Label text={props.itemNo} />
        <div className="Input-elWrapper">
          <TypeAhead
            options={props.items}
            disabled={props.itemsLoading}
            class="ps-in-modal"
            searchIndices={['id', 'name']}
            placeholder={
              props.itemsLoading ? 'Loading Items...' : 'Selecte a item'
            }
            optionComponent={ItemOption}
            onChange={props.onChangeInAddOnItem}
            selected={props.items.find(({ id }) => id === selectedItemId)}
          />
        </div>
      </div>
    </>
  );
}

function ItemOption({ option }) {
  return (
    <div>
      <strong>{option.id} </strong>
    </div>
  );
}
