import { Fragment } from 'react';
import { blocks } from './blocks';
import MethodBlock from './MethodBlock';

const MethodBlocks = () => {
  return (
    <>
      {blocks.map((block, index) => {
        return (
          <Fragment key={index}>
            <MethodBlock {...block} />
          </Fragment>
        );
      })}
    </>
  );
};

export default MethodBlocks;
