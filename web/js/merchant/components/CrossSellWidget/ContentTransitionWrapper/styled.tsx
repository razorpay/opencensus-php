import styled from 'styled-components';

export const ContentTransiton = styled.div(({ transition, id, selectedViewId }) => {
  return `
    position: absolute;
    opacity: ${id === selectedViewId ? 1 : 0};
    transition: ${transition};
    transform: ${id === selectedViewId ? 'translateX(0)' : 'translateX(40px)'};
    z-index: ${id === selectedViewId ? 1 : 0};
    pointer-events: ${id === selectedViewId ? 'auto' : 'none'}
  `;
});
