import { useState } from 'react';
import { connect } from 'react-redux';
import ListItem from './ListItem';

const RootList = ({ instruments }) => {
  const [clickedName, setClickedName] = useState(null);
  const handleClickedInstument = name => {
    setClickedName(name);
  };
  return (
    <div class="level-1">
      <ul>
        {instruments.map((instrument, index) => {
          return (
            <ListItem
              key={instrument.name}
              index={index}
              instrument={instrument}
              from="root"
              handleClickedInstument={handleClickedInstument}
              clickedName={clickedName}
            />
          );
        })}
      </ul>
    </div>
  );
};

const mapStateToProps = state => ({
  instruments: state.instrumentRequests.pg,
});

export default connect(mapStateToProps, {})(RootList);
