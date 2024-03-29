import React from 'react';
import PropTypes from 'prop-types';
import styled from 'styled-components';

const Container = styled.div`
  background-color: #fff;
  border-radius: 0px 0px 3px 3px;
  padding: 24px 28px;
`;

const Heading = styled.h2`
  font-weight: 800;
  font-size: 16px;
  line-height: 22px;
  text-align: center;
  color: #324664;
  margin: 0;
`;

export default function BottomSection({ heading, children }) {
  return (
    <Container>
      <Heading>{heading}</Heading>
      {children}
    </Container>
  );
}

BottomSection.propTypes = {
  heading: PropTypes.oneOfType([PropTypes.string, PropTypes.node]).isRequired,
  children: PropTypes.node.isRequired,
};
