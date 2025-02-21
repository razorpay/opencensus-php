import styled from 'styled-components';

export const CustomBlockBody = styled.div(
  ({ height }: { height: string }) => `
    transition: max-height 0.5s ease-out;
    max-height: ${height ?? '0'}px;
    overflow: hidden;
`,
);
