import React from 'react';
import PropTypes from 'prop-types';
import styled from 'styled-components';
import Link from '../Link';

const ButtonWithoutStyles = styled.button`
  background: none;
  border: none;
  padding: 1px 2px;
`;

const LinkButton = (props) => {
  return (
    <Link as={ButtonWithoutStyles} size="xsmall" {...props}>
      {props.children}
    </Link>
  );
};

LinkButton.propTypes = {
  children: PropTypes.string,
};

export default LinkButton;
