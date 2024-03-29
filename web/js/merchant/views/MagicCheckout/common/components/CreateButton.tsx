import React from 'react';
import styled from 'styled-components';

const Button = styled.button<{ width?: number; height?: number }>(
  ({ theme, width, height }) => `
  width: ${width || 315}px;
  height: ${height || 75}px;
  background: ${theme.colors.surface.background.primary.subtle};
  border: 1px dashed ${theme.colors.surface.background.primary.intense};
  border-radius: 3px;
  color: ${theme.colors.surface.background.primary.intense};
  font-weight: 600;
`,
);

const CreateButton = ({
  entity,
  onClick,
}: {
  entity: 'zones' | 'categories' | 'slabs' | 'products ' | 'category';
  onClick: () => void;
}): JSX.Element => {
  return <Button onClick={onClick}>+ Add {entity}</Button>;
};

export default CreateButton;
