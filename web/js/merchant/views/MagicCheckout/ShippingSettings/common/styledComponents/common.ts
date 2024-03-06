import styled from 'styled-components';

export const UploadZonesButton = styled.button<{ width?: number; height?: number }>(
  ({ theme, width, height }) => `
    width: ${width || 315}px;
    height: ${height || 75}px;
    background: ${theme.colors.brand.primary[300]};
    border: 1px dashed ${theme.colors.brand.primary[500]};
    border-radius: 3px;
    color: ${theme.colors.brand.primary[500]};
    font-weight: 700;
    margin-top: 8px;
  `,
);
