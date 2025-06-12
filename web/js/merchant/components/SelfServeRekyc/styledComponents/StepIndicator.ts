import styled from "styled-components";

export const DividerWrapper = styled.span(({isMobile}: {isMobile: boolean}) => {
  if(isMobile){
    return `
      transform: rotate(90deg);
      position: relative;
      top: -12px;
      left: -30px;
    `
  }

  return `
    transform: rotate(90deg);
    position: relative;
    top: -12px;
  `
})