import styled from 'styled-components';

export const StyledEllipse = styled.div`
  width: 15px;
  height: 30px;
  position: absolute;
  border: 1.5px solid #1b4498;
  border-bottom-right-radius: 30px;
  border-top-right-radius: 30px;
  border-left: none;
  top: 15px;
  left: 0;
`;
export const StyledEllipseLeft = styled(StyledEllipse)`
  top: 50%;
  left: calc(100% - 52%);
  transform: rotate(180deg);
`;

export const StyledSquare = styled.div`
  width: 31px;
  height: 31px;
  transform: rotate(45deg);
  border-top: 1.5px solid #682241;
  border-right: 1.5px solid #682241;
  position: absolute;
  bottom: 34px;
  left: -15px;
`;
export const IntegrationModalIcon = styled.img`
  position: absolute;
  top: 0;
  left: calc(100% - 52%);
  margin-top: 34px;
  z-index: 1;
  width: 40px;
  height: 40px;
  background: #fff;
  padding: 4px;
  filter: drop-shadow(0px 0px 4px rgba(0, 0, 0, 0.15));
`;
export const StyledSquareBottom = styled(StyledSquare)`
  bottom: -15px;
  border-top: 1.5px solid #1b4498;
  border-right: 1.5px solid #1b4498;
  left: 40%;
  transform: rotate(315deg);
`;
