import { useState } from 'react';
import ListItem from './ListItem';
const IntermediateList = ({ instrument }) => {
  const [clickedName, setClickedName] = useState(null);
  const handleClickedInstument = name => {
    setClickedName(name);
  };
  return (
    <div class="level-2">
      <ul>
        {instrument.intermediateList.map((intermediateItem, index) => {
          return (
            <ListItem
              key={intermediateItem.name}
              index={index}
              instrument={intermediateItem}
              from="intermediate"
              handleClickedInstument={handleClickedInstument}
              clickedName={clickedName}
            />
          );
        })}
      </ul>
    </div>
  );
};
export default IntermediateList;
