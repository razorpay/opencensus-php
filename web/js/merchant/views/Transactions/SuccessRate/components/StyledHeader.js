import React from 'react';

const StyledHeader = ({ text, dataTestId }) => {
  return (
    <p className="styled-header" data-testid={dataTestId ?? ''}>
      {text}
    </p>
  );
};

export default StyledHeader;
